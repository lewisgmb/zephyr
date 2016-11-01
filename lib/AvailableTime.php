<?php
namespace Bookly\Lib;

/**
 * Class AvailableTime
 * @package Bookly\Lib
 */
class AvailableTime
{
    /** @var \DateInterval */
    private $one_day = null;

    /** @var UserBookingData */
    private $userData;

    /** @var Entities\Service[] */
    private $services = array();

    private $main_service_id = null;

    private $service_type = null;

    private $is_all_day_service = null;

    private $last_fetched_slot = null;

    private $selected_date = null;

    private $has_more_slots = false;

    private $slots = array();

    /**
     * Constructor.
     *
     * @param UserBookingData $userData
     */
    public function __construct( UserBookingData $userData )
    {
        $this->one_day  = new \DateInterval( 'P1D' );
        $this->userData = $userData;
        $service = $userData->getCartService();
        if ( $service->get( 'type' ) == Entities\Service::TYPE_COMPOUND ) {
            $this->service_type = Entities\Service::TYPE_COMPOUND;
            $sub_services = json_decode( $service->get( 'sub_services' ), true );
            $services = Entities\Service::query()
                ->whereIn( 'id', $sub_services )
                ->where( 'type', Entities\Service::TYPE_SIMPLE )
                ->indexBy( 'id' )
                ->find();
            // Ordering services like sub_services array.
            foreach ( $sub_services as $service_id ) {
                $this->services[] = $services[ $service_id ];
            }
        } else {
            $this->service_type = Entities\Service::TYPE_SIMPLE;
            $this->services[] = $service;
        }
        // Check whether the first service is all day or not.
        // An all day service has duration set to 86400 seconds.
        $this->is_all_day_service = $this->services[0]->get( 'duration' ) == DAY_IN_SECONDS;
    }

