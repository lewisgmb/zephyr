<?php
namespace Bookly\Backend\Modules\Appointments;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Backend\Modules\Appointments
 */
class Controller extends Lib\Controller
{
    public function index()
    {
        /** @var \WP_Locale $wp_locale */
        global $wp_locale;

        $this->enqueueStyles( array(
            'frontend' => get_option( 'ab_settings_phone_default_country' ) == 'disabled'
                ? array()
                : array( 'css/intlTelInput.css' ),
            'backend'  => array(
                'css/jquery-ui-theme/jquery-ui.min.css',
                'css/bookly.main-backend.css',
                'bootstrap/css/bootstrap.min.css',
                'css/daterangepicker.css',
                'css/chosen.min.css',
            ),
            'module'   => array( 'css/styles.css', ),
        ) );

        $this->enqueueScripts( array(
            'backend'  => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/angular.min.js',
                'js/angular-sanitize.min.js'    => array( 'ab-angular.min.js' ),
                'js/angular-ui-utils.min.js'    => array( 'ab-angular.min.js' ),
                'js/ng-new_customer_dialog.js'  => array( 'ab-angular.min.js' ),
                'js/angular-ui-date-0.0.8.js'   => array( 'ab-angular.min.js' ),
                'js/moment.min.js',
                'js/daterangepicker.js'   => array( 'jquery' ),
                'js/chosen.jquery.min.js' => array( 'jquery' ),
                'js/ng-edit_appointment_dialog.js' => array( 'ab-angular-ui-date-0.0.8.js', 'jquery-ui-datepicker' ),
            ),
            'frontend' => get_option( 'ab_settings_phone_default_country' ) == 'disabled'
                ? array()
                : array( 'js/intlTelInput.min.js' => array( 'jquery' ) ),
            'module'   => array(
                'js/ng-app.js' => array( 'jquery', 'ab-angular.min.js', 'ab-angular-ui-utils.min.js' ),
            ),
        ) );

