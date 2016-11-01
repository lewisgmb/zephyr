<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div id="ab_print_appointments_dialog" class="modal fade" tabindex=-1 role="dialog" aria-labelledby="printAppointmentsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php _e( 'Print', 'bookly' ) ?></h4>
            </div>
            <div class="modal-body">
                <div class="checkbox"><label><input checked value="<?php _e( 'No.', 'bookly' ) ?>" name="app_print[id]" type="checkbox"/><?php _e( 'No.', 'bookly' ) ?></label></div>
                <div class="checkbox"><label><input checked value="<?php _e( 'Appointment Date', 'bookly' ) ?>" name="app_print[start_date_f]" type="checkbox"/><?php _e( 'Appointment Date', 'bookly' ) ?></label></div>
                <div class="checkbox"><label><input checked value="<?php echo esc_attr( \Bookly\Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_label_employee' ) ) ?>" name="app_print[staff_name]" type="checkbox"/><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_label_employee' ) ?></label></div>
                <div class="checkbox"><label><input checked value="<?php _e( 'Customer', 'bookly' ) ?>" name="app_print[customer_name]" type="checkbox"/><?php _e( 'Customer', 'bookly' ) ?></label></div>
                <div class="checkbox"><label><input checked value="<?php echo esc_attr( \Bookly\Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_label_service' ) ) ?>" name="app_print[service_title]" type="checkbox"/><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_label_service' ) ?></label></div>
                <div class="checkbox"><label><input checked value="<?php _e( 'Duration', 'bookly' ) ?>" name="app_print[service_duration]" type="checkbox"/><?php _e( 'Duration', 'bookly' ) ?></label></div>
                <div class="checkbox"><label><input checked value="<?php _e( 'Status', 'bookly' ) ?>" name="app_print[status]" type="checkbox"/><?php _e( 'Status', 'bookly' ) ?></label></div>
                <div class="checkbox"><label><input checked value="<?php _e( 'Payment', 'bookly' ) ?>" name="app_print[payment_title]" type="checkbox"/><?php _e( 'Payment', 'bookly' ) ?></label></div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-info ab-popup-save print-appointments"><?php _e( 'Print', 'bookly' ) ?></button>
                <button class="ab-reset-form" data-dismiss="modal" aria-hidden="true"><?php _e( 'Cancel', 'bookly' ) ?></button>
            </div>
        </div>
    </div>
</div>