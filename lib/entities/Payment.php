<?php
namespace Bookly\Lib\Entities;

use Bookly\Lib;

/**
 * Class Payment
 * @package Bookly\Lib\Entities
 */
class Payment extends Lib\Entity
{
    const TYPE_LOCAL        = 'local';
    const TYPE_COUPON       = 'coupon';  // when price reduced to zero due to coupon
    const TYPE_PAYPAL       = 'paypal';
    const TYPE_STRIPE       = 'stripe';
    const TYPE_AUTHORIZENET = 'authorizeNet';
    const TYPE_2CHECKOUT    = '2checkout';
    const TYPE_PAYULATAM    = 'payulatam';
    const TYPE_PAYSON       = 'payson';
    const TYPE_MOLLIE       = 'mollie';

    const STATUS_COMPLETED  = 'completed';
    const STATUS_PENDING    = 'pending';

    protected static $table = 'ab_payments';

    protected static $schema = array(
        'id'                => array( 'format' => '%d' ),
        'created'           => array( 'format' => '%s' ),
        'type'              => array( 'format' => '%s' ),
        'token'             => array( 'format' => '%s', 'default' => '' ),
        'transaction_id'    => array( 'format' => '%s', 'default' => '' ),
        'total'             => array( 'format' => '%.2f' ),
        'status'            => array( 'format' => '%s', 'default' => self::STATUS_COMPLETED ),
        'details'           => array( 'format' => '%s' ),
    );

    /**
     * @param CustomerAppointment[] $ca_list
     * @param Coupon|null           $coupon
     * @return $this
     */
    public function setDetails( array $ca_list, $coupon = null )
    {
        $details = array( 'items' => array(), 'coupon' => null, 'customer' => null );

        foreach ( $ca_list as $ca ) {
            $data = Appointment::query( 'a' )
                ->select( 'a.service_id, a.staff_id, s.title AS service_name, st.full_name AS staff_name, ca.number_of_persons, a.start_date,
                    IF(ca.compound_service_id IS NULL, ss.price, s.price) AS service_price' )
                ->leftJoin( 'CustomerAppointment', 'ca', 'ca.appointment_id = a.id' )
                ->leftJoin( 'Service', 's', 's.id = COALESCE(ca.compound_service_id, a.service_id)' )
                ->leftJoin( 'StaffService', 'ss', 'ss.staff_id = a.staff_id AND ss.service_id = a.service_id' )
                ->leftJoin( 'Staff', 'st', 'st.id = a.staff_id' )
                ->where( 'ca.id', $ca->get( 'id' ) )
                ->fetchRow();

            $extras = array();
            if ( $ca->get( 'extras' ) != '[]' ) {
                /** @var \BooklyServiceExtras\Lib\Entities\ServiceExtra $extra */
                foreach ( apply_filters( 'bookly_extras_find_by_ids', array(), json_decode( $ca->get( 'extras' ), true ) ) as $extra ) {
                    $extras[] = array(
                        'title' => $extra->get( 'title' ),
                        'price' => $extra->get( 'price' ),
                    );
                }
            }

            $details['items'][] = array(
                'appointment_date'  => $data['start_date'],
                'service_name'      => $data['service_name'],
                'service_price'     => $data['service_price'],
                'number_of_persons' => $data['number_of_persons'],
                'staff_name'        => $data['staff_name'],
                'extras'            => $extras,
            );

            if ( empty( $details['customer'] ) ) {
                $customer = Lib\Entities\Customer::query( 'c' )
                    ->select( 'c.name' )
                    ->leftJoin( 'CustomerAppointment', 'ca', 'ca.customer_id = c.id' )
                    ->where( 'ca.id', $ca->get( 'id' ) )
                    ->fetchRow();
                $details['customer'] = $customer['name'];
            }
        }

        if ( $coupon instanceof Coupon ) {
            $details['coupon'] = array(
                'code'      => $coupon->get( 'code' ),
                'discount'  => $coupon->get( 'discount' ),
                'deduction' => $coupon->get( 'deduction' ),
            );
        }

        $this->set( 'details', json_encode( $details ) );
        
        return $this;
    }
    
    /**
     * Get display name for given payment type.
     *
     * @param string $type
     * @return string
     */
    public static function typeToString( $type )
    {
        switch ( $type ) {
            case self::TYPE_PAYPAL:       return 'PayPal';
            case self::TYPE_LOCAL:        return __( 'Local', 'bookly' );
            case self::TYPE_STRIPE:       return 'Stripe';
            case self::TYPE_AUTHORIZENET: return 'Authorize.Net';
            case self::TYPE_2CHECKOUT:    return '2Checkout';
            case self::TYPE_PAYULATAM:    return 'PayU Latam';
            case self::TYPE_PAYSON:       return 'Payson';
            case self::TYPE_MOLLIE:       return 'Mollie';
            case self::TYPE_COUPON:       return __( 'Coupon', 'bookly' );
            default:                      return '';
        }
    }

    /**
     * Get status of payment.
     *
     * @param string $status
     * @return string
     */
    public static function statusToString( $status )
    {
        switch ( $status ) {
            case self::STATUS_COMPLETED:  return __( 'Completed', 'bookly' );
            case self::STATUS_PENDING:    return __( 'Pending',   'bookly' );
            default:                      return '';
        }
    }

}