<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    /** @var Bookly\Backend\Modules\Notifications\Forms\Notifications $form */
    $administrator_phone = get_option( 'ab_sms_administrator_phone' );
    $collapse_id = 0;
    $form_data = $form->getData();
?>
<form action="<?php echo esc_url( remove_query_arg( array( 'paypal_result', 'auto-recharge', 'tab' ) ) ) ?>" method="post">
    <input type="hidden" name="form-notifications">
    <table class="bookly-notifications-settings">
        <tr>
            <td><label for="admin_phone"><?php _e( 'Administrator phone', 'bookly' ) ?></label></td>
            <td>
                <div class="input-group">
                    <input id="admin_phone" name="ab_sms_administrator_phone" class="ab-auto-w" type="text" value="<?php echo esc_attr( $administrator_phone ) ?>"/>
                    <span class="input-group-btn">
                        <button class="btn btn-info" id="send_test_sms"><?php _e( 'Send test SMS', 'bookly' ) ?></button>
                    </span>
                </div>
            </td>
            <td><?php \Bookly\Lib\Utils\Common::popover( __( 'Enter a phone number in international format. E.g. for the United States a valid phone number would be +17327572923.', 'bookly' ) ) ?></td>
        </tr>
    </table>

    <?php if ( $form->types['combined'] ) : ?>
        <!-- Nav tabs -->
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#single" aria-controls="single" role="tab" data-toggle="tab"><?php _e( 'Single', 'bookly' ) ?></a></li>
            <li role="presentation"><a href="#combined" aria-controls="combined" role="tab" data-toggle="tab"><?php _e( 'Combined', 'bookly' ) ?></a></li>
        </ul>
    <?php endif ?>

    <!-- Tab panes -->
    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="single">
            <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
            <?php foreach ( $form->types['single'] as $type ) : ?>
                <div class="panel panel-default bookly-notifications">
                    <div class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <input name="<?php echo $type ?>[active]" value="0" type="checkbox" checked="checked" class="hidden" />
                            <input id="<?php echo $type ?>_active" name="<?php echo $type ?>[active]" value="1" type="checkbox" <?php checked( $form_data[ $type ]['active'] ) ?> />
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse_<?php echo ++ $collapse_id ?>">
                                <?php echo $form_data[ $type ]['name'] ?>
                            </a>
                        </h4>
                    </div>
                    <div id="collapse_<?php echo $collapse_id ?>" class="panel-collapse collapse">
                        <div class="panel-body">

                            <?php $form->renderSendingTime( $type ) ?>
                            <?php $form->renderEditor( $type ) ?>
                            <?php $form->renderCopy( $type ) ?>

                            <div class="form-group">
                                <label><?php _e( 'Codes', 'bookly' ) ?></label>
                                <table class="bookly-codes">
                                    <tbody>
                                    <?php switch ( $type ) :
                                        case 'staff_agenda':        include '_codes_staff_agenda.php';        break;
                                        case 'client_new_wp_user':  include '_codes_client_new_wp_user.php';  break;
                                        default:                    include '_codes.php';
                                    endswitch ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endforeach ?>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="combined">
            <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                <?php foreach ( $form->types['combined'] as $type ) : ?>
                    <div class="panel panel-default bookly-notifications">
                        <div class="panel-heading" role="tab">
                            <h4 class="panel-title">
                                <input name="<?php echo $type ?>[active]" value="0" type="checkbox" checked="checked" class="hidden" />
                                <input id="<?php echo $type ?>_active" name="<?php echo $type ?>[active]" value="1" type="checkbox" <?php checked( $form_data[ $type ]['active'] ) ?> />
                                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse_<?php echo ++ $collapse_id ?>">
                                    <?php echo $form_data[ $type ]['name'] ?>
                                </a>
                            </h4>
                        </div>
                        <div id="collapse_<?php echo $collapse_id ?>" class="panel-collapse collapse">
                            <div class="panel-body">

                                <?php $form->renderSendingTime( $type ) ?>
                                <?php $form->renderEditor( $type ) ?>
                                <?php $form->renderCopy( $type ) ?>

                                <div class="form-group">
                                    <label><?php _e( 'Codes', 'bookly' ) ?></label>
                                    <table class="bookly-codes">
                                        <tbody>
                                            <?php include '_codes_cart.php' ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>

    <div style="padding-bottom: 15px">
        <?php if ( is_multisite() ) : ?>
            <i><?php printf( __( 'To send scheduled notifications please refer to <a href="%1$s">Bookly Multisite</a> add-on <a href="%2$s">message</a>.', 'bookly' ), 'http://codecanyon.net/item/bookly-multisite-addon/13903524?ref=ladela', network_admin_url( 'admin.php?page=bookly-multisite-network' ) ) ?></i><br />
        <?php else : ?>
            <i><?php _e( 'To send scheduled notifications please execute the following script hourly with your cron:', 'bookly' ) ?></i><br />
            <b>php -f <?php echo $cron_path ?></b>
        <?php endif ?>
    </div>
    <?php \Bookly\Lib\Utils\Common::submitButton( 'js-submit-notifications' ) ?>
    <?php \Bookly\Lib\Utils\Common::resetButton() ?>
</form>