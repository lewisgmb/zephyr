<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<style>
    .search-choice { display: none; }
</style>
<div ng-controller=appointmentDialogCtrl>
    <div id=ab_appointment_dialog class="modal fade">
        <div class="modal-dialog">
            <div ng-show=loading class="modal-content loading-indicator">
                <div class="modal-body">
                    <span class="ab-loader"></span>
                </div>
            </div>
            <div ng-hide=loading class="modal-content">
                <form ng-submit=processForm() class=form-horizontal>
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php _e( 'New appointment', 'bookly' ) ?></h4>
                    </div>
                    <div class="modal-body">
                        <div style="padding: 0 15px;">
                            <div class=form-group>
                                <label for="ab_provider"><?php _e( 'Provider', 'bookly' ) ?></label>
                                <select id="ab_provider" class="field form-control" ng-model="form.staff" ng-options="s.full_name for s in dataSource.data.staff" ng-change="onStaffChange()"></select>
                            </div>

                            <div class=form-group>
                                <label for="ab_service"><?php _e( 'Service', 'bookly' ) ?></label>
                                <div my-slide-up="errors.service_required" style="color: red; margin-top: 5px;">
                                    <?php _e( 'Please select a service', 'bookly' ) ?>
                                </div>
                                <select id="ab_service" class="field form-control" ng-model="form.service" ng-options="s.title for s in form.staff.services" ng-change="onServiceChange()">
                                    <option value=""><?php _e( '-- Select a service --', 'bookly' ) ?></option>
                                </select>
                            </div>

                            <div class=form-group>
                                <label for="ab_date"><?php _e( 'Date', 'bookly' ) ?></label>
                                <input id="ab_date" class="form-control ab-auto-w" type=text ng-model=form.date ui-date="dateOptions" autocomplete="off" />
                            </div>
                            <div class="form-group">
                                <div ng-hide="form.service.duration == 86400">
                                    <label for="ab_period"><?php _e( 'Period', 'bookly' ) ?></label>
                                    <div>
                                        <select id="ab_period" style="display: inline" class="form-control ab-auto-w" ng-model=form.start_time
                                                ng-options="t.title for t in dataSource.data.start_time"
                                                ng-change=onStartTimeChange()></select>
                                        <span>&nbsp;<?php _e( 'to', 'bookly' ) ?>&nbsp;</span>
                                        <select style="display: inline" class="form-control ab-auto-w" ng-model=form.end_time
                                                ng-options="t.title for t in dataSource.getDataForEndTime()"
                                                ng-change=onEndTimeChange()></select>

                                        <div my-slide-up=errors.date_interval_warning id=date_interval_warning_msg style="color: green; margin-top: 5px;">
                                            <?php _e( 'Selected period doesn\'t match service duration', 'bookly' ) ?>
                                        </div>
                                        <div my-slide-up="errors.time_interval" ng-bind="errors.time_interval" style="color: red; margin-top: 5px;"></div>
                                    </div>
                                </div>
                                <div my-slide-up=errors.date_interval_not_available id=date_interval_not_available_msg style="color: red; margin-top: 5px;">
                                    <?php _e( 'The selected period is occupied by another appointment', 'bookly' ) ?>
                                </div>
                            </div>
                            <div class=form-group>
                                <label>
                                    <?php _e( 'Customers', 'bookly' ) ?>
                                    <span ng-show="form.service" title="<?php esc_attr_e( 'Selected / maximum', 'bookly' ) ?>">({{dataSource.getTotalNumberOfPersons()}}/{{form.service.capacity}})</span>
                                </label>
                                <div my-slide-up="errors.customers_required" style="color: red; margin-top: 5px;"><?php _e( 'Please select a customer', 'bookly' ) ?></div>
                                <div my-slide-up="errors.overflow_capacity" ng-bind="errors.overflow_capacity" style="color: red; margin-top: 5px;"></div>
                                <ul class="ab-customer-list">
                                    <li ng-repeat="customer in form.customers">
                                        {{customer.number_of_persons}}&times;<i class="glyphicon glyphicon-user"></i>
                                        <a ng-click="editCustomFields(customer)" title="<?php esc_attr_e( 'Edit booking details', 'bookly' ) ?>">{{customer.name}}</a>
                                        <span ng-class="{'glyphicon ab-popover': true, 'glyphicon-time': customer.status == 'pending', 'glyphicon-ok': customer.status == 'approved', 'glyphicon-remove': customer.status == 'cancelled'}" data-content="<?php esc_attr_e( 'Status', 'bookly' ) ?>: {{statusToString(customer.status)}}" style="cursor:default"></span>
                                        <span ng-click="showModal('receipt', customer)" ng-show="customer.payment_gateway" ng-class="{'glyphicon ab-popover': true, 'glyphicon-credit-card': ( customer.payment_gateway != '' && customer.payment_gateway.indexOf('local') == -1 ), 'glyphicon-usd': customer.payment_gateway.indexOf('local') != -1}" data-content="<?php esc_attr_e( 'Payment', 'bookly' ) ?>: {{customer.payment_title}}"></span>
                                        <?php if ( has_action( 'wp_ajax_bookly_compound_services_get_appointments' ) ) : ?>
                                        <span ng-click="showModal('compound', customer)" ng-show="customer.compound_token" class="ab-popover glyphicon glyphicon-th-list" data-content="<?php esc_attr_e( 'Part of compound service', 'bookly' ) ?>: {{customer.compound_service}}"></span>
                                        <?php endif ?>
                                        <span ng-click="removeCustomer(customer)" class="glyphicon glyphicon-trash ab-popover" data-content="<?php esc_attr_e( 'Remove customer', 'bookly' ) ?>"></span>
                                    </li>
                                </ul>

                                <div ng-show="!form.service || dataSource.getTotalNotCancelledNumberOfPersons() < form.service.capacity">
                                    <select id="chosen" multiple data-placeholder="<?php esc_attr_e( '-- Search customers --', 'bookly' ) ?>"
                                            class="field chzn-select form-control" chosen="dataSource.data.customers"
                                            ng-model="form.customers" ng-options="c.name for c in dataSource.data.customers">
                                    </select><br/>
                                    <a href=#ab_new_customer_dialog class="{{btn_class}}" data-backdrop={{backdrop}} data-toggle="modal"><?php _e( 'New customer', 'bookly' ) ?></a>
                                </div>
                            </div>

                            <div class=form-group>
                                <label for="ab_notification"><?php _e( 'Send notifications', 'bookly' ) ?></label>
                                <div>
                                    <select class="form-control ab-inline-block ab-auto-w" style="margin-top: 0" ng-model=form.notification id="ab_notification">
                                        <option value="no"><?php _e( 'Don\'t send', 'bookly' ) ?></option>
                                        <option value="changed_status"><?php _e( 'If status changed', 'bookly' ) ?></option>
                                        <option value="all"><?php _e( 'To all customers', 'bookly' ) ?></option>
                                    </select>
                                    <?php \Bookly\Lib\Utils\Common::popover( __( 'If email or SMS notifications are enabled and you want customers or staff member to be notified about this appointment after saving, select appropriate option before clicking Save. With "If status changed" the notifications are sent to those customers whose status has just been changed. With "To all customers" the notifications are sent to everyone in the list.', 'bookly' ) ) ?>
                                </div>
                            </div>
                            <div class=form-group>
                                <label for="ab_internal_note"><?php _e( 'Internal note', 'bookly' ) ?></label>
                                <div>
                                    <textarea class="form-control" ng-model=form.internal_note id="ab_internal_note"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class=dialog-button-wrapper>
                            <?php \Bookly\Lib\Utils\Common::submitButton() ?>
                            <a ng-click=closeDialog() class=ab-reset-form href="" data-dismiss="modal"><?php _e( 'Cancel', 'bookly' ) ?></a>
                        </div>
                    </div>
                </form>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    <div style="margin-bottom: 2px;" class="ab-inline-block ab-create-customer" new-customer-dialog=createCustomer(customer) backdrop=false btn-class=""></div>
    <?php include '_custom_fields_form.php' ?>
    <?php $this->render( AB_PATH . '/backend/modules/payments/templates/_modal', array( 'modal' =>array( 'id' => 'ab--modal', 'title' => 'Bookly' ) ) ) ?>
</div>
