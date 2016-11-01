<?php
namespace Bookly\Backend\Modules\Staff\Forms;

use Bookly\Lib;

/**
 * Class StaffSchedule
 * @package Bookly\Backend\Modules\Staff\Forms
 */
class StaffSchedule extends Lib\Form
{
    protected static $entity_class = 'StaffScheduleItem';

    public function configure()
    {
        $this->setFields( array( 'days', 'staff_id', 'start_time', 'end_time' ) );
    }

    public function save()
    {
        if ( isset( $this->data['days'] ) ) {
            foreach ( $this->data['days'] as $id => $day_index ) {
                $staffScheduleItem = new Lib\Entities\StaffScheduleItem();
                $staffScheduleItem->load( $id );
                $staffScheduleItem->set( 'day_index', $day_index );
                if ( $this->data['start_time'][ $day_index ] ) {
                    $staffScheduleItem->set( 'start_time', $this->data['start_time'][ $day_index ] );
                    $staffScheduleItem->set( 'end_time', $this->data['end_time'][ $day_index ] );
                } else {
                    $staffScheduleItem->set( 'start_time', null );
                    $staffScheduleItem->set( 'end_time', null );
                }
                $staffScheduleItem->save();
            }
        }
    }

}