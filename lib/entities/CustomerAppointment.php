<?php
namespace Bookly\Lib\Entities;

use Bookly\Lib;

/**
 * Class CustomerAppointment
 * @package Bookly\Lib\Entities
 */
class CustomerAppointment extends Lib\Entity
{
    const STATUS_PENDING   = 'pending';
    const STATUS_APPROVED  = 'approved';
    const STATUS_CANCELLED = 'cancelled';

    protected static $table = 'ab_customer_appointments';

    protected static $schema = array(
        'id'                  => array( 'format' => '%d' ),
        'customer_id'         => array( 'format' => '%d', 'reference' => array( 'entity' => 'Customer' ) ),
        'appointment_id'      => array( 'format' => '%d', 'reference' => array( 'entity' => 'Appointment' ) ),
        'number_of_persons'   => array( 'format' => '%d', 'default' => 1 ),
        'extras'              => array( 'format' => '%s', 'default' => '[]' ),
        'custom_fields'       => array( 'format' => '%s', 'default' => '[]' ),
        'status'              => array( 'format' => '%s' ),
        'token'               => array( 'format' => '%s' ),
        'time_zone_offset'    => array( 'format' => '%d' ),
        'payment_id'          => array( 'format' => '%d' ),
        'locale'              => array( 'format' => '%s', 'default' => null ),
        'compound_service_id' => array( 'format' => '%d', 'default' => null ),
        'compound_token'      => array( 'format' => '%s', 'default' => null ),
    );

    /** @var Customer */
    public $customer = null;

    /**
     * Save entity to database.
     * Generate token before saving.
     *
     * @return int|false
     */
    public function save()
    {
        // Generate new token if it is not set.
        if ( $this->get( 'token' ) == '' ) {
            $this->set( 'token', self::generateToken( 'token' ) );
        }
        $this->set( 'locale', apply_filters( 'wpml_current_language', null ) );

        return parent::save();
    }


    /**
     * Get array of custom fields with labels and values.
     *
     * @return array
     */
    public function getCustomFields()
    {
        $service_id = null;
        if ( get_option( 'ab_custom_fields_per_service' ) ) {
            $appointment = new Appointment();
            $appointment->load( $this->get( 'appointment_id' ) );
            $service_id = $appointment->get( 'service_id' );
        }
        $result = array();
        if ( $this->get( 'custom_fields' ) != '[]' ) {
            $custom_fields = array();
            foreach ( Lib\Utils\Common::getTranslatedCustomFields( $service_id ) as $field ) {
                $custom_fields[ $field->id ] = $field;
            }
            $data = json_decode( $this->get( 'custom_fields' ), true );
            if ( is_array( $data ) ) {
                foreach ( $data as $customer_custom_field ) {
                    if ( array_key_exists( $customer_custom_field['id'], $custom_fields ) ) {
                        $field = $custom_fields[ $customer_custom_field['id'] ];
                        $translated_value = array();
                        if ( array_key_exists( 'value', $customer_custom_field ) ) {
                            // Custom field have items ( radio group, etc. )
                            if ( property_exists( $field, 'items' ) ) {
                                foreach ( $field->items as $item ) {
                                    // Customer select many values ( checkbox )
                                    if ( is_array( $customer_custom_field['value'] ) ) {
                                        foreach ( $customer_custom_field['value'] as $field_value ) {
                                            if ( $item['value'] == $field_value ) {
                                                $translated_value[] = $item['label'];
                                            }
                                        }
                                    } elseif ( $item['value'] == $customer_custom_field['value'] ) {
                                        $translated_value[] = $item['label'];
                                    }
                                }
                            } else {
                                $translated_value[] = $customer_custom_field['value'];
                            }
                        }
                        $result[] = array(
                            'id'    => $customer_custom_field['id'],
                            'label' => $field->label,
                            'value' => implode( ', ', $translated_value )
                        );
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get formatted custom fields.
     *
     * @param string $format
     * @return string
     */
    public function getFormattedCustomFields( $format )
    {
        $result = '';
        switch ( $format ) {
            case 'html':
                foreach ( $this->getCustomFields() as $custom_field ) {
                    if ( $custom_field['value'] != '' ) {
                        $result .= sprintf(
                            '<tr valign=top><td>%s:&nbsp;</td><td>%s</td></tr>',
                            $custom_field['label'], $custom_field['value']
                        );
                    }
                }
                if ( $result != '' ) {
                    $result = "<table cellspacing=0 cellpadding=0 border=0>$result</table>";
                }
                break;

            case 'text':
                foreach ( $this->getCustomFields() as $custom_field ) {
                    if ( $custom_field['value'] != '' ) {
                        $result .= sprintf(
                            "%s: %s\n",
                            $custom_field['label'], $custom_field['value']
                        );
                    }
                }
                break;
        }

        return $result;
    }

    /**
     * Delete entity and delete payment, appointment if there are no more customers.
     * 
     * @param bool $compound
     */
    public function deleteCascade( $compound = false )
    {
        $this->delete();

        // Load appointment.
        $appointment = new Appointment();
        if ( $appointment->load( $this->get( 'appointment_id' ) ) ) {
            // Check if there are any customers left.
            if ( CustomerAppointment::query()->where( 'appointment_id', $appointment->get( 'id' ) )->count() == 0 ) {
                // If no customers then delete the appointment.
                $appointment->delete();
            } else {
                // If there are customers then recalculate extras duration.
                if ( $this->get( 'extras' ) != '[]' ) {
                    $extras_duration = $appointment->getMaxExtrasDuration();
                    if ( $appointment->get( 'extras_duration' ) != $extras_duration ) {
                        $appointment->set( 'extras_duration', $extras_duration );
                        $appointment->save();
                    }
                }
                // Update GC event.
                $appointment->handleGoogleCalendar();
            }
            if ( $compound && $this->get( 'compound_token' ) ) {
                /** @var CustomerAppointment[] $ca_list */
                $ca_list = CustomerAppointment::query()->where( 'compound_token', $this->get( 'compound_token' ) )->where( 'compound_service_id', $this->get( 'compound_service_id' ) )->find();
                foreach ( $ca_list as $ca ) {
                    $ca->deleteCascade();
                }
            }
        }
    }

    public function getStatusTitle()
    {
        return self::statusToString( $this->get( 'status' ) );
    }

    static public function statusToString( $status )
    {
        switch ( $status ) {
            case self::STATUS_PENDING:   return __( 'Pending',   'bookly' );
            case self::STATUS_APPROVED:  return __( 'Approved',  'bookly' );
            case self::STATUS_CANCELLED: return __( 'Cancelled', 'bookly' );
            default: return '';
        }
    }

    /**
     * Generate unique token.
     *
     * @param $token_field
     * @return string
     */
    static public function generateToken( $token_field )
    {
        $test = new self();
        do {
            $token = md5( uniqid( time(), true ) );
        }
        while ( $test->loadBy( array( $token_field => $token ) ) === true );

        return $token;
    }

}