    /**
     * Load and init.
     */
    public function load()
    {
        $show_calendar       = Config::showCalendar();
        $time_slot_length    = Config::getTimeSlotLength();
        $show_day_per_column = Config::showDayPerColumn();
        $client_diff         = get_option( 'ab_settings_use_client_time_zone' )
            ? get_option( 'gmt_offset' ) * HOUR_IN_SECONDS + $this->userData->get( 'time_zone_offset' ) * 60
            : 0;

        $prev_slots   = null;
        $prev_service = null;

        // Service id with custom_fields, extras & etc.
        $this->main_service_id = $this->services[0]->get( 'id' );
        $extras_duration       = apply_filters( 'bookly_extras_get_total_duration', 0, $this->userData->get( 'extras' ) );
        foreach ( array_reverse( $this->services ) as $service ) {
            $this->slots = array();
            $slots  = 0; // number of handled slots
            $groups = 0; // number of handled groups

            /**
             * @var int       $req_timestamp
             * @var \DateTime $date
             * @var \DateTime $max_date
             */
            list ( $req_timestamp, $date, $max_date ) = $this->_prepareDates();

            // Prepare staff data.
            $staff_data = $this->_prepareStaffData( $service, $date );

            // The main loop.
            while ( ( $date = $this->_findNextDay( $date, $max_date ) ) && (
                $show_calendar ||
                $show_day_per_column ||  // this loop will break when $groups reaches 10 (see loop body)
                ! $show_day_per_column && $slots < 100  // 10 slots/column * 10 columns
            ) ) {
                foreach ( $this->_findAvailableTime( $staff_data, $service, $date ) as $frame ) {
                    // Loop from start to:
                    //   1. end minus time slot length when 'blocked' or 'not_full' is set.
                    //   2. end minus service duration when nothing is set.
                    $end = null;
                    if ( isset ( $frame['blocked'] ) || isset ( $frame['not_full'] ) ) {
                        $end = $frame['end'] - $time_slot_length;
                    } else {
                        $end = $frame['end'] - $service->get( 'duration' ) - $extras_duration;
                    }
                    for ( $time = $frame['start']; $time <= $end; $time += $time_slot_length ) {

                        $timestamp        = $date->getTimestamp() + $time;
                        $client_timestamp = $timestamp - $client_diff;

                        if ( $client_timestamp < $req_timestamp ) {
                            // When we start 1 day before the requested date we may not need all found slots,
                            // we should skip those slots which do not fit the requested date in client's time zone.
                            continue;
                        }

                        $group = date( 'Y-m-d', ( $service->get( 'duration' ) == DAY_IN_SECONDS && ! $show_calendar )
                            ? strtotime( 'first day of this month', $client_timestamp )  // group slots by months
                            : $client_timestamp                                          // group slots by days
                        );

                        // Create/update slots.
                        if ( ! isset ( $this->slots[ $group ][ $client_timestamp ] ) ) {
                            $data = null;

                            if ( $prev_slots === null ) {
                                $data = array( array( (int) $service->get( 'id' ), $frame['staff_id'], $timestamp ) );
                            } else {
                                $prev_data = null;
                                $prev_timestamp = $client_timestamp + $service->get( 'duration' );
                                if ( $service->get( 'id' ) == $this->main_service_id ) {
                                    $prev_timestamp += $extras_duration;
                                }
                                $prev_group = date( 'Y-m-d', ( $prev_service->get( 'duration' ) == DAY_IN_SECONDS && ! $show_calendar )
                                    ? strtotime( 'first day of this month', $prev_timestamp )
                                    : $prev_timestamp
                                );
                                $padding = $service->get( 'padding_right' ) + $prev_service->get( 'padding_left' );
                                // Look for available slot for previous service. There are 2 possible options:
                                // 1. previous service is done by another staff, then do not take into account padding
                                // 2. previous service is done by the same staff, then count padding
                                if ( isset ( $prev_slots[ $prev_group ][ $prev_timestamp ] ) &&
                                    $prev_slots[ $prev_group ][ $prev_timestamp ]['blocked'] == false &&
                                    ( $padding == 0 || $prev_slots[ $prev_group ][ $prev_timestamp ]['data'][0][1] != $frame['staff_id'] )
                                ) {
                                    $prev_data = $prev_slots[ $prev_group ][ $prev_timestamp ]['data'];
                                } else {
                                    $prev_timestamp += $padding;
                                    $prev_group = date( 'Y-m-d', ( $prev_service->get( 'duration' ) == DAY_IN_SECONDS && ! $show_calendar )
                                        ? strtotime( 'first day of this month', $prev_timestamp )
                                        : $prev_timestamp
                                    );
                                    if ( isset ( $prev_slots[ $prev_group ][ $prev_timestamp ] ) &&
                                        $prev_slots[ $prev_group ][ $prev_timestamp ]['blocked'] == false &&
                                        $prev_slots[ $prev_group ][ $prev_timestamp ]['data'][0][1] == $frame['staff_id']
                                    ) {
                                        $prev_data = $prev_slots[ $prev_group ][ $prev_timestamp ]['data'];
                                    }
                                }
                                // If slot is found, then add its data to the current slot data.
                                if ( $prev_data !== null ) {
                                    $data = array_merge( array( array( (int) $service->get( 'id' ), $frame['staff_id'], $timestamp ) ), $prev_data );
                                }
                            }

                            if ( $data !== null ) {
                                if ( ! isset ( $this->slots[ $group ] ) ) {
                                    if ( ! $show_calendar && $show_day_per_column && $groups + 1 >= 10 ) {
                                        // Break the while-loop.
                                        break ( 3 );
                                    }
                                    $this->slots[ $group ] = array();
                                    ++ $slots;
                                    ++ $groups;
                                }
                                $this->slots[ $group ][ $client_timestamp ] = array(
                                    'data'    => $data,
                                    'blocked' => isset ( $frame['blocked'] ),
                                );
                                ++ $slots;
                            }
                        } elseif ( ! isset ( $frame['blocked'] ) ) {
                            if ( $this->slots[ $group ][ $client_timestamp ]['blocked'] ) {
                                // Set slot to available if it was marked as 'blocked' before.
                                $this->slots[ $group ][ $client_timestamp ]['data'][0][1] = $frame['staff_id'];
                                $this->slots[ $group ][ $client_timestamp ]['blocked'] = false;
                            } // Change staff member for this slot if the other staff member has higher price.
                            elseif ( $staff_data[ $this->slots[ $group ][ $client_timestamp ]['data'][0][1] ]['price'] < $staff_data[ $frame['staff_id'] ]['price'] ) {
                                $this->slots[ $group ][ $client_timestamp ]['data'][0][1] = $frame['staff_id'];
                            }
                        }
                    }
                }

                $date->add( $this->one_day );
            }

            // Detect if there are more slots.
            if ( ! $show_calendar && $date !== false ) {
                while ( $date = $this->_findNextDay( $date, $max_date ) ) {
                    $available_time = $this->_findAvailableTime( $staff_data, $service, $date );
                    if ( ! empty ( $available_time ) ) {
                        $this->has_more_slots = true;
                        break;
                    }
                    $date->add( $this->one_day );
                }
            }

            $prev_slots   = $this->slots;
            $prev_service = $service;
        }
    }

