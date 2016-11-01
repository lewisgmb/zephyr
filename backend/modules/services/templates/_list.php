<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    $time_interval = get_option( 'ab_settings_time_slot_length' );
?>
<?php \Bookly\Lib\Utils\Common::notice( __( 'Settings saved.', 'bookly' ), 'notice-success', false ) ?>
<?php if ( ! empty( $service_collection ) ) : ?>
<div class="panel-group" id="services_list" role="tablist" aria-multiselectable="true">
<?php foreach ( $service_collection as $service ) : ?>
    <?php $service_id   = $service['id'];
    $assigned_staff_ids = $service['staff_ids'] ? explode( ',', $service['staff_ids'] ) : array();
    $all_staff_selected = count( $assigned_staff_ids ) == count( $staff_collection );
    ?>
<div class="panel panel-default service" data-service_id="<?php echo $service_id ?>">
    <div class="panel-heading" role="tab" id="s_<?php echo $service_id ?>">
        <h4 class="panel-title">
            <span class="ab-handle ab-move" title="<?php esc_attr_e( 'Reorder', 'bookly' ) ?>"><i class="ab-inner-handle glyphicon glyphicon-align-justify"></i></span>
            <span class="badge" style="background-color: <?php echo esc_attr( $service['color'] ) ?>">&nbsp;</span>
            <a role="button" class="collapsed" data-toggle="collapse" data-parent="#services_list" href="#service_<?php echo $service_id ?>" aria-expanded="false" aria-controls="service_<?php echo $service_id ?>"><?php echo esc_html( $service['title'] ) ?></a>
            <div class="pull-right"><div class="ab-right-nav ab-inline-block"><small><?php echo ( $service['type'] == \Bookly\Lib\Entities\Service::TYPE_SIMPLE ? \Bookly\Lib\Utils\DateTime::secondsToInterval( $service['duration'] ) : sprintf( _n( '%d service', '%d services', count( json_decode( $service['sub_services'], true ) ), 'bookly' ), count( json_decode( $service['sub_services'], true ) ) ) ) ?>
            <div class="ab-inline-block" style="width: 75px;text-align: right"><?php echo \Bookly\Lib\Utils\Common::formatPrice( $service['price'] ) ?></div></small></div><input style="margin: 0 0 0 5px" type="checkbox" class="service-checker"></div>
        </h4>
    </div>
    <div id="service_<?php echo $service_id ?>" class="panel-collapse collapse" role="tabpanel" aria-labelledby="s_<?php echo $service_id ?>" style="height: 0">
        <div class="panel-body">
        <form method="post">
            <?php do_action( 'bookly_render_service_form_contents', $service ) ?>
            <div class="form-group">
                <label for="title_<?php echo $service_id ?>"><?php _e( 'Title', 'bookly' ) ?></label>
                <div class="row">
                    <div class="col-sm-11 col-xs-10"><input id="title_<?php echo $service_id ?>" class="form-control" type="text" name="title" value="<?php echo esc_attr( $service['title'] ) ?>"></div>
                    <div class="col-sm-1 col-xs-2"><div class="service-color-wrapper"><input type="hidden" class="service-color" name="color" value="<?php echo esc_attr( $service['color'] ) ?>" data-last_color=''></div></div>
                </div>
            </div>
            <div class="form-group ab--for-simple">
                <div class="row">
                    <div class="col-sm-5 col-xs-10 ab-col-responsive">
                        <label for="duration_<?php echo $service_id ?>"><?php _e( 'Duration', 'bookly' ) ?></label>
                        <select id="duration_<?php echo $service_id ?>" class="form-control" name="duration">
                            <?php for ( $j = $time_interval; $j <= 720; $j += $time_interval ) : ?><?php if ( $service['duration'] / 60 > $j - $time_interval && $service['duration'] / 60 < $j ) : ?><option value="<?php echo esc_attr( $service['duration'] ) ?>" selected><?php echo \Bookly\Lib\Utils\DateTime::secondsToInterval( $service['duration'] ) ?></option><?php endif ?><option value="<?php echo $j * 60 ?>" <?php selected( $service['duration'], $j * 60 ) ?>><?php echo \Bookly\Lib\Utils\DateTime::secondsToInterval( $j * 60 ) ?></option><?php endfor ?>
                            <option value="86400" <?php selected( $service['duration'], DAY_IN_SECONDS ) ?>><?php _e( 'All day', 'bookly' ) ?></option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-xs-10 ab-padding-before-after">
                        <label for="padding_left_<?php echo $service_id ?>"><?php _e( 'Padding time (before and after)', 'bookly' ) ?></label>
                        <div class="ab-clear"></div>
                        <select id="padding_left_<?php echo $service_id ?>" class="form-control ab-auto-w pull-left" name="padding_left">
                            <option value="0"><?php _e( 'OFF', 'bookly' ) ?></option>
                            <?php for ( $j = $time_interval; $j <= 720; $j += $time_interval ) : ?><?php if ( $service['padding_left'] > 0 && $service['padding_left'] / 60 > $j - $time_interval && $service['padding_left'] / 60 < $j ) : ?><option value="<?php echo esc_attr( $service['padding_left'] ) ?>" selected><?php echo \Bookly\Lib\Utils\DateTime::secondsToInterval( $service['padding_left'] ) ?></option><?php endif ?><option value="<?php echo $j * 60 ?>" <?php selected( $service['padding_left'], $j * 60 ) ?>><?php echo \Bookly\Lib\Utils\DateTime::secondsToInterval( $j * 60 ) ?></option><?php endfor ?>
                        </select>
                        <select id="padding_right_<?php echo $service_id ?>" class="form-control ab-auto-w pull-right" name="padding_right">
                            <option value="0"><?php _e( 'OFF', 'bookly' ) ?></option>
                            <?php for ( $j = $time_interval; $j <= 720; $j += $time_interval ) : ?><?php if ( $service['padding_right'] > 0 && $service['padding_right'] / 60 > $j - $time_interval && $service['padding_right'] / 60 < $j ) : ?><option value="<?php echo esc_attr( $service['padding_right'] ) ?>" selected><?php echo \Bookly\Lib\Utils\DateTime::secondsToInterval( $service['padding_right'] ) ?></option><?php endif ?><option value="<?php echo $j * 60 ?>" <?php selected( $service['padding_right'], $j * 60 ) ?>><?php echo \Bookly\Lib\Utils\DateTime::secondsToInterval( $j * 60 ) ?></option><?php endfor ?>
                        </select>
                    </div>
                    <div class="col-sm-1 col-xs-2"><img src="<?php echo plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ?>" alt="" style="margin: 28px 0 0 -20px" class="ab-popover-ext" data-ext_id="ab-padding-popover-ext"></div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-xs-5">
                        <label for="price_<?php echo $service_id ?>"><?php _e( 'Price', 'bookly' ) ?></label>
                        <input id="price_<?php echo $service_id ?>" class="form-control ab-question" type="number" min="0.00" step="any" name="price" value="<?php echo esc_attr( $service['price'] ) ?>">
                    </div>
                    <div class="col-sm-3 col-xs-5 ab--for-simple">
                        <label for="capacity_<?php echo $service_id ?>"><?php _e( 'Capacity', 'bookly' ) ?></label>
                        <input id="capacity_<?php echo $service_id ?>" class="form-control ab-question" type="number" min="1" step="1" name="capacity" value="<?php echo esc_attr( $service['capacity'] ) ?>">
                    </div>
                    <div class="col-xs-1 ab--for-simple"><img src="<?php echo plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ?>" alt="" style="margin: 28px 0 0 -20px" class="ab-popover-ext" data-ext_id="ab-allowed-popover-ext"></div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-xs-5">
                        <label for="category_<?php echo $service_id ?>"><?php _e( 'Category', 'bookly' ) ?></label>
                        <select id="category_<?php echo $service_id ?>" class="form-control" name="category_id"><option value="0"></option>
                        <?php foreach ( $category_collection as $category ) : ?>
                            <option value="<?php echo $category['id'] ?>" <?php selected( $category['id'], $service['category_id'] ) ?>><?php echo esc_html( $category['name'] ) ?></option>
                        <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-xs-7 ab--for-simple">
                        <label><?php _e( 'Providers', 'bookly' ) ?></label>
                        <div class="ab-clear"></div>
                        <div class="btn-group">
                            <button class="btn btn-info" data-toggle="dropdown"><i class="glyphicon glyphicon-user"></i> <span class=staff-count><?php echo $service['total_staff'] ?></span></button>
                            <button class="btn btn-info dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
                            <ul class="dropdown-menu staff-list">
                                <li><a href="javascript:void(0)"><input style="margin-right: 5px" type="checkbox" id="service_<?php echo $service_id ?>_all_staff" class="all-staff" <?php checked( $all_staff_selected ) ?>"><label class="ab-inline" for="service_<?php echo $service_id ?>_all_staff"><?php _e( 'All staff', 'bookly' ) ?></label></a></li>
                                <?php foreach ( $staff_collection as $i => $staff ) : ?>
                                    <li><a href="javascript:void(0)" style="padding-left: 30px"><input style="margin-right: 5px" type="checkbox" name="staff_ids[]" class="staff" value="<?php echo $staff['id'] ?>" <?php checked( in_array( $staff['id'], $assigned_staff_ids ) ) ?> data-staff_name="<?php echo esc_attr( $staff['full_name'] ) ?>"><label class="ab-inline" for="service_<?php echo $service_id . '_staff_' . $i ?>"><?php echo esc_html( $staff['full_name'] ) ?></label></a></li>
                                <?php endforeach ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-md-12">
                        <label for="info_<?php echo $service_id ?>"><?php _e( 'Info', 'bookly' ) ?></label>
                        <div class="ab-clear"></div>
                        <textarea class="form-control" id="info_<?php echo $service_id ?>" name="info" rows="3" type="text"><?php echo esc_textarea( $service['info'] ) ?></textarea>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-sm-11 col-xs-10">
                        <label for="visibility_<?php echo $service_id ?>"><?php _e( 'Visibility', 'bookly' ) ?></label>
                        <select name="visibility" class="form-control" id="visibility_<?php echo $service_id ?>">
                            <option value="public" <?php selected( $service['visibility'], 'public' ) ?>><?php _e( 'Public', 'bookly' ) ?></option>
                            <option value="private" <?php selected( $service['visibility'], 'private' ) ?>><?php _e( 'Private', 'bookly' ) ?></option>
                        </select>
                    </div>
                    <div class="col-sm-1 col-xs-2">
                        <img src="<?php echo plugins_url( 'backend/resources/images/help.png', AB_PATH . '/main.php' ) ?>" alt="" style="margin: 28px 0 0 -20px" class="ab-popover-ext" data-ext_id="ab-visibility-popover-ext">
                    </div>
                </div>
            </div>
            <?php do_action( 'bookly_compound_render_sub_services', $service, $service_collection, $service['sub_services'] ) ?>
            <?php if ( has_filter( 'bookly_extras_find_by_service_id' ) ) : ?>
            <fieldset class="form-group ab--for-simple">
                <h4><?php _e( 'Extras', 'bookly' ) ?><a aria-controls="ab--container_<?php echo $service_id ?>" aria-expanded="false" href="#ab--container_<?php echo $service_id ?>" data-toggle="collapse" role="button"></a></h4>
                <div id="ab--container_<?php echo $service_id ?>" class="collapse in">
                    <ul class="extras-container" data-service="<?php echo $service_id ?>">
                        <?php foreach ( apply_filters( 'bookly_extras_find_by_service_id', array(), $service_id ) as $extra ) : ?>
                            <li class="extra" data-extra-id="<?php echo $extra->get( 'id' ) ?>">
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <i class="ab-handle glyphicon glyphicon-align-justify ab-move" title="<?php _e( 'Reorder', 'bookly' ) ?>"></i> <label
                                                for="extras[<?php echo $extra->get( 'id' ) ?>][title]"><?php _e( 'Title', 'bookly' ) ?></label>
                                            <input class="form-control" type="text" id="extras[<?php echo $extra->get( 'id' ) ?>][title]" name="extras[<?php echo $extra->get( 'id' ) ?>][title]" value="<?php echo $extra->get( 'title' ) ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <input type="hidden" name="extras[<?php echo $extra->get( 'id' ) ?>][id]" value="<?php echo $extra->get( 'id' ) ?>">
                                            <input type="hidden" name="extras[<?php echo $extra->get( 'id' ) ?>][attachment_id]" value="<?php echo $extra->get( 'attachment_id' ) ?>">
                                            <div class="attachment-container">
                                                <?php if ( $img = wp_get_attachment_image_src( $extra->get( 'attachment_id' ), 'thumbnail' ) ) : ?>
                                                    <img src="<?php echo $img[0] ?>" class="extra-attachment-image">
                                                    <button class="btn btn-default remove-attachment"><?php _e( 'Remove Image', 'bookly' ) ?></button>
                                                <?php else : ?>
                                                    <img src="" class="extra-attachment-image" style="display: none;">
                                                    <button class="btn btn-default remove-attachment" style="display:none;"><?php _e( 'Remove Image', 'bookly' ) ?></button>
                                                <?php endif ?>
                                                <button class="btn btn-default extra-attachment" <?php if ( $img ) : ?>style='display:none;'<?php endif ?>><?php _e( 'Image', 'bookly' ) ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="duration_<?php echo $extra->get( 'id' ) ?>"><?php _e( 'Duration', 'bookly' ) ?></label>
                                            <select id="duration_<?php echo $extra->get( 'id' ) ?>" class="form-control" name="extras[<?php echo $extra->get( 'id' ) ?>][duration]">
                                                <option value="0"><?php _e( 'OFF', 'bookly' ) ?></option>
                                                <?php for ( $j = $time_interval; $j <= 720; $j += $time_interval ) : ?><?php if ( $extra->get( 'duration' ) > 0 && $extra->get( 'duration' ) / 60 > $j - $time_interval && $extra->get( 'duration' ) / 60 < $j ) : ?>
                                                    <option value="<?php echo esc_attr( $extra->get( 'duration' ) ) ?>" selected><?php echo Bookly\Lib\Utils\DateTime::secondsToInterval( $extra->get( 'duration' ) ) ?></option><?php endif ?>
                                                    <option value="<?php echo $j * 60 ?>" <?php selected( $extra->get( 'duration' ), $j * 60 ) ?>><?php echo Bookly\Lib\Utils\DateTime::secondsToInterval( $j * 60 ) ?></option><?php endfor ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="extras[<?php echo $extra->get( 'id' ) ?>][price]"><?php _e( 'Price', 'bookly' ) ?></label>
                                            <input class="form-control" type="number" step="0.01" min="0.00" name="extras[<?php echo $extra->get( 'id' ) ?>][price]" value="<?php echo $extra->get( 'price' ) ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <button class="btn btn-default extra-delete"><?php _e( 'Delete Item', 'bookly' ) ?></button>
                                </div>
                            </li>
                        <?php endforeach ?>
                    </ul>
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-12">
                                <button class="btn btn-default extra-new"><?php _e( 'New Item', 'bookly' ) ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>
            <?php endif ?>
            <div class="form-group">
                <input type="hidden" name="action" value="ab_update_service">
                <input type="hidden" name="id" value="<?php echo esc_html( $service_id ) ?>">
                <input type="hidden" name="update_staff" value="0">
                <?php \Bookly\Lib\Utils\Common::submitButton( null, 'ajax-service-send' ) ?>
                <?php \Bookly\Lib\Utils\Common::resetButton( null, 'js-reset' ) ?>
            </div>
        </form>
        </div>
    </div>
</div>
<?php endforeach ?>
    <div id="ab-padding-popover-ext" style="display: none"><?php _e( 'Set padding time before and/or after an appointment. For example, if you require 15 minutes to prepare for the next appointment then you should set "padding before" to 15 min. If there is an appointment from 8:00 to 9:00 then the next available time slot will be 9:15 rather than 9:00.', 'bookly' ) ?></div>
    <div id="ab-allowed-popover-ext" style="display: none"><?php _e( 'The maximum number of customers allowed to book the service for the certain time period.', 'bookly' ) ?></div>
    <div id="ab-visibility-popover-ext" style="display: none"><?php _e( 'To make service invisible to your customers set the visibility to "Private".', 'bookly' ) ?></div>
</div>
<?php endif ?>