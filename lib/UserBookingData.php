<?php
namespace Bookly\Lib;

/**
 * Class UserBookingData
 * @package Bookly\Frontend\Modules\Booking\Lib
 */
class UserBookingData
{
    private $form_id = null;

    /**
     * @var Entities\Coupon|null
     */
    private $coupon = null;

    /**
     * @var array
     */
    private $booking_numbers = array();

    /**
     * Data provided by user at booking steps
     * and stored in PHP session.
     * @var array
     */
    private $data = array(
        // Step 0
        'time_zone_offset' => null,
        'cart'             => array(),
        // Step details
        'name'             => null,
        'email'            => null,
        'phone'            => null,
        // Step payment
        'coupon'           => null,
    );

    /**
     * Cart item draft with default values.
     * @var array
     */
    private $cart_item = array(
        // Step service
        'service_id'        => null,
        'staff_ids'         => null,
        'number_of_persons' => null,
        'date_from'         => null,
        'days'              => null,
        'time_from'         => null,
        'time_to'           => null,
        // Step extras
        'extras'            => array(),
        // Step time
        'slots'             => null,
    );

    /**
     * Key of current cart item.
     * @var int|null
     */
    private $cart_key = null;

    private $cart_services = array();
    private $cart_staff = array();
    private $cart_service_prices = array();

    /**
     * Constructor.
     *
     * @param $form_id
     */
    public function __construct( $form_id )
    {
        $this->form_id = $form_id;

        // Set up custom fields depending on whether they are bound to services or not.
        if ( get_option( 'ab_custom_fields_per_service' ) ) {
            $this->cart_item['custom_fields'] = null;
        } else {
            $this->data['custom_fields'] = null;
        }

        // Set up default parameters.
        $prior_time = Config::getMinimumTimePriorBooking();
        $this->cart_item['date_from'] = date( 'Y-m-d', current_time( 'timestamp' ) + $prior_time );
        $times = Entities\StaffScheduleItem::query()
            ->select( 'SUBSTRING_INDEX(MIN(start_time), ":", 2) AS min_end_time,' .
                      'SUBSTRING_INDEX(MAX(end_time),   ":", 2) AS max_end_time' )
            ->whereNot( 'start_time', null )
            ->fetchRow();
        $this->cart_item['time_from'] = $times['min_end_time'];
        $this->cart_item['time_to']   = $times['max_end_time'];

        // If logged in then set name, email and if existing customer then also phone.
        $current_user = wp_get_current_user();
        if ( $current_user && $current_user->ID ) {
            $customer = new Entities\Customer();
            if ( $customer->loadBy( array( 'wp_user_id' => $current_user->ID ) ) ) {
                $this->set( 'name',  $customer->get( 'name' ) );
                $this->set( 'email', $customer->get( 'email' ) );
                $this->set( 'phone', $customer->get( 'phone' ) );
            } else {
                $this->set( 'name',  $current_user->display_name );
                $this->set( 'email', $current_user->user_email );
            }
        }
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        Session::setFormVar( $this->form_id, 'data', $this->data );
        Session::setFormVar( $this->form_id, 'booking_numbers', $this->booking_numbers );
        Session::setFormVar( $this->form_id, 'last_touched', time() );
    }

    /**
     * Set data parameter.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function set( $name, $value )
    {
        if ( array_key_exists( $name, $this->cart_item ) ) {
            if ( $name == 'extras' && ! is_array( $value ) ) {
                $value = array();
            }
            $this->data['cart'][ $this->cart_key ][ $name ] = $value;
        } elseif ( array_key_exists( $name, $this->data ) ) {
            $this->data[ $name ] = $value;
        }
    }

    /**
     * Get data parameter.
     *
     * @param string $name
     * @return mixed
     */
    public function get( $name )
    {
        if ( array_key_exists( $name, $this->cart_item ) ) {
            return $this->data['cart'][ $this->cart_key ][ $name ];
        }

        return $this->data[ $name ];
    }