        wp_localize_script( 'ab-ng-app.js', 'BooklyL10n', array(
            'tomorrow'         => __( 'Tomorrow', 'bookly' ),
            'today'         => __( 'Today', 'bookly' ),
            'yesterday'     => __( 'Yesterday', 'bookly' ),
            'last_7'        => __( 'Last 7 Days', 'bookly' ),
            'last_30'       => __( 'Last 30 Days', 'bookly' ),
            'this_month'    => __( 'This Month', 'bookly' ),
            'next_month'    => __( 'Next Month', 'bookly' ),
            'custom_range'  => __( 'Custom Range', 'bookly' ),
            'apply'         => __( 'Apply', 'bookly' ),
            'cancel'        => __( 'Cancel', 'bookly' ),
            'to'            => __( 'To', 'bookly' ),
            'from'          => __( 'From', 'bookly' ),
            'calendar'      => array(
                'longMonths'  => array_values( $wp_locale->month ),
                'shortMonths' => array_values( $wp_locale->month_abbrev ),
                'longDays'    => array_values( $wp_locale->weekday ),
                'shortDays'   => array_values( $wp_locale->weekday_abbrev ),
            ),
            'dpDateFormat'  => Lib\Utils\DateTime::convertFormat( 'date', Lib\Utils\DateTime::FORMAT_JQUERY_DATEPICKER ),
            'mjsDateFormat' => Lib\Utils\DateTime::convertFormat( 'date', Lib\Utils\DateTime::FORMAT_MOMENT_JS ),
            'startOfWeek'   => (int) get_option( 'start_of_week' ),
            'intlTelInput'  => array(
                'enabled'   => ( get_option( 'ab_settings_phone_default_country' ) != 'disabled' ),
                'utils'     => plugins_url( 'intlTelInput.utils.js', AB_PATH . '/frontend/resources/js/intlTelInput.utils.js' ),
                'country'   => get_option( 'ab_settings_phone_default_country' ),
            ),
            'please_select_at_least_one_row' => __( 'Please select at least one appointment.', 'bookly' ),
            'cf_per_service' => get_option( 'ab_custom_fields_per_service' ),
            'default_status' => get_option( 'ab_settings_default_appointment_status' ),
            'title'          => array(
                'payment'          => __( 'Payment',  'bookly' ),
                'edit_appointment' => __( 'Edit appointment', 'bookly' ),
                'new_appointment'  => __( 'New appointment',  'bookly' ),
            ),
        ) );
        // Custom fields without captcha field.
        $custom_fields = array_filter( json_decode( get_option( 'ab_custom_fields' ) ), function( $field ) { return ! in_array( $field->type, array( 'captcha', 'text-content' ) ); } );
        // Filters data
        $staff_members = Lib\Entities\Staff::query( 's' )->select( 's.id, s.full_name' )->fetchArray();
        $customers = Lib\Entities\Customer::query( 'c' )->select( 'c.id, c.name' )->fetchArray();
        $services  = Lib\Entities\Service::query( 's' )->select( 's.id, s.title' )->where( 'type', Lib\Entities\Service::TYPE_SIMPLE )->fetchArray();
        $this->render( 'index', compact( 'custom_fields', 'staff_members', 'customers', 'services' ) );
    }

    /**
     * Get list of appointments.
     */
    public function executeGetAppointments()
    {
        $response = array(
            'appointments' => array(),
            'total'        => 0,
            'pages'        => 0,
            'active_page'  => 0
        );

        $page  = (int) $this->getParameter( 'page' );
        $query = $this->_applyParameters( Lib\Entities\CustomerAppointment::query( 'ca' ), $this->getParameters() );

        $items_per_page = 20;
        $total = clone $query;
        $total = $total->count();

        if ( $total ) {
            $pages = ceil( $total / $items_per_page );
            if ( $page < 1 || $page > $pages ) {
                $page = 1;
            }
            $query->limit( $items_per_page )->offset( ( $page - 1 ) * $items_per_page );

            // Populate response.
            $response['appointments'] = $this->_fetchAppointmentsQuery( $query );
            $response['total']        = $total;
            $response['pages']        = $pages;
            $response['active_page']  = $page;
        }

        wp_send_json_success( $response );
    }

    /**
     * Delete customer appointments.
     */
    public function executeDeleteCustomerAppointments()
    {
        /** @var Lib\Entities\CustomerAppointment $ca */
        foreach ( Lib\Entities\CustomerAppointment::query()->whereIn( 'id', $this->getParameter( 'ids' ) )->find() as $ca ) {
            $ca->deleteCascade();
        }
        wp_send_json_success();
    }

    /**
     * Print Appointments
     */
    public function executePrintAppointments()
    {
        $response = null;
        $titles   = array();
        $values   = array();
        $query    = $this->_applyParameters( Lib\Entities\CustomerAppointment::query( 'ca' ), $this->getParameters() );

        foreach ( $this->getParameter( 'app_print' ) as $key => $value ) {
            $titles[] = $value;
            $values[] = $key;
        }

        $total = clone $query;
        $total = $total->count();

        if ( $total ) {
            $response = $this->_fetchAppointmentsQuery( $query );
        }

        $this->render( '_print_table' , compact ( 'response', 'titles', 'values' ) ); exit;
    }

    /**
     * Export Appointment to CSV
     */
    public function executeExportAppointments()
    {
        $delimiter  = $this->getParameter( 'export_appointments_delimiter', ',' );
        $query      = $this->_applyParameters( Lib\Entities\CustomerAppointment::query( 'ca' ), $this->getParameters() );
        
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=Appointments.csv' );

        $header = array();
        $column = array();

        foreach ( $this->getParameter( 'app_exp' ) as $key => $value ) {
            $header[] = $value;
            $column[] = $key;
        }

        $custom_fields = array();
        if ( $this->getParameter( 'custom_fields' ) ) {
            $fields_data = array_filter( json_decode( get_option( 'ab_custom_fields' ) ), function( $field ) { return ! in_array( $field->type, array( 'captcha', 'text-content' ) ); } );
            foreach ( $fields_data as $field_data ) {
                $custom_fields[ $field_data->id ] = '';
                $header[] = $field_data->label;
            }
        }

        $output = fopen( 'php://output', 'w' );
        fwrite( $output, pack( 'CCC', 0xef, 0xbb, 0xbf ) );
        fputcsv( $output, $header, $delimiter );

        $rows = $query->select( 'a.id,
            ca.appointment_id,
            a.start_date,
            a.end_date,
            a.staff_id,
            a.extras_duration,
            ca.status,
            ca.id        AS ca_id,
            c.name       AS customer_name,
            c.phone      AS customer_phone,
            c.email      AS customer_email,
            s.title      AS service_title,
            s.duration   AS service_duration,
            st.full_name AS staff_name,
            p.total      AS payment,
            p.type       AS payment_type,
            p.status     AS payment_status' )
        ->leftJoin( 'Appointment', 'a', 'a.id = ca.appointment_id' )
        ->leftJoin( 'Service', 's', 's.id = a.service_id' )
        ->leftJoin( 'Customer', 'c', 'c.id = ca.customer_id' )
        ->leftJoin( 'Payment', 'p', 'p.id = ca.payment_id' )
        ->leftJoin( 'Staff', 'st', 'st.id = a.staff_id' )
        ->leftJoin( 'StaffService', 'ss', 'ss.staff_id = st.id AND ss.service_id = s.id' )
        ->fetchArray();

        foreach ( $rows as $row ) {
            $row_data = array_fill( 0, count( $column ), '' );
            foreach ( $row as $key => $value ) {
                $pos = array_search( $key, $column );
                if ( $pos !== false ) {
                    if ( $key == 'service_duration' ) {
                        $row_data[ $pos ] = Lib\Utils\DateTime::secondsToInterval( $value );
                        if ( $row['extras_duration'] > 0 ) {
                            $row_data[ $pos ] .= ' + ' . Lib\Utils\DateTime::secondsToInterval( $row['extras_duration'] );
                        }
                    } elseif ( $key == 'payment' ) {
                        $payment_title = '';
                        if ( $row['payment'] !== null ) {
                            $payment_title = Lib\Utils\Common::formatPrice( $row['payment'] ) . ' ' . Lib\Entities\Payment::typeToString( $row['payment_type'] );
                            if ( $row['payment_type'] != Lib\Entities\Payment::TYPE_LOCAL ) {
                                $payment_title .= ' ' . Lib\Entities\Payment::statusToString( $row['payment_status'] );
                            }
                        }
                        $row_data[ $pos ] = $payment_title;
                    } elseif ( $key == 'status' ) {
                        $row_data[ $pos ] = Lib\Entities\CustomerAppointment::statusToString( $value );
                    } else {
                        $row_data[ $pos ] = $value;
                    }
                }
            }

            if ( $this->getParameter( 'custom_fields' ) ) {
                $customer_appointment = new Lib\Entities\CustomerAppointment();
                $customer_appointment->load( $row['ca_id'] );
                foreach ( $customer_appointment->getCustomFields() as $custom_field ) {
                    $custom_fields[ $custom_field['id'] ] = $custom_field['value'];
                }
            }

            fputcsv( $output, array_merge( $row_data, $custom_fields ), $delimiter );
            // Each custom field in an individual column.
            $custom_fields = array_map( function () { return ''; }, $custom_fields );
        }
        fclose( $output );

        exit;
    }

    /**
     * @param Lib\Query $query
     * @param           $parameters
     * @return Lib\Query
     */
    private function _applyParameters( Lib\Query $query, $parameters )
    {
        $date_start = isset( $parameters['date_start'] ) ? $parameters['date_start'] : $parameters['filter']['date_start'];
        $date_end   = isset( $parameters['date_end'] ) ? $parameters['date_end'] : $parameters['filter']['date_end'];
        $start_date = date_create( $date_start )->format( 'Y-m-d 00:00:00' );
        $end_date   = date_create( $date_end )->modify( '+1 day' )->format( 'Y-m-d 00:00:00' );
        $query->leftJoin( 'Appointment', 'a', 'a.id = ca.appointment_id' )
              ->whereBetween( 'a.start_date', $start_date, $end_date );
        $key_field = array(
            'booking_number' => 'a.id',
            'customer'       => 'ca.customer_id',
            'service'        => 'a.service_id',
            'staff_member'   => 'a.staff_id',
        );
        $filter = $parameters['filter'];
        foreach ( $key_field as $key => $field ) {
            if ( ! empty( $filter[ $key ] ) ) {
                $query->where( $field, $filter[ $key ] );
            }
        }
        if ( in_array( $filter['status'], array( Lib\Entities\CustomerAppointment::STATUS_PENDING, Lib\Entities\CustomerAppointment::STATUS_APPROVED, Lib\Entities\CustomerAppointment::STATUS_CANCELLED ) ) ) {
            $query->where( 'ca.status', $filter['status'] );
        }

        $sort  = in_array( $parameters['sort'], array( 'id', 'staff_name', 'service_title', 'start_date', 'customer_name', 'service_duration', 'status', 'payment' ) )
            ? $parameters['sort']
            : 'start_date';
        $order = $parameters['order'] == 'desc' ? 'desc' : 'asc';

        $query->sortBy( $sort )->order( $order );

        return $query;
    }

    /**
     * @param Lib\Query $query
     * @return array
     */
    private function _fetchAppointmentsQuery( Lib\Query $query )
    {
        $rows = $query->select( 'a.id,
            ca.appointment_id,
            ca.payment_id,
            ca.status,
            ca.id        AS ca_id,
            a.start_date,
            a.end_date,
            a.staff_id,
            a.extras_duration,
            c.name       AS customer_name,
            s.title      AS service_title,
            s.duration   AS service_duration,
            st.full_name AS staff_name,
            p.total      AS payment,
            p.type       AS payment_type,
            p.status     AS payment_status' )
        ->leftJoin( 'Service', 's', 's.id = a.service_id' )
        ->leftJoin( 'Customer', 'c', 'c.id = ca.customer_id' )
        ->leftJoin( 'Payment', 'p', 'p.id = ca.payment_id' )
        ->leftJoin( 'Staff', 'st', 'st.id = a.staff_id' )
        ->leftJoin( 'StaffService', 'ss', 'ss.staff_id = st.id AND ss.service_id = s.id' )
        ->fetchArray();

        foreach ( $rows as &$row ) {
            $payment_title = '';
            if ( $row['payment'] !== null ) {
                $payment_title = Lib\Utils\Common::formatPrice( $row['payment'] ) . ' ' . Lib\Entities\Payment::typeToString( $row['payment_type'] );
                if ( $row['payment_type'] != Lib\Entities\Payment::TYPE_LOCAL ) {
                    $payment_title .= '&nbsp;<span class="ab-pay-status-' . $row['payment_status'] . '">' . Lib\Entities\Payment::statusToString( $row['payment_status'] ) . '</span>';
                }
            }
            $row['payment_title'] = $payment_title;
            $row['start_date_f']  = Lib\Utils\DateTime::formatDateTime( $row['start_date'] );
            $row['status'] = Lib\Entities\CustomerAppointment::statusToString( $row['status'] );
            $row['service_duration'] = Lib\Utils\DateTime::secondsToInterval( $row['service_duration'] );
            if ( $row['extras_duration'] > 0 ) {
                $row['service_duration'] .= ' + ' . Lib\Utils\DateTime::secondsToInterval( $row['extras_duration'] );
            }
        }

        return $rows;
    }

    /**
     * Override parent method to add 'wp_ajax_ab_' prefix
     * so current 'execute*' methods look nicer.
     *
     * @param string $prefix
     */
    protected function registerWpActions( $prefix = '' )
    {
        parent::registerWpActions( 'wp_ajax_ab_' );
    }

}