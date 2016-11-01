<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use \Bookly\Lib\Entities\CustomerAppointment;
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><?php _e( 'Appointments', 'bookly' ) ?></h3>
    </div>
    <div class="panel-body">
        <div ng-app="appointments" ng-controller="appointmentsCtrl" id="appointmentsCtrl" class="form-horizontal ng-cloak">

            <form style="margin-bottom: 20px" class="form-horizontal" action="<?php echo admin_url( 'admin-ajax.php' ) ?>?action=ab_export_appointments" method="POST">
                <div class="bookly-list-filter">
                    <div>
                        <label for="booking_number"><?php _e( 'Booking Number', 'bookly' ) ?></label>
                    </div>
                    <input class="form-control" type="text" ng-model="booking_number" id="booking_number" />
                </div>
                <div class="bookly-list-filter ab-auto-w">
                    <div>
                        <label><?php _e( 'Date', 'bookly' ) ?></label>
                    </div>
                    <div id=reportrange class="pull-left ab-reportrange">
                        <i class="glyphicon glyphicon-calendar"></i>
                        <span data-date="<?php echo date( 'F j, Y', strtotime( 'first day of' ) ) ?> - <?php echo date( 'F j, Y', strtotime( 'last day of' ) ) ?>"><?php echo date_i18n( get_option( 'date_format' ), strtotime( 'first day of' ) ) ?> - <?php echo date_i18n( get_option( 'date_format' ), strtotime( 'last day of' ) ) ?></span> <b style="margin-top: 8px;" class=caret></b>
                    </div>
                </div>
                <div class="bookly-list-filter">
                    <div>
                        <label for="staff_member"><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_label_employee' ) ?></label>
                    </div>
                    <select class="form-control chosen-filters" ng-model="staff_member" id="staff_member">
                        <option value=""><?php _e( 'All', 'bookly' ) ?></option>
                        <?php foreach ( $staff_members as $staff ) : ?>
                            <option value="<?php echo $staff['id'] ?>"><?php esc_html_e( $staff['full_name'] ) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="bookly-list-filter">
                    <div>
                        <label for="customer"><?php _e( 'Customer', 'bookly' ) ?></label>
                    </div>
                    <select class="form-control chosen-filters" ng-model="customer" id="customer">
                        <option value=""><?php _e( 'All', 'bookly' ) ?></option>
                        <?php foreach ( $customers as $customer ) : ?>
                            <option value="<?php echo $customer['id'] ?>"><?php esc_html_e( $customer['name'] ) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="bookly-list-filter">
                    <div>
                        <label for="service"><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_label_service' ) ?></label>
                    </div>
                    <select class="form-control chosen-filters" ng-model="service" id="service">
                        <option value=""><?php _e( 'All', 'bookly' ) ?></option>
                        <?php foreach ( $services as $service ) : ?>
                            <option value="<?php echo $service['id'] ?>"><?php esc_html_e( $service['title'] ) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="bookly-list-filter">
                    <div>
                        <label for="status"><?php _e( 'Status', 'bookly' ) ?></label>
                    </div>
                    <select class="form-control" ng-model="status" id="status">
                        <option value=""><?php _e( 'All', 'bookly' ) ?></option>
                        <option value="<?php echo CustomerAppointment::STATUS_PENDING ?>"><?php echo CustomerAppointment::statusToString( CustomerAppointment::STATUS_PENDING ) ?></option>
                        <option value="<?php echo CustomerAppointment::STATUS_APPROVED ?>"><?php echo CustomerAppointment::statusToString( CustomerAppointment::STATUS_APPROVED ) ?></option>
                        <option value="<?php echo CustomerAppointment::STATUS_CANCELLED ?>"><?php echo CustomerAppointment::statusToString( CustomerAppointment::STATUS_CANCELLED ) ?></option>
                    </select>
                </div>
                <input type="hidden" name="date_start" ng-value="date_start" />
                <input type="hidden" name="date_end" ng-value="date_end" />
                <input type="hidden" name="sort" ng-value="sort" />
                <input type="hidden" name="order" ng-value="order" />
                <input type="hidden" name="filter[booking_number]" ng-value="booking_number" />
                <input type="hidden" name="filter[staff_member]" ng-value="staff_member" />
                <input type="hidden" name="filter[customer]" ng-value="customer" />
                <input type="hidden" name="filter[service]" ng-value="service" />
                <input type="hidden" name="filter[status]" ng-value="status" />
                <a style="margin-left: 5px;" href="#ab_export_appointments_dialog" class="btn btn-info pull-right" data-toggle="modal"><?php _e( 'Export to CSV', 'bookly' ) ?></a>
                <a style="margin-left: 5px;" href="#ab_print_appointments_dialog" class="btn btn-info pull-right" data-toggle="modal"><?php _e( 'Print', 'bookly' ) ?></a>
                <button type="button" class="btn btn-info pull-right" ng-click="newAppointment()"><?php _e( 'New appointment', 'bookly' ) ?></button>
                <div class="ab-clear"></div>
                <?php include '_export.php' ?>
            </form>
            <form target="_blank" action="<?php echo admin_url( 'admin-ajax.php' ) ?>?action=ab_print_appointments" method="POST">
                <input type="hidden" name="sort" ng-value="sort" />
                <input type="hidden" name="order" ng-value="order" />
                <input type="hidden" name="filter[date_start]" ng-value="date_start" />
                <input type="hidden" name="filter[date_end]" ng-value="date_end" />
                <input type="hidden" name="filter[booking_number]" ng-value="booking_number" />
                <input type="hidden" name="filter[staff_member]" ng-value="staff_member" />
                <input type="hidden" name="filter[customer]" ng-value="customer" />
                <input type="hidden" name="filter[service]" ng-value="service" />
                <input type="hidden" name="filter[status]" ng-value="status" />
                <?php include '_print_popup.php' ?>
            </form>
            <div class="table-responsive">
                <table id="ab_appointments_list" class="table table-striped ab-clear" cellspacing=0 cellpadding=0 border=0>
                    <thead>
                    <tr>
                        <th style="width: 1%;" ng-class="css_class.id"><a href="" ng-click="reload({sort:'id'})"><?php _e( 'No.', 'bookly' ) ?></a></th>
                        <th style="min-width: 12%;" ng-class="css_class.start_date"><a href="" ng-click="reload({sort:'start_date'})"><?php _e( 'Appointment Date', 'bookly' ) ?></a></th>
                        <th style="min-width: 12%;" ng-class="css_class.staff_name"><a href="" ng-click="reload({sort:'staff_name'})"><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_label_employee' ) ?></a></th>
                        <th style="min-width: 12%;" ng-class="css_class.customer_name"><a href="" ng-click="reload({sort:'customer_name'})"><?php _e( 'Customer Name', 'bookly' ) ?></a></th>
                        <th style="min-width: 12%;" ng-class="css_class.service_title"><a href="" ng-click="reload({sort:'service_title'})"><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'ab_appearance_text_label_service' ) ?></a></th>
                        <th style="min-width: 12%;" ng-class="css_class.service_duration"><a href="" ng-click="reload({sort:'service_duration'})"><?php _e( 'Duration', 'bookly' ) ?></a></th>
                        <th style="min-width: 12%;" ng-class="css_class.status"><a href="" ng-click="reload({sort:'status'})"><?php _e( 'Status', 'bookly' ) ?></a></th>
                        <th colspan="2" ng-class="css_class.payment"><a href="" ng-click="reload({sort:'payment'})"><?php _e( 'Payment', 'bookly' ) ?></a></th>
                        <th style="width: 1%;"><input type="checkbox" ng-model="selectedAll" ng-click="checkAll()"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr ng-repeat="appointment in dataSource.appointments">
                        <td>{{appointment.id}}</td>
                        <td>{{appointment.start_date_f}}</td>
                        <td>{{appointment.staff_name}}</td>
                        <td>{{appointment.customer_name}}</td>
                        <td>{{appointment.service_title}}</td>
                        <td>{{appointment.service_duration}}</td>
                        <td>{{appointment.status}}</td>
                        <td><a href="#" data-payment-id="{{appointment.payment_id}}" role="button" data-target="#ab-receipt" data-toggle="modal" data-ng-bind-html="appointment.payment_title"><a/></td>
                        <td>
                            <button class="btn btn-info pull-right" ng-click="editAppointment(appointment.appointment_id)">
                                <?php _e( 'Edit', 'bookly' ) ?>
                            </button>
                        </td>
                        <td><input type="checkbox" data-ca_id="{{appointment.ca_id}}" ng-model="appointment.Selected"></td>
                    </tr>
                    </tbody>
                </table>
                <div ng-hide="dataSource.appointments.length || loading" class="alert alert-info"><?php _e( 'No appointments for selected period.', 'bookly' ) ?></div>
            </div>

            <div>
                <div class="col-xs-8" role="toolbar">
                    <div ng-show="dataSource.pages.length > 1">
                        <div class="btn-group" role="group" ng-show="dataSource.paginator.beg">
                            <button ng-click=reload({page:1}) class="btn btn-default">
                                1
                            </button>
                        </div>
                        <div class="btn-group" role="group">
                            <button ng-click=reload({page:page.number}) class="btn btn-default" ng-class="{'active': page.active}" ng-repeat="page in dataSource.pages">
                                {{page.number}}
                            </button>
                        </div>
                        <div class="btn-group" role="group" ng-show="dataSource.paginator.end != false">
                            <button ng-click=reload({page:dataSource.paginator.end.number}) class="btn btn-default">
                                {{dataSource.paginator.end.number}}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-xs-4">
                    <a class="btn btn-info pull-right" ng-click="deleteAppointments()"><?php _e( 'Delete', 'bookly' ) ?></a>
                </div>
            </div>

            <div ng-show="loading" class="loading-indicator">
                <span class="ab-loader"></span>
            </div>
        </div>
        <div id="ab-appointment-form">
            <?php include AB_PATH . '/backend/modules/calendar/templates/_appointment_form.php' ?>
        </div>
        <?php $this->render( AB_PATH . '/backend/modules/payments/templates/_modal', array( 'modal' =>array( 'id' => 'ab-receipt', 'title' => __( 'Payment', 'bookly' ) ) ) ) ?>
    </div>
</div>