    /**
     * Load data from session.
     *
     * @return bool
     */
    public function load()
    {
        $data = Session::getFormVar( $this->form_id, 'data' );
        if ( $data !== null ) {
            // Restore data.
            $this->data = $data;
            end( $this->data['cart'] );
            $this->cart_key = key( $this->data['cart'] );
            // Restore booking numbers.
            $this->booking_numbers = Session::getFormVar( $this->form_id, 'booking_numbers' );

            return true;
        }

        return false;
    }

    /**
     * Get data.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set data.
     *
     * @param array $data
     */
    public function setData( array $data )
    {
        $this->data = $data;
    }

    /**
     * Partially update data in session.
     *
     * @param array $data
     */
    public function fillData( array $data )
    {
        foreach ( $data as $key => $value ) {
            $this->set( $key, $value );
        }
    }

    /**
     * Validate fields.
     *
     * @param $data
     * @return array
     */
    public function validate( $data )
    {
        $validator = new Validator();
        foreach ( $data as $field_name => $field_value ) {
            switch ( $field_name ) {
                case 'service_id':
                    $validator->validateNumber( $field_name, $field_value );
                    break;
                case 'date_from':
                case 'time_from':
                    $validator->validateDateTime( $field_name, $field_value, true );
                    break;
                case 'name':
                    $validator->validateString( $field_name, $field_value, 255, true, true, 3 );
                    break;
                case 'email':
                    $validator->validateEmail( $field_name, $data );
                    break;
                case 'phone':
                    $validator->validatePhone( $field_name, $field_value, true );
                    break;
                case 'custom_fields':
                    $validator->validateCustomFields( $field_value, $data['form_id'], $this->getCartService()->get( 'id' ) );
                    break;
                default:
            }
        }

        if ( isset( $data['time_from'] ) && isset( $data['time_to'] ) ) {
            $validator->validateTimeGt( 'time_from', $data['time_from'], $data['time_to'] );
        }

        return $validator->getErrors();
    }

