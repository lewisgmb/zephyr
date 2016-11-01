<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<form method="post" action="<?php echo esc_url( add_query_arg( 'tab', 'cart' ) ) ?>"  class="ab-settings-form">
    <div class="form-group">
        <label for="ab_settings_step_cart_enabled"><?php _e( 'Cart', 'bookly' ) ?></label>
        <p class="help-block"><?php _e( 'If cart is enabled then your clients will be able to book several appointments at once. Please note that WooCommerce integration must be disabled.', 'bookly' ) ?></p>
        <?php \Bookly\Lib\Utils\Common::optionToggle( 'ab_settings_step_cart_enabled' ) ?>
    </div>
    <div class="form-group">
        <label for="ab_cart_show_columns"><?php _e( 'Columns', 'bookly' ) ?></label><br/>
        <div class="ab-flags" id="ab_cart_show_columns">
        <?php foreach ( (array) get_option( 'ab_cart_show_columns' ) as $column => $attr ) : ?>
            <div class="checkbox">
                <i class="ab-inner-handle glyphicon glyphicon-align-justify" title="<?php esc_attr_e( 'Reorder', 'bookly' ) ?>"></i>
                <input type="hidden" name="ab_cart_show_columns[<?php echo $column ?>][show]" value="0" />
                <label><input type="checkbox" name="ab_cart_show_columns[<?php echo $column ?>][show]" value="1" <?php checked( $attr['show'], true ) ?> /><?php echo $cart_columns[ $column ] ?></label>
            </div>
        <?php endforeach ?>
        </div>
    </div>
    <div class="form-group">
        <label for="ab_settings_cart_notifications_combined"><?php _e( 'Combined notifications', 'bookly' ) ?></label>
        <p class="help-block"><?php _e( 'If combined notifications are enabled then your clients will receive single notification for whole order instead of separate notification per each cart item. You will need to edit corresponding templates in Email and SMS Notifications.', 'bookly' ) ?></p>
        <?php \Bookly\Lib\Utils\Common::optionToggle( 'ab_settings_cart_notifications_combined' ) ?>
    </div>
    <?php \Bookly\Lib\Utils\Common::submitButton() ?>
    <?php \Bookly\Lib\Utils\Common::resetButton() ?>
</form>