    /**
     * Determine requested timestamp and start and max date.
     *
     * @return array
     */
    private function _prepareDates()
    {
        if ( $this->last_fetched_slot ) {
            $start_date = date_create( '@' . $this->last_fetched_slot[0][2] )->setTime( 0, 0, 0 );
            $req_timestamp = $start_date->getTimestamp();
            // The last_fetched_slot is always in WP time zone (see \Bookly\Frontend\Modules\Booking\Controller::executeRenderNextTime()).
            // We increase it by 1 day to get the date to start with.
            $start_date->add( $this->one_day );
        } else {
            $start_date = new \DateTime( $this->selected_date ? $this->selected_date : $this->userData->get( 'date_from' ) );
            if ( Config::showCalendar() ) {
                // Get slots for selected month.
                $start_date->modify( 'first day of this month' );
            }
            $req_timestamp = $start_date->getTimestamp();
            if ( get_option( 'ab_settings_use_client_time_zone' ) ) {
                // The userData::date_from is in client's time zone so we need to check the previous day too
                // because some available slots can be found in the previous day due to time zone offset.
                $start_date->sub( $this->one_day );
            }
        }

        $max_date = date_create(
            '@' . ( (int) current_time( 'timestamp' ) + Config::getMaximumAvailableDaysForBooking() * DAY_IN_SECONDS )
        )->setTime( 0, 0 );
        if ( Config::showCalendar() ) {
            $next_month = clone $start_date;
            if ( get_option( 'ab_settings_use_client_time_zone' ) ) {
                // Add one day since it was subtracted hereinabove.
                $next_month->add( $this->one_day );
            }
            $next_month->modify( 'first day of next month' );
            if ( $next_month < $max_date ) {
                $max_date = $next_month;
            }
        }

        return array( $req_timestamp, $start_date, $max_date );
    }

    /**
     * Find a day which is available for booking based on
     * user requested set of days.
     *
     * @access private
     * @param \DateTime $date
     * @param \DateTime $max_date
     * @return \DateTime
     */
    private function _findNextDay( \DateTime $date, \DateTime $max_date )
    {
        $attempt = 0;
        // Find available day within requested days.
        $requested_days = $this->userData->get( 'days' );
        while ( ! in_array( intval( $date->format( 'w' ) ) + 1, $requested_days ) ) {
            $date->add( $this->one_day );
            if ( ++ $attempt >= 7 ) {
                return false;
            }
        }

        return $date >= $max_date ? false : $date;
    }

