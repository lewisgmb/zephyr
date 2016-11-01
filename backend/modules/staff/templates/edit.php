<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    /** @var \Bookly\Lib\Entities\Staff $staff */
?>
<div id="ab-edit-staff">
    <?php \Bookly\Lib\Utils\Common::notice( __( 'Settings saved.', 'bookly' ), 'notice-success', isset ( $updated ) ) ?>
    <?php \Bookly\Lib\Utils\Common::notice( $errors, 'notice-error' ) ?>

    <div class="ab-nav-head" style="">
        <h2 class="pull-left"><?php echo $staff->get( 'full_name' ) ?></h2>
        <?php if ( \Bookly\Lib\Utils\Common::isCurrentUserAdmin() ) : ?>
        <a class="btn btn-info" id="ab-staff-delete"><?php _e( 'Delete this staff member', 'bookly' ) ?></a>
        <?php endif ?>
    </div>
    <div class="tabbable">
        <ul class="nav nav-tabs ab-nav-tabs">
            <li class="active"><a id="ab-staff-details-tab" href="#details" data-toggle="tab"><?php _e( 'Details', 'bookly' ) ?></a></li>
            <li><a id="ab-staff-services-tab" href="#services" data-toggle="tab"><?php _e( 'Services', 'bookly' ) ?></a></li>
            <li><a id="ab-staff-schedule-tab" href="#schedule" data-toggle="tab"><?php _e( 'Schedule', 'bookly' ) ?></a></li>
            <li><a id="ab-staff-holidays-tab" href="#daysoff" data-toggle="tab"><?php _e( 'Days off', 'bookly' ) ?></a></li>
        </ul>
        <div class="tab-content">
            <div style="display: none;" class="loading-indicator">
                <span class="ab-loader"></span>
            </div>
            <div class="tab-pane active" id="details">
                <div id="ab-staff-details-container" class="ab-staff-tab-content">
                    <form class="ab-staff-form form-horizontal" action="" name="ab_staff" method="POST" enctype="multipart/form-data">
                        <?php if ( \Bookly\Lib\Utils\Common::isCurrentUserAdmin() ) : ?>
                            <div class="form-group">
                                <div class="col-sm-11 col-xs-10">
                                    <label for="ab-staff-wpuser"><?php _e( 'User', 'bookly' ) ?></label>
                                    <select class="form-control" name="wp_user_id" id="ab-staff-wpuser">
                                        <option value=""><?php _e( 'Select from WP users', 'bookly' ) ?></option>
                                        <?php foreach ( $users_for_staff as $user ) : ?>
                                            <option value="<?php echo $user->ID ?>" data-email="<?php echo $user->user_email ?>" <?php selected( $user->ID, $staff->get( 'wp_user_id' ) ) ?>><?php echo $user->display_name ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="col-sm-1 col-xs-2">
                                    <img
                                        src="<?php echo plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ?>"
                                        alt=""
                                        style="margin: 28px 0 0 -20px;"
                                        class="ab-popover-ext"
                                        data-ext_id="ab-staff-popover-ext"
                                        />
                                </div>
                            </div>
                        <?php endif ?>
                        <div class="form-group">
                            <div class="col-xs-11">
                                <label class="control-label" for="ab-staff-avatar"><?php _e( 'Photo', 'bookly' ) ?></label>
                                <div id="ab-staff-avatar-image">
                                    <?php if ( $staff->get( 'avatar_url' ) ) : ?>
                                        <img src="<?php echo $staff->get( 'avatar_url' ) ?>" alt="<?php _e( 'Avatar', 'bookly' ) ?>"/>
                                        <a id="ab-delete-avatar" href="javascript:void(0)"><?php _e( 'Delete current photo', 'bookly' ) ?></a>
                                    <?php endif ?>
                                </div>
                                <input id="ab-staff-avatar" name="avatar" type="file"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-11 col-xs-10">
                                <label for="ab-staff-full-name"><?php _e( 'Full name', 'bookly' ) ?> </label>
                                <input class="form-control" id="ab-staff-full-name" name="full_name" value="<?php echo esc_attr( $staff->get( 'full_name' ) ) ?>" type="text"/>
                            </div>
                            <div class="col-sm-1 col-xs-2">
                                <span style="position: relative;top: 28px;left: -20px;" class="ab-red"> *</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-11">
                                <label for="ab-staff-email"><?php _e( 'Email', 'bookly' ) ?>  v <?php echo $staff->get( 'smoker' ) ?></label>
                                <input class="form-control" id="ab-staff-email" name="email" value="<?php echo esc_attr( $staff->get( 'email' ) ) ?>" type="text"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-11">
                                <label for="ab-staff-phone"><?php _e( 'Phone', 'bookly' ) ?></label>
                                <div class="ab-clear"></div>
                                <input class="form-control" id="ab-staff-phone" name="phone" value="<?php echo esc_attr( $staff->get( 'phone' ) ) ?>" type="text" />
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-11">
                                <label for="ab-staff-mobile">Cellulaire</label>
                                <div class="ab-clear"></div>
                                <input class="form-control" id="ab-staff-phone" name="mobile" value="<?php echo esc_attr( $staff->get( 'mobile' ) ) ?>" type="text" />
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-11">
                                <label for="ab-staff-gender">Genre</label>
                                <select name="female" class="form-control" id="ab-staff-gender">
                                      <option value="empty" <?php selected( $staff->get( 'female' ), 'empty' ) ?>>Non sp&eacute;cifi&eacute;</option>
                                    <option value="male" <?php selected( $staff->get( 'female' ), 'male' ) ?>>Homme</option>
                                    <option value="female" <?php selected( $staff->get( 'female' ), 'female' ) ?>>Femme</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-xs-11">
                                <h3>R&eacute;gions d&eacute;servies</h3>
                                <label for="ab-staff-region">Beauharnois-Salaberry</label>
                                <select name="region1" class="form-control" id="ab-staff-region1">
                                <option value="empty" <?php selected( $staff->get( 'region1' ), 'empty' ) ?>>Non sp&eacute;cifi&eacute;</option>
                                <option value="yes" <?php selected( $staff->get( 'region1' ), 'yes' ) ?>>Oui</option>
                                <option value="no" <?php selected( $staff->get( 'region1' ), 'no' ) ?>>Non</option>
                                </select>
                                <br>
                                <label for="ab-staff-region">Vaudreuil-Soulange</label>
                                <select name="region2" class="form-control" id="ab-staff-region2">
                                <option value="empty" <?php selected( $staff->get( 'region2' ), 'empty' ) ?>>Non sp&eacute;cifi&eacute;</option>
                                <option value="yes" <?php selected( $staff->get( 'region2' ), 'yes' ) ?>>Oui</option>
                                <option value="no" <?php selected( $staff->get( 'region2' ), 'no' ) ?>>Non</option>
                                </select>
                                <br>
                                <label for="ab-staff-region">Haut-Saint-Laurent</label>
                                <select name="region3" class="form-control" id="ab-staff-region2">
                                <option value="empty" <?php selected( $staff->get( 'region3' ), 'empty' ) ?>>Non sp&eacute;cifi&eacute;</option>
                                <option value="yes" <?php selected( $staff->get( 'region3' ), 'yes' ) ?>>Oui</option>
                                <option value="no" <?php selected( $staff->get( 'region3' ), 'no' ) ?>>Non</option>
                                </select>
                                <br>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-xs-11">
                                <label for="ab-staff-smoker">Question relative au tabagisme</label>
                                <select name="smoker" class="form-control" id="ab-staff-smoker">
                                      <option value="empty" <?php selected( $staff->get( 'smoker' ), 'empty' ) ?>>Non sp&eacute;cifi&eacute;</option>
                                    <option value="smoker" <?php selected( $staff->get( 'smoker' ), 'smoker' ) ?>>Fumeur</option>
                                    <option value="nonsmoker" <?php selected( $staff->get( 'smoker' ), 'nonsmoker' ) ?>>Non-fumeur</option>
                                </select>
                                <br>
                                <select name="smokerEnv" class="form-control" id="ab-staff-smokerEnv">
                                      <option value="empty" <?php selected( $staff->get( 'smokerEnv' ), 'empty' ) ?>>Non sp&eacute;cifi&eacute;</option>
                                    <option value="yes" <?php selected( $staff->get( 'smokerEnv' ), 'yes' ) ?>>Tol&egrave;re les milieux fumeurs</option>
                                    <option value="no" <?php selected( $staff->get( 'smokerEnv' ), 'no' ) ?>>Ne tol&egrave;re pas les milieux fumeurs</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-xs-11">
                                <h3>Certifications</h3>
                                <label for="ab-staff-smoker">R&eacute;animation cardiorespiratoire (RCR)</label>
                                <select name="rcrcert" class="form-control" id="ab-staff-rcrcert">
                                      <option value="empty" <?php selected( $staff->get( 'rcrcert' ), 'empty' ) ?>>Non sp&eacute;cifi&eacute;</option>
                                    <option value="yes" <?php selected( $staff->get( 'rcrcert' ), 'yes' ) ?>>Oui</option>
                                    <option value="no" <?php selected( $staff->get( 'rcrcert' ), 'no' ) ?>>Non</option>
                                </select>
                                <br>
                                  <label for="ab-staff-pdsbcert">Principe de d&eacute;placement s&eacute;curitaire des b&eacute;n&eacute;ficiaires (PDSB)</label>
                                <select name="pdsbcert" class="form-control" id="ab-staff-pdsbcert">
                                      <option value="empty" <?php selected( $staff->get( 'pdsbcert' ), 'empty' ) ?>>Non sp&eacute;cifi&eacute;</option>
                                    <option value="yes" <?php selected( $staff->get( 'pdsbcert' ), 'yes' ) ?>>Oui</option>
                                    <option value="no" <?php selected( $staff->get( 'pdsbcert' ), 'no' ) ?>>Non</option>
                                </select>
                                <br>
                                  <label for="ab-staff-aslcert">Language des signes</label>
                                <select name="aslcert" class="form-control" id="ab-staff-aslcert">
                                      <option value="empty" <?php selected( $staff->get( 'aslcert' ), 'empty' ) ?>>Non sp&eacute;cifi&eacute;</option>
                                    <option value="yes" <?php selected( $staff->get( 'aslcert' ), 'yes' ) ?>>Oui</option>
                                    <option value="no" <?php selected( $staff->get( 'aslcert' ), 'no' ) ?>>Non</option>
                                </select>
                                <br>
                                <label for="ab-staff-aslcert">Attestation de v&eacute;rification de casier judiciaire</label>
                                <select name="filecert" class="form-control" id="ab-staff-filecert">
                                    <option value="empty" <?php selected( $staff->get( 'filecert' ), 'empty' ) ?>>Non sp&eacute;cifi&eacute;</option>
                                    <option value="yes" <?php selected( $staff->get( 'filecert' ), 'yes' ) ?>>Oui</option>
                                    <option value="no" <?php selected( $staff->get( 'filecert' ), 'no' ) ?>>Non</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-xs-11">
                                <label for="ab-staff-info"><?php _e( 'Info', 'bookly' ) ?></label>
                                <div class="ab-clear"></div>
                                <textarea class="form-control" id="ab-staff-info" name="info" rows="3" type="text"><?php echo esc_textarea( $staff->get( 'info' ) ) ?></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-11 col-xs-10">
                                <label for="ab-staff-visibility"><?php _e( 'Visibility', 'bookly' ) ?></label>
                                <select name="visibility" class="form-control" id="ab-staff-visibility">
                                    <option value="public" <?php selected( $staff->get( 'visibility' ), 'public' ) ?>><?php _e( 'Public', 'bookly' ) ?></option>
                                    <option value="private" <?php selected( $staff->get( 'visibility' ), 'private' ) ?>><?php _e( 'Private', 'bookly' ) ?></option>
                                </select>
                            </div>
                            <div class="col-sm-1 col-xs-2">
                                <?php \Bookly\Lib\Utils\Common::popover( __( 'To make staff member invisible to your customers set the visibility to "Private".', 'bookly' ), 'margin: 28px 0 0 -20px;' ) ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-11">
                                <h4 class="pull-left"><?php _e( 'Google Calendar integration', 'bookly' ) ?></h4>
                                <div style="margin: 5px;display: inline-block;">
                                    <?php \Bookly\Lib\Utils\Common::popover( __( 'Synchronize the data of the staff member bookings with Google Calendar.', 'bookly' ) ) ?>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-11">
                                <label>
                                    <?php if ( isset( $authUrl ) ) : ?>
                                        <?php if ( $authUrl ) : ?>
                                            <a href="<?php echo $authUrl ?>"><?php _e( 'Connect', 'bookly' ) ?></a>
                                        <?php else : ?>
                                            <?php printf( __( 'Please configure Google Calendar <a href="%s">settings</a> first', 'bookly' ), \Bookly\Lib\Utils\Common::escAdminUrl( \Bookly\Backend\Modules\Settings\Controller::page_slug, array( 'tab' => 'google_calendar' ) ) ) ?>
                                        <?php endif ?>
                                    <?php else : ?>
                                        <?php _e( 'Connected', 'bookly' ) ?> (<a href="<?php echo \Bookly\Lib\Utils\Common::escAdminUrl( \Bookly\Backend\Modules\Staff\Controller::page_slug, array( 'google_logout' => $staff->get( 'id' ) ) ) ?>" ><?php _e( 'disconnect', 'bookly' ) ?></a>)
                                    <?php endif ?>
                                </label>
                            </div>
                        </div>
                        <?php if ( ! isset( $authUrl ) ) : ?>
                            <div class="form-group">
                                <div class="col-sm-11 col-xs-10">
                                    <label for="ab-calendar-id"><?php _e( 'Calendar', 'bookly' ) ?></label>
                                    <select class="form-control" name="google_calendar_id" id="ab-calendar-id">
                                        <?php foreach ( $calendar_list as $id => $calendar ) : ?>
                                            <option <?php selected( $staff->get( 'google_calendar_id' ) == $id || $staff->get( 'google_calendar_id' ) == '' && $calendar['primary'] ) ?> value="<?php echo esc_attr( $id ) ?>"><?php echo esc_html( $calendar['summary'] ) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                            </div>
                        <?php endif ?>
                        <input type="hidden" name="id" value="<?php echo $staff->get( 'id' ) ?>"/>
                        <input type="hidden" name="staff" value="update"/>
                        <div class="form-group">
                            <div class="col-xs-11">
                                <?php \Bookly\Lib\Utils\Common::submitButton() ?>
                                <?php \Bookly\Lib\Utils\Common::resetButton() ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="tab-pane" id="services">
                <div id="ab-staff-services-container" class="ab-staff-tab-content" style="display: none"></div>
            </div>
            <div class="tab-pane" id="schedule">
                <div id="ab-staff-schedule-container" class="ab-staff-tab-content" style="display: none"></div>
            </div>
            <div class="tab-pane" id="daysoff">
                <div id="ab-staff-holidays-container" class="ab-staff-tab-content" style="display: none"></div>
            </div>
        </div>
    </div>
</div>
