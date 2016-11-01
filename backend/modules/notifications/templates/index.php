<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    $ab_settings_sender_name  = get_option( 'ab_settings_sender_name' ) == '' ?
        get_option( 'blogname' )    : get_option( 'ab_settings_sender_name' );
    $ab_settings_sender_email = get_option( 'ab_settings_sender_email' ) == '' ?
        get_option( 'admin_email' ) : get_option( 'ab_settings_sender_email' );
    $collapse_id = 0;
    $form_data = $form->getData();
?>
<form method="post">
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><?php _e( 'Email Notifications', 'bookly' ) ?></h3>
    </div>
    <div class="panel-body">
        <?php \Bookly\Lib\Utils\Common::notice( $message ) ?>
        <table class="bookly-notifications-settings">
            <tr>
                <td><label for="sender_name"><?php _e( 'Sender name', 'bookly' ) ?></label></td>
                <td><input id="sender_name" name="ab_settings_sender_name" class="form-control ab-inline-block ab-auto-w ab-sender" type="text" value="<?php echo esc_attr( $ab_settings_sender_name ) ?>"/></td>
                <td></td>
            </tr>
            <tr>
                <td><label for="sender_email"><?php _e( 'Sender email', 'bookly' ) ?></label></td>
                <td><input id="sender_email" name="ab_settings_sender_email" class="form-control ab-inline-block ab-auto-w ab-sender" type="text" value="<?php echo esc_attr( $ab_settings_sender_email ) ?>"/></td>
                <td></td>
            </tr>
            <tr>
                <td><label for="ab_email_notification_reply_to_customers"><?php _e( 'Reply directly to customers', 'bookly' ) ?></label></td>
                <td><?php \Bookly\Lib\Utils\Common::optionToggle( 'ab_email_notification_reply_to_customers' ) ?></td>
                <td><?php \Bookly\Lib\Utils\Common::popover( __( 'If this option is enabled then the email address of the customer is used as a sender email address for notifications sent to staff members and administrators.', 'bookly' ) ) ?></td>
            </tr>
            <tr>
                <td><label for="ab_email_content_type"><?php _e( 'Send emails as', 'bookly' ) ?></label></td>
                <td><?php \Bookly\Lib\Utils\Common::optionToggle( 'ab_email_content_type', array( 't' => array( 'html', __( 'HTML',  'bookly' ) ), 'f' => array( 'plain', __( 'Text', 'bookly' ) ) ) ) ?></td>
                <td><?php \Bookly\Lib\Utils\Common::popover( __( 'HTML allows formatting, colors, fonts, positioning, etc. With Text you must use Text mode of rich-text editors below. On some servers only text emails are sent successfully.', 'bookly' ) ) ?></td>
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
                                    <?php $form->renderSubject( $type ) ?>
                                    <?php $form->renderEditor( $type ) ?>
                                    <?php $form->renderCopy( $type ) ?>

                                    <div class="form-group">
                                        <label><?php _e( 'Codes', 'bookly' ) ?></label>
                                        <table class="bookly-codes">
                                            <tbody>
                                            <?php switch ( $type ) :
                                                case 'staff_agenda':       include '_codes_staff_agenda.php';       break;
                                                case 'client_new_wp_user': include '_codes_client_new_wp_user.php'; break;
                                                default:                   include '_codes.php';
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

                                    <?php $form->renderSubject( $type ) ?>
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

        <div>
            <?php if ( is_multisite() ) : ?>
                <i><?php printf( __( 'To send scheduled notifications please refer to <a href="%1$s">Bookly Multisite</a> add-on <a href="%2$s">message</a>.', 'bookly' ), 'http://codecanyon.net/item/bookly-multisite-addon/13903524?ref=ladela', network_admin_url( 'admin.php?page=bookly-multisite-network' ) ) ?></i><br />
            <?php else : ?>
                <i><?php _e( 'To send scheduled notifications please execute the following script hourly with your cron:', 'bookly' ) ?></i><br />
                <b>php -f <?php echo $cron_path ?></b>
            <?php endif ?>
        </div>
    </div>
    <div class="panel-footer">
        <?php \Bookly\Lib\Utils\Common::submitButton() ?>
        <?php \Bookly\Lib\Utils\Common::resetButton() ?>
    </div>
</div>
</form>