    /**
     * Find array of time slots available for booking
     * for given date.
     *
     * @access private
     * @param array $staff_data
     * @param Entities\Service $service
     * @param \DateTime $date
     * @return array
     */
    private function _findAvailableTime( array $staff_data, Entities\Service $service, \DateTime $date )
    {
        $result             = array();
        $time_slot_length   = Config::getTimeSlotLength();
        $prior_time         = Config::getMinimumTimePriorBooking();
        $current_timestamp  = (int) current_time( 'timestamp' ) + $prior_time;
        $current_date       = date_create( '@' . $current_timestamp )->setTime( 0, 0 );
        $is_all_day_service = $service->get( 'duration' ) == DAY_IN_SECONDS;
        $request_extras_duration = apply_filters( 'bookly_extras_get_total_duration', 0, $this->userData->get( 'extras' ) );

        if ( $date < $current_date ) {
            return array();
        }

        $day_of_week = intval( $date->format( 'w' ) ) + 1; // 1-7
        foreach ( $staff_data as $staff_id => $staff ) {

            if ( $staff['capacity'] < $this->userData->get( 'number_of_persons' ) ) {
                continue;
            }

            if ( isset ( $staff['working_hours'][ $day_of_week ] ) &&          // working day
                ! isset ( $staff['holidays'][ $date->format( 'Y-m-d' ) ] ) &&  // no holiday
                ! isset ( $staff['holidays'][ $date->format( 'm-d' ) ] )       // no repeating holiday
            ) {
                if ( $is_all_day_service ) {
                    // For whole day services do not check staff working hours.
                    $intersections = array( array(
                        'start' => 0,
                        'end'   => DAY_IN_SECONDS,
                    ) );
                } else {
                    // Find intersection between working and requested hours
                    //(excluding time slots in the past).
                    $working_start_time = $staff['working_hours'][ $day_of_week ]['start_time'];
                    if ( $date == $current_date ) {
                        $working_start = Utils\DateTime::timeToSeconds( $working_start_time );
                        $new_start = $working_start + ceil ( ( $current_timestamp % DAY_IN_SECONDS - $working_start ) / $time_slot_length ) * $time_slot_length;
                        if ( $new_start > $working_start ) {
                            $working_start_time = Utils\DateTime::buildTimeString( $new_start );
                        }
                    }

                    $intersections = $this->_findIntersections(
                        Utils\DateTime::timeToSeconds( $working_start_time ),
                        Utils\DateTime::timeToSeconds( $staff['working_hours'][ $day_of_week ]['end_time'] ),
                        Utils\DateTime::timeToSeconds( $this->userData->get( 'time_from' ) ),
                        Utils\DateTime::timeToSeconds( $this->userData->get( 'time_to' ) )
                    );
                }

                foreach ( $intersections as $intersection ) {
                    if ( $intersection['end'] - $intersection['start'] >= $service->get( 'duration' ) ) {
                        // Initialize time frames.
                        $frames = array( array(
                            'start'    => $intersection['start'],
                            'end'      => $intersection['end'],
                            'staff_id' => $staff_id
                        ) );
                        if ( ! $is_all_day_service ) {
                            // Remove breaks from time frames for non all day services only.
                            foreach ( $staff['working_hours'][ $day_of_week ]['breaks'] as $break ) {
                                $frames = $this->_removeTimePeriod(
                                    $frames,
                                    Utils\DateTime::timeToSeconds( $break['start'] ),
                                    Utils\DateTime::timeToSeconds( $break['end'] ),
                                    $service
                                );
                            }
                        }
                        // Remove bookings from time frames.
                        foreach ( $staff['bookings'] as $booking ) {
                            // Work with bookings for the current day only.
                            if ( $date->format( 'Y-m-d' ) == $booking['start_date'] ) {

                                $frames = $this->_removeTimePeriod(
                                    $frames,
                                    $booking['start_time'] - $booking['padding_left'],
                                    $booking['end_time'] + $booking['padding_right'],
                                    $service,
                                    $removed
                                );

                                if ( $removed ) {
                                    // Handle not full bookings (when number of bookings is less than capacity).
                                    if (
                                        $booking['from_google'] == false &&
                                        $booking['service_id'] == $this->userData->get( 'service_id' ) &&
                                        $booking['start_time'] >= $intersection['start'] &&
                                        $staff['capacity'] - $booking['number_of_bookings'] >= $this->userData->get( 'number_of_persons' )
                                    ) {
                                        $exist_extras_duration = apply_filters( 'bookly_extras_get_total_duration', 0, (array) json_decode( $booking['extras'], true ) );
                                        if ( $exist_extras_duration >= $request_extras_duration ) {
                                            // Show the first slot as available.
                                            $frames[] = array(
                                                'start'    => $booking['start_time'],
                                                'end'      => $booking['start_time'] + $time_slot_length,
                                                'staff_id' => $staff_id,
                                                'not_full' => true,
                                            );
                                        }
                                    }
                                    if ( $is_all_day_service ) {
                                        // For all day services we break the loop since there can be
                                        // just 1 booking per day for such services.
                                        break;
                                    }
                                }
                            }
                        }
                        $result = array_merge( $result, $frames );
                    }
                }
            }
        }
        usort( $result, function ( $a, $b ) { return $a['start'] - $b['start']; } );

        return $result;
    }

