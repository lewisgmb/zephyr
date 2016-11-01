<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use Bookly\Lib\Entities\CustomerAppointment;
?>
<div id="ab_custom_fields_dialog" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title"><?php _e( 'Edit booking details', 'bookly' ) ?></h4>
            </div>
            <form class="form-horizontal" ng-hide=loading style="z-index: 1050">
            <div class="modal-body">
                <fieldset>
                    <legend><?php _e( 'General', 'bookly' ) ?></legend>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="ab-appointment-status"><?php _e( 'Status', 'bookly' ) ?></label>
                            <select class="ab-custom-field form-control" id="ab-appointment-status">
                                <option value="<?php echo CustomerAppointment::STATUS_PENDING ?>"><?php echo esc_html( CustomerAppointment::statusToString( CustomerAppointment::STATUS_PENDING ) ) ?></option>
                                <option value="<?php echo CustomerAppointment::STATUS_APPROVED ?>"><?php echo esc_html( CustomerAppointment::statusToString( CustomerAppointment::STATUS_APPROVED ) ) ?></option>
                                <option value="<?php echo CustomerAppointment::STATUS_CANCELLED ?>"><?php echo esc_html( CustomerAppointment::statusToString( CustomerAppointment::STATUS_CANCELLED ) ) ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="ab-edit-number-of-persons"><?php _e( 'Number of persons', 'bookly' ) ?></label>
                            <select class="ab-custom-field form-control" id="ab-edit-number-of-persons"></select>
                        </div>
                    </div>
                </fieldset>
                <fieldset id="ab--custom-fields">
                    <legend><?php _e( 'Custom Fields', 'bookly' ) ?></legend>
                    <?php foreach ( $custom_fields as $custom_field ) : ?>
                        <div class="col-md-12">
                            <div class="form-group" data-services="<?php echo esc_attr( json_encode( $custom_field->services ) ) ?>">
                                <label class="ab-formLabel"><?php echo $custom_field->label ?></label>
                                <div class="ab-formField" data-type="<?php echo esc_attr( $custom_field->type )?>" data-id="<?php echo esc_attr( $custom_field->id ) ?>">

                                    <?php if ( $custom_field->type == 'text-field' ) : ?>
                                        <input type="text" class="ab-custom-field form-control" />

                                    <?php elseif ( $custom_field->type == 'textarea' ) : ?>
                                        <textarea rows="3" class="ab-custom-field form-control"></textarea>

                                    <?php elseif ( $custom_field->type == 'checkboxes' ) : ?>
                                        <?php foreach ( $custom_field->items as $item ) : ?>
                                            <div class="checkbox">
                                                <label>
                                                    <input class="ab-custom-field" type="checkbox" value="<?php echo esc_attr( $item ) ?>" />
                                                    <?php echo $item ?>
                                                </label>
                                            </div>
                                        <?php endforeach ?>

                                    <?php elseif ( $custom_field->type == 'radio-buttons' ) : ?>
                                        <?php foreach ( $custom_field->items as $item ) : ?>
                                            <div class="radio">
                                                <label>
                                                    <input type="radio" name="<?php echo $custom_field->id ?>" class="ab-custom-field" value="<?php echo esc_attr( $item ) ?>" />
                                                    <?php echo $item ?>
                                                </label>
                                            </div>
                                        <?php endforeach ?>

                                    <?php elseif ( $custom_field->type == 'drop-down' ) : ?>
                                        <select class="ab-custom-field form-control">
                                            <option value=""></option>
                                            <?php foreach ( $custom_field->items as $item ) : ?>
                                                <option value="<?php echo esc_attr( $item ) ?>"><?php echo $item ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    <?php endif ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </fieldset>
                <?php if ( $extras = apply_filters( 'bookly_extras_find_all', array() ) ) : ?>
                <fieldset id="ab--extras">
                    <legend><?php _e( 'Extras', 'bookly' ) ?></legend>
                    <?php foreach ( $extras as $extra ) : ?>
                        <label class="ab_service service_<?php echo $extra->get( 'service_id' ) ?>" style="width: 100%"><input type="checkbox" name="extra[]" value="<?php echo $extra->get( 'id' ) ?>">
                            <?php echo $extra->getTitle() . ' ' . \Bookly\Lib\Utils\Common::formatPrice( $extra->get( 'price' ) ) ?>
                        </label>
                    <?php endforeach ?>
                </fieldset>
                <?php endif ?>
            </div>
            <div class="modal-footer">
                <input type="button" data-customer="" ng-click=saveCustomFields() class="btn btn-info ab-popup-save" value="<?php _e( 'Apply', 'bookly' ) ?>">
                <input type="button" class="ab-reset-form" data-dismiss=modal value="<?php _e( 'Cancel', 'bookly' ) ?>" aria-hidden=true>
            </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->