    /**
     * Save all data and create appointment.
     *
     * @param int $payment_id
     * @return Entities\CustomerAppointment
     */
    public function save( $payment_id = null )
    {
        $return = null;

        $user_id  = get_current_user_id();
        $customer = new Entities\Customer();
        if ( $user_id > 0 ) {
            // Try to find customer by WP user ID.
            $customer->loadBy( array( 'wp_user_id' => $user_id ) );
        }
        if ( ! $customer->isLoaded() ) {
            // If customer with such name & e-mail exists, append new booking to him, otherwise - create new customer
            $customer->loadBy( array(
                'name'  => $this->get( 'name' ),
                'email' => $this->get( 'email' )
            ) );
        }
        $customer->set( 'name',  $this->get( 'name' ) );
        $customer->set( 'email', $this->get( 'email' ) );
        $customer->set( 'phone', $this->get( 'phone' ) );
        if ( get_option( 'ab_settings_create_account', 0 ) && ! $customer->get( 'wp_user_id' ) ) {
            // Create WP user and link it to customer.
            $customer->setWPUser( $user_id );
        }
        $customer->save();

        $service = $this->getCartService();
        $compound_service_id = null;
        $compound_token      = null;
        if ( $service->get( 'type' ) == Entities\Service::TYPE_COMPOUND ) {
            $compound_service_id = $service->get( 'id' );
            $compound_token      = Entities\CustomerAppointment::generateToken( 'compound_token' );
        }

        $extras = json_encode( $this->get( 'extras' ) );
        $custom_fields = $this->get( 'custom_fields' );
        foreach ( $this->get( 'slots' ) as $slot ) {
            list ( $service_id, $staff_id, $timestamp ) = $slot;
            if ( ! isset( $this->cart_services[ $service_id ] ) ) {
                $this->cart_services[ $service_id ] = Entities\Service::query()->where( 'id', $service_id )->findOne();
            }
            $service = $this->cart_services[ $service_id ];

            /*
             * Get appointment with the same params.
             * If it exists -> create connection to this appointment,
             * otherwise create appointment and connect customer to new appointment
             */
            $appointment = new Entities\Appointment();
            $appointment->loadBy( array(
                'service_id' => $service_id,
                'staff_id'   => $staff_id,
                'start_date' => date( 'Y-m-d H:i:s', $timestamp ),
            ) );
            if ( $appointment->isLoaded() == false ) {
                $appointment->set( 'service_id', $service_id );
                $appointment->set( 'staff_id',   $staff_id );
                $appointment->set( 'start_date', date( 'Y-m-d H:i:s', $timestamp ) );
                $appointment->set( 'end_date',   date( 'Y-m-d H:i:s', $timestamp + $service->get( 'duration' ) ) );
                $appointment->save();
            }

            // Create CustomerAppointment record.
            $customer_appointment = new Entities\CustomerAppointment();
            $customer_appointment->set( 'customer_id',         $customer->get( 'id' ) );
            $customer_appointment->set( 'appointment_id',      $appointment->get( 'id' ) );
            $customer_appointment->set( 'payment_id',          $payment_id );
            $customer_appointment->set( 'number_of_persons',   $this->get( 'number_of_persons' ) );
            $customer_appointment->set( 'extras',              $extras );
            $customer_appointment->set( 'custom_fields',       $custom_fields );
            $customer_appointment->set( 'status',              get_option( 'ab_settings_default_appointment_status' ) );
            $customer_appointment->set( 'time_zone_offset',    $this->get( 'time_zone_offset' ) );
            $customer_appointment->set( 'compound_service_id', $compound_service_id );
            $customer_appointment->set( 'compound_token',      $compound_token );
            $customer_appointment->save();

            // Handle extras duration.
            if ( get_option( 'bookly_service_extras_step_extras_enabled' ) == 1 ) {
                $appointment->set( 'extras_duration', $appointment->getMaxExtrasDuration() );
                $appointment->save();
            }

            // Google Calendar.
            $appointment->handleGoogleCalendar();

            // Add booking number.
            $this->booking_numbers[] = $appointment->get('id');
            $this->booking_numbers = array_unique( $this->booking_numbers );

            if ( $return === null ) {
                // Return very first CustomerAppointment.
                $return = $customer_appointment;
            }
            // Only firs service have custom fields, extras (compound).
            $custom_fields = $extras = '[]';
        }

        return $return;
    }

    /**
     * Get coupon.
     *
     * @return Entities\Coupon|false
     */
    public function getCoupon()
    {
        if ( $this->coupon === null ) {
            $coupon = new Entities\Coupon();
            $coupon->loadBy( array(
                'code' => $this->get( 'coupon' ),
            ) );
            if ( $coupon->isLoaded() && $coupon->get( 'used' ) < $coupon->get( 'usage_limit' ) ) {
                $this->coupon = $coupon;
            } else {
                $this->coupon = false;
            }
        }

        return $this->coupon;
    }

    /**
     * Get staff id selected in the first step of booking.
     *
     * @return int
     */
    public function getInitialStaffId()
    {
        $ids = $this->get( 'staff_ids' );
        if ( count( $ids ) == 1 ) {
            return $ids[0];
        }

        return 0;
    }

    /**
     * Get staff name selected in the first step of booking.
     *
     * @return string
     */
    public function getInitialStaffName()
    {
        $staff_id = $this->getInitialStaffId();

        if ( $staff_id ) {
            $staff = new Entities\Staff();
            $staff->load( $staff_id );

            return $staff->getName();
        }

        return __( 'Any', 'bookly' );
    }

    /**
     * Get staff info selected in the first step of booking.
     *
     * @return string
     */
    public function getInitialStaffInfo()
    {
        $staff_id = $this->getInitialStaffId();

        if ( ! isset ( $this->cart_staff[ $staff_id ] ) ) {
            $staff = new Entities\Staff();
            $staff->load( $staff_id );
            $this->cart_staff[ $staff_id ]['name'] = $staff->getName();
            $this->cart_staff[ $staff_id ]['info'] = $staff->getInfo();
        }

        return $this->cart_staff[ $staff_id ]['info'];
    }