    /**
     * Find intersection between 2 time periods.
     *
     * @param mixed $p1_start
     * @param mixed $p1_end
     * @param mixed $p2_start
     * @param mixed $p2_end
     * @return array
     */
    private function _findIntersections( $p1_start, $p1_end, $p2_start, $p2_end )
    {
        $result = array();

        if ( $p2_start > $p2_end ) {
            $result[] = $this->_findIntersections( $p1_start, $p1_end, $p2_start, DAY_IN_SECONDS );
            $result[] = $this->_findIntersections( $p1_start, $p1_end, 0, $p2_end );
        } else {
            if ( $p1_start <= $p2_start && $p1_end > $p2_start && $p1_end <= $p2_end ) {
                $result[] = array( 'start' => $p2_start, 'end' => $p1_end );
            } elseif ( $p1_start <= $p2_start && $p1_end >= $p2_end ) {
                $result[] = array( 'start' => $p2_start, 'end' => $p2_end );
            } elseif ( $p1_start >= $p2_start && $p1_start < $p2_end && $p1_end >= $p2_end ) {
                $result[] = array( 'start' => $p1_start, 'end' => $p2_end );
            } elseif ( $p1_start >= $p2_start && $p1_end <= $p2_end ) {
                $result[] = array( 'start' => $p1_start, 'end' => $p1_end );
            }
        }

        return $result;
    }

    /**
     * Remove time period from the set of time frames.
     *
     * @param array $frames
     * @param mixed $p_start
     * @param mixed $p_end
     * @param Entities\Service $service
     * @param bool& $removed  Whether the period was removed or not
     * @return array
     */
    private function _removeTimePeriod( array $frames, $p_start, $p_end, Entities\Service $service, &$removed = false )
    {
        $show_blocked_slots = Config::showBlockedTimeSlots();
        $service_duration   = $service->get( 'duration' );
        $is_all_day_service = $service->get( 'duration' ) == DAY_IN_SECONDS;

        $result  = array();
        $removed = false;

        foreach ( $frames as $frame ) {
            $intersections = $this->_findIntersections(
                $frame['start'],
                $frame['end'],
                $p_start,
                $p_end
            );
            foreach ( $intersections as $intersection ) {
                $blocked_start = $frame['start'];
                $blocked_end   = $frame['end'];
                if ( $intersection['start'] - $frame['start'] >= $service_duration ) {
                    $result[] = array_merge( $frame, array(
                        'end' => $intersection['start'],
                    ) );
                    $blocked_start = $intersection['start'];
                }
                if ( $frame['end'] - $intersection['end'] >= $service_duration ) {
                    $result[] = array_merge( $frame, array(
                        'start' => $intersection['end'],
                    ) );
                    $blocked_end = $intersection['end'];
                }
                if ( $show_blocked_slots ) {
                    // Show removed period as 'blocked'.
                    $result[] = array_merge( $frame, array(
                        'start'   => $blocked_start,
                        'end'     => $is_all_day_service ? Config::getTimeSlotLength() : $blocked_end,
                        'blocked' => true,
                    ) );
                }
            }
            if ( empty ( $intersections ) ) {
                $result[] = $frame;
            } else {
                $removed = true;
            }
        }

        return $result;
    }

    /**
     * Prepare data for staff.
     *
     * @param Entities\Service $service
     * @param \DateTime $start_date
     * @return array
     */
    private function _prepareStaffData( Entities\Service $service, \DateTime $start_date )
    {
        $result = array();

        $staff_ids = array();
        if ( $this->service_type == Entities\Service::TYPE_COMPOUND && $service->get( 'id' ) != $this->main_service_id ) {
            $res = Entities\StaffService::query()
                ->select( 'staff_id' )
                ->where( 'service_id', $service->get( 'id' ) )
                ->fetchArray();
            foreach ( $res as $item ) {
                $staff_ids[] = $item['staff_id'];
            }
        } else {
            $staff_ids = $this->userData->get( 'staff_ids' );
        }

        // Load service price and capacity for each staff member.
        $staff_services = Entities\StaffService::query( 'ss' )
            ->select( 'ss.staff_id, ss.price, ss.capacity' )
            ->whereIn( 'ss.staff_id', $staff_ids )
            ->where( 'ss.service_id', $service->get( 'id' ) )
            ->fetchArray();
        // Create initial data structure.
        foreach ( $staff_services as $staff_service ) {
            $result[ $staff_service['staff_id'] ] = array(
                'price'         => $staff_service['price'],
                'capacity'      => $staff_service['capacity'],
                'holidays'      => array(),
                'bookings'      => array(),
                'working_hours' => array(),
            );
        }

        // Load holidays.
        $holidays = Entities\Holiday::query( 'h' )
            ->select( 'IF(h.repeat_event, DATE_FORMAT(h.date, \'%%m-%%d\'), h.date) as date, h.staff_id' )
            ->whereIn( 'h.staff_id', $staff_ids )
            ->whereRaw( 'h.repeat_event = 1 OR h.date >= %s', array( $start_date->format( 'Y-m-d H:i:s' ) ) )
            ->fetchArray();
        foreach ( $holidays as $holiday ) {
            $result[ $holiday['staff_id'] ]['holidays'][ $holiday['date'] ] = 1;
        }

        // Load working schedule.
        $working_schedule = Entities\StaffScheduleItem::query( 'ssi' )
            ->select( 'ssi.*, break.start_time AS break_start, break.end_time AS break_end' )
            ->leftJoin( 'ScheduleItemBreak', 'break', 'break.staff_schedule_item_id = ssi.id' )
            ->whereIn( 'ssi.staff_id', $staff_ids )
            ->whereNot( 'ssi.start_time', null )
            ->fetchArray();

        foreach ( $working_schedule as $item ) {
            if ( ! isset ( $result[ $item['staff_id'] ]['working_hours'][ $item['day_index'] ] ) ) {
                $result[ $item['staff_id'] ]['working_hours'][ $item['day_index'] ] = array(
                    'start_time' => $item['start_time'],
                    'end_time'   => $item['end_time'],
                    'breaks'     => array(),
                );
            }
            if ( $item['break_start'] ) {
                $result[ $item['staff_id'] ]['working_hours'][ $item['day_index'] ]['breaks'][] = array(
                    'start' => $item['break_start'],
                    'end'   => $item['break_end']
                );
            }
        }

        // Load bookings.
        $bookings = Entities\CustomerAppointment::query( 'ca' )
            ->select( '`a`.`id`,
                `a`.`staff_id`,
                `a`.`service_id`,
                `a`.`google_event_id`,
                `a`.`start_date`,
                DATE_ADD(`a`.`end_date`, INTERVAL `a`.`extras_duration` SECOND) AS `end_date`,
                `ca`.`extras`,
                COALESCE(`s`.`padding_left`,0) + ' . $service->get( 'padding_right' ) . ' AS `padding_left`,
                COALESCE(`s`.`padding_right`,0) + ' . $service->get( 'padding_left' ) . ' AS `padding_right`,
                SUM(`ca`.`number_of_persons`) AS `number_of_bookings`'
            )
            ->leftJoin( 'Appointment', 'a', '`a`.`id` = `ca`.`appointment_id`' )
            ->leftJoin( 'StaffService', 'ss', '`ss`.`staff_id` = `a`.`staff_id` AND `ss`.`service_id` = `a`.`service_id`' )
            ->leftJoin( 'Service', 's', '`s`.`id` = `a`.`service_id`' )
            ->whereNot( 'ca.status', Entities\CustomerAppointment::STATUS_CANCELLED )
            ->whereIn( 'a.staff_id', $staff_ids )
            ->whereGte( 'a.start_date', $start_date->format( 'Y-m-d' ) )
            ->groupBy( 'a.start_date' )->groupBy( 'a.staff_id' )->groupBy( 'a.service_id' )
            ->fetchArray();
        foreach ( $bookings as $booking ) {
            $booking['from_google'] = false;
            list ( $s_date, $s_time ) = explode( ' ', $booking['start_date'] );
            list ( $e_date, $e_time ) = explode( ' ', $booking['end_date'] );
            $booking['start_date'] = $s_date;
            $booking['start_time'] = Utils\DateTime::timeToSeconds( $s_time );
            unset ( $booking['end_date'] );
            $booking['end_time'] = Utils\DateTime::timeToSeconds( $e_time );
            if ( $s_date != $e_date ) {
                // Add 24 hours for bookings that end on the next day.
                $booking['end_time'] += DAY_IN_SECONDS;
            }
            $result[ $booking['staff_id'] ]['bookings'][] = $booking;
        }

        // Handle cart bookings.
        if ( get_option( 'ab_settings_step_cart_enabled' ) ) {
            $main_service_id = $this->main_service_id;
            $current_appointment = $this->userData->getCartKey();
            $this->userData->foreachCartItem( function ( UserBookingData $userData, $cart_key, $cart_item ) use ( &$result, $main_service_id, $current_appointment ) {
                if ( $current_appointment != $cart_key ) {
                    $extras_duration = apply_filters( 'bookly_extras_get_total_duration', 0, $userData->get( 'extras' ) );
                    foreach ( $userData->get( 'slots' ) as $slot ) {
                        list( $service_id, $staff_id, $timestamp ) = $slot;
                        $service = new Entities\Service();
                        $service->load( $service_id );
                        $start = date_create( '@' . $timestamp );
                        $start_date = $start->format( 'Y-m-d' );
                        $start_time = Utils\DateTime::timeToSeconds( $start->format( 'H:i' ) );
                        $end = clone $start;
                        $end->modify( '+' . ( $service->get( 'duration' ) + $extras_duration ) . ' sec' );
                        $end_time = Utils\DateTime::timeToSeconds( $end->format( 'H:i' ) );
                        $extras_duration = 0;
                        $booking_exists = false;
                        foreach ( $result[ $staff_id ]['bookings'] as &$booking ) {
                            // If such booking exists increase number_of_bookings.
                            if ( $booking['service_id'] == $service_id
                                 && $booking['start_date']  == $start_date
                                 && $booking['start_time']  == $start_time
                                 && $booking['from_google'] == false
                                 && $booking['end_time'] >= $end_time
                            ) {
                                $booking['number_of_bookings'] += $cart_item['number_of_persons'];
                                $booking_exists = true;
                                break;
                            }
                        }
                        if ( ! $booking_exists ) {
                            // Add in staff bookings array new appointments from cart, for checking capacity.

                            $result[ $staff_id ]['bookings'][] = array(
                                'staff_id'           => $staff_id,
                                'service_id'         => $service_id,
                                'start_date'         => $start_date,
                                'start_time'         => $start_time,
                                'end_time'           => $end_time,
                                'padding_left'       => $service->get( 'padding_left' ),
                                'padding_right'      => $service->get( 'padding_right' ),
                                'number_of_bookings' => $cart_item['number_of_persons'],
                                'from_google'        => false,
                                'google_event_id'    => null,
                                'extras'             => $main_service_id == $service_id ? json_encode( $cart_item['extras'] ) : '[]',
                            );
                        }
                    }
                }
            } );
        }

        // Handle Google Calendar events.
        if ( get_option( 'ab_settings_google_two_way_sync' ) ) {
            $query = Entities\Staff::query( 's' )->whereIn( 's.id', $staff_ids )->whereNot( 'google_data', null );
            foreach ( $query->find() as $staff ) {
                $google = new Google();
                if ( $google->loadByStaff( $staff ) ) {
                    $result[ $staff->get( 'id' ) ]['bookings'] = array_merge(
                        $result[ $staff->get( 'id' ) ]['bookings'],
                        $google->getCalendarEvents( $start_date, $service->get( 'padding_left' ), $service->get( 'padding_right' ) ) ?: array()
                    );
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getSlots()
    {
        return $this->slots;
    }

    /**
     * Get disabled days in Pickadate format.
     *
     * @return array
     */
    public function getDisabledDaysForPickadate()
    {
        $result = array();
        $date = new \DateTime( $this->selected_date ? $this->selected_date : $this->userData->get( 'date_from' ) );
        $date->modify( 'first day of this month' );
        $end_date = clone $date;
        $end_date->modify( 'first day of next month' );
        $Y = (int) $date->format( 'Y' );
        $n = (int) $date->format( 'n' ) - 1;
        while ( $date < $end_date ) {
            if ( ! array_key_exists( $date->format( 'Y-m-d' ), $this->slots ) ) {
                $result[] = array( $Y, $n, (int) $date->format( 'j' ) );
            }
            $date->add( $this->one_day );
        }

        return $result;
    }

    public function setLastFetchedSlot( $last_fetched_slot )
    {
        $this->last_fetched_slot = json_decode( $last_fetched_slot );
    }

    public function setSelectedDate( $selected_date )
    {
        $this->selected_date = $selected_date;
    }

    public function getSelectedDateForPickadate()
    {
        if ( $this->selected_date ) {
            foreach ( $this->slots as $group => $slots ) {
                if ( $group >= $this->selected_date ) {
                    return $group;
                }
            }

            if ( empty( $this->slots ) ) {
                return $this->selected_date;
            } else {
                reset( $this->slots );
                return key( $this->slots );
            }
        }

        if ( ! empty ( $this->slots ) ) {
            reset( $this->slots );
            return key( $this->slots );
        }

        return $this->userData->get( 'date_from' );
    }

    public function hasMoreSlots()
    {
        return $this->has_more_slots;
    }

    /**
     * Return is_all_day_service.
     *
     * @return bool
     */
    public function isAllDayService()
    {
        return $this->is_all_day_service;
    }

}