    /**
     * Get service price for staff selected in the first step of booking.
     *
     * @return string|false
     */
    public function getInitialServicePrice()
    {
        $staff_service = new Entities\StaffService();
        $staff_service->loadBy( array(
            'staff_id'   => $this->getInitialStaffId(),
            'service_id' => $this->get( 'service_id' )
        ) );

        return $staff_service->isLoaded() ? (float) $staff_service->get( 'price' ) : false;
    }

    /**
     * Get service for current cart item.
     *
     * @return Entities\Service
     */
    public function getCartService()
    {
        $service_id = $this->get( 'service_id' );

        if ( ! isset ( $this->cart_services[ $service_id ] ) ) {
            $service = new Entities\Service();
            $service->load( $service_id );
            $this->cart_services[ $service_id ] = $service;
        }

        return $this->cart_services[ $service_id ];
    }

    /**
     * Get service price for current cart item.
     *
     * @return float
     */
    public function getCartServicePrice()
    {
        $service = $this->getCartService();
        $price   = 0.0;
        if ( $service->get( 'type' ) == Entities\Service::TYPE_COMPOUND ) {
            $price += $service->get( 'price' );
        } else {
            $slots      = $this->get( 'slots' );
            $staff_id   = $slots[0][1];
            $service_id = $this->get( 'service_id' );
            if ( ! isset ( $this->cart_service_prices[ $staff_id ][ $service_id ] ) ) {
                $staff_service = new Entities\StaffService();
                $staff_service->loadBy( array(
                    'staff_id'   => $staff_id,
                    'service_id' => $service_id,
                ) );
                $this->cart_service_prices[ $staff_id ][ $service_id ] = $staff_service->get( 'price' );
            }
            $price += $this->cart_service_prices[ $staff_id ][ $service_id ];
        }

        return $price + $this->getCartExtrasAmount();
    }

    /**
     * Get staff for current cart item.
     *
     * @return Entities\Staff
     */
    public function getCartStaff()
    {
        $slots = $this->get( 'slots' );
        $staff_id = $slots[0][1];

        if ( ! isset ( $this->cart_staff[ $staff_id ] ) ) {
            $staff = new Entities\Staff();
            $staff->load( $staff_id );
            $this->cart_staff[ $staff_id ] = $staff;
        }

        return $this->cart_staff[ $staff_id ];
    }

    /**
     * Get the sum price of service's extras for the current cart item.
     *
     * @return int
     */
    public function getCartExtrasAmount()
    {
        $amount = 0.0;
        /** @var \BooklyServiceExtras\Lib\Entities\ServiceExtra[] $extras */
        $extras = apply_filters( 'bookly_extras_find_by_ids', array(), $this->get( 'extras' ) );
        foreach ( $extras as $extra ) {
            $amount += $extra->get( 'price' );
        }

        return $amount;
    }

    /**
     * Get the duration of service's extras for the current cart item.
     *
     * @return int
     */
    public function getCartExtrasDuration()
    {
        return apply_filters( 'bookly_extras_get_total_duration', 0, $this->get( 'extras' ) );
    }

    /**
     * Get total price and prices for each appointment
     *
     * @param bool $apply_coupon
     * @return array
     */
    public function getCartInfo( $apply_coupon = true )
    {
        $info = array( 'total_price' => 0, 'items' => array() );
        $this->foreachCartItem( function ( UserBookingData $_this, $cart_key ) use ( &$info ) {
            $info['items'][ $cart_key ] = array(
                'total_price' => $_this->getCartServicePrice() * $_this->get( 'number_of_persons' )
            );
            $info['total_price'] += $info['items'][ $cart_key ]['total_price'];
        } );
        $discount_total = $info['total_price'];
        if ( $apply_coupon ) {
            // Apply coupon.
            $coupon = $this->getCoupon();
            if ( $coupon ) {
                $discount_total = $coupon->apply( $info['total_price'] );
                if ( $discount_total < 0 ) {
                    $discount_total = 0;
                }
            }
        }

        // Apply discount
        if ( $discount_total != 0 ) {
            $ratio = $info['total_price'] / $discount_total;
        }
        foreach ( $info['items'] as &$cart_item ) {
            $cart_item['total_price'] = $discount_total != 0 ? round( $cart_item['total_price'] / $ratio, 2 ) : 0;
        }
        $info['total_price'] = $discount_total;

        // Array like [ 'total_price' => 100,
        //              'items' => [ '3' => [ 'total_price' => 70 ],
        //                           '5' => [ 'total_price' => 30 ] ] ]
        // where 3 and 5 is cart_key of item in cart.
        return $info;
    }

    /**
     * Add empty cart item and set cart_key to it.
     *
     * @return array
     */
    public function addEmptyCartItem()
    {
        $this->data['cart'][] = $this->cart_item;
        end( $this->data['cart'] );
        $this->cart_key = key( $this->data['cart'] );
    }

    /**
     * Set current item key.
     *
     * @param $cart_key
     */
    public function setCartKey( $cart_key )
    {
        if ( ! array_key_exists( $cart_key, $this->data['cart'] ) ) {
            end( $this->data['cart'] );
            $cart_key = key( $this->data['cart'] );
        }
        $this->cart_key = $cart_key;
    }

    /**
     * Get current item key.
     *
     * @return int|null
     */
    public function getCartKey()
    {
        return $this->cart_key;
    }

    /**
     * Remove item from cart.
     *
     * @param $cart_key
     */
    public function dropCartItem( $cart_key )
    {
        unset ( $this->data['cart'][ $cart_key ] );
    }

    /**
     * Drop incomplete items from cart.
     */
    public function dropIncompleteCartItems()
    {
        // We need to check if the step previous to cart has been passed.
        $key = ( get_option( 'ab_custom_fields_per_service' ) == 1 ) ? 'custom_fields' : 'slots';
        foreach ( $this->data['cart'] as $cart_key => $item ) {
            if ( ! isset ( $item[ $key ] ) ) {
                unset ( $this->data['cart'][ $cart_key ] );
            }
        }
    }

    /**
     * Generate title of cart items (used in payments).
     *
     * @param int  $max_length
     * @param bool $multi_byte
     * @return string
     */
    public function getCartItemsTitle( $max_length = 255, $multi_byte = true )
    {
        $title = $this->getCartService()->getTitle();
        $tail  = '';
        $more = count( $this->data['cart'] ) - 1;
        if ( $more > 0 ) {
            $tail = sprintf( _n( ' and %d more item', ' and %d more items', $more, 'bookly' ), $more );
        }

        if ( $multi_byte ) {
            if ( preg_match_all( '/./su', $title . $tail, $matches ) > $max_length ) {
                $length_tail = preg_match_all( '/./su', $tail, $matches );
                $title       = preg_replace( '/^(.{' . ( $max_length - $length_tail - 3 ) . '}).*/su', '$1', $title ) . '...';
            }
        } else {
            if ( strlen( $title . $tail ) > $max_length ) {
                while ( strlen( $title . $tail ) + 3 > $max_length ) {
                    $title = preg_replace( '/.$/su', '', $title );
                }
                $title .= '...';
            }
        }

        return $title . $tail;
    }

    /**
     * Loop through cart items.
     *
     * @param callable $function
     */
    public function foreachCartItem( $function )
    {
        $key = $this->cart_key;

        foreach ( $this->data['cart'] as $cart_key => $cart_item ) {
            $this->cart_key = $cart_key;
            if ( call_user_func( $function, $this, $cart_key, $cart_item ) === false ) {
                break;
            }
        }

        $this->cart_key = $key;
    }

    /**
     * Set payment ( PayPal, 2Checkout, PayU Latam, Payson, Mollie ) transaction status.
     *
     * @param string $gateway
     * @param string $status
     * @param mixed  $data
     */
    public function setPaymentStatus( $gateway, $status, $data = null )
    {
        Session::setFormVar( $this->form_id, 'payment', array(
            'gateway' => $gateway,
            'status'  => $status,
            'data'    => $data,
        ) );
    }

    /**
     * Get and clear ( PayPal, 2Checkout, PayU Latam, Payson ) transaction status.
     *
     * @return array|false
     */
    public function extractPaymentStatus()
    {
        if ( $status = Session::getFormVar( $this->form_id, 'payment' ) ) {
            Session::destroyFormVar( $this->form_id, 'payment' );

            return $status;
        }

        return false;
    }

    /**
     * Return cart_key for not available appointment or NULL
     *
     * @return int|null
     */
    public function getFailedCartKey()
    {
        $failed_cart_key = null;
        $max_date  = date_create( '@' . ( current_time( 'timestamp' ) + Config::getMaximumAvailableDaysForBooking() * DAY_IN_SECONDS ) )->setTime( 0, 0 );

        $this->foreachCartItem( function ( UserBookingData $userData, $cart_key ) use ( &$failed_cart_key, $max_date ) {
            $service     = $userData->getCartService();
            $is_compound = $service->get( 'type' ) == Entities\Service::TYPE_COMPOUND;
            foreach ( $userData->get( 'slots' ) as $slot ) {
                list( $service_id, $staff_id, $timestamp ) = $slot;
                if ( $is_compound ) {
                    if ( ! isset ( $userData->cart_services[ $service_id ] ) ) {
                        $userData->cart_services[ $service_id ] = Entities\Service::query()->where( 'id', $service_id )->findOne();
                    }
                    $service = $userData->cart_services[ $service_id ];
                }
                $bound_beg = date_create( '@' . $timestamp )->modify( '-' . (int) $service->get( 'padding_left' ) . ' sec' );
                $bound_end = date_create( '@' . $timestamp )->modify( ( (int) $service->get( 'duration' ) + (int) $service->get( 'padding_right' ) + $userData->getCartExtrasDuration() ) . ' sec' );

                if ( $bound_end < $max_date ) {
                    $query = Entities\CustomerAppointment::query( 'ca' )
                        ->select( 'ss.capacity, SUM(ca.number_of_persons) AS total_number_of_persons,
                            DATE_SUB(a.start_date, INTERVAL (COALESCE(s.padding_left,0) )  SECOND) AS bound_left,
                            DATE_ADD(a.end_date,   INTERVAL (COALESCE(s.padding_right,0) + a.extras_duration ) SECOND) AS bound_right' )
                        ->leftJoin( 'Appointment', 'a', 'a.id = ca.appointment_id' )
                        ->leftJoin( 'StaffService', 'ss', 'ss.staff_id = a.staff_id AND ss.service_id = a.service_id' )
                        ->leftJoin( 'Service', 's', 's.id = a.service_id' )
                        ->where( 'a.staff_id', $staff_id )
                        ->whereNot( 'ca.status', Entities\CustomerAppointment::STATUS_CANCELLED )
                        ->groupBy( 'a.service_id, a.start_date' )
                        ->havingRaw( '%s > bound_left AND bound_right > %s AND ( total_number_of_persons + %d ) > ss.capacity',
                            array( $bound_end->format( 'Y-m-d H:i:s' ), $bound_beg->format( 'Y-m-d H:i:s' ), $userData->get( 'number_of_persons' ) ) )
                        ->limit( 1 );
                    $rows = $query->execute( Query::HYDRATE_NONE );

                    if ( $rows != 0 ) {
                        // Exist intersect appointment, time not available.
                        $failed_cart_key = $cart_key;
                        return false;
                    }
                }
            }
            return true;
        } );

        return $failed_cart_key;
    }

    /**
     * Get booking numbers.
     *
     * @return array
     */
    public function getBookingNumbers()
    {
        return $this->booking_numbers;
    }
}