<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><?php _e( 'Payments', 'bookly' ) ?></h3>
    </div>
    <div class="panel-body">
        <div class=ab-nav-payment>
            <div class=row-fluid>
                <div id=reportrange class="ab-reportrange ab-inline-block">
                    <i class="glyphicon glyphicon-calendar"></i>
                    <span data-date="<?php echo date( 'Y-m-d', strtotime( '-30 day' ) ) ?> - <?php echo date( 'Y-m-d' ) ?>"><?php echo date_i18n( get_option( 'date_format' ), strtotime( '-30 day' ) ) ?> - <?php echo date_i18n( get_option( 'date_format' ) ) ?></span> <b style="margin-top: 8px;" class=caret></b>
                </div>
                <div class=ab-inline-block>
                    <select id=ab-type-filter class=selectpicker>
                        <option value="-1"><?php _e( 'All payment types', 'bookly' ) ?></option>
                        <?php foreach ( $types as $type ) : ?>
                            <option value="<?php echo esc_attr( $type ) ?>">
                                <?php echo \Bookly\Lib\Entities\Payment::typeToString( $type ) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                    <select id=ab-provider-filter class=selectpicker>
                        <option value="-1"><?php _e( 'All providers', 'bookly' ) ?></option>
                        <?php foreach ( $providers as $provider ) : ?>
                            <option><?php echo esc_html( $provider ) ?></option>
                        <?php endforeach ?>
                    </select>
                    <select id=ab-service-filter class=selectpicker>
                        <option value="-1"><?php _e( 'All services', 'bookly' ) ?></option>
                        <?php foreach ( $services as $service ) : ?>
                            <option><?php echo esc_html( $service ) ?></option>
                        <?php endforeach ?>
                    </select>
                    <a id=ab-filter-submit href="#" class="btn btn-primary"><?php _e( 'Filter', 'bookly' ) ?></a>
                </div>
                <div class="pull-right"><a href="<?php echo \Bookly\Lib\Utils\Common::escAdminUrl( \Bookly\Backend\Modules\Settings\Controller::page_slug, array( 'tab' => 'payments' ) )?>" class="btn btn-info"><?php _e( 'Settings', 'bookly' )?></a></div>
            </div>
        </div>
        <div id=ab-alert-div class=alert style="display: none"></div>
        <div class="table-responsive">
            <table class="table table-striped" cellspacing=0 cellpadding=0 border=0 id=ab_payments_list>
                <thead>
                <tr>
                    <th width=150 class="desc active" order-by=created><a href="javascript:void(0)"><?php _e( 'Date', 'bookly' ) ?></a></th>
                    <th width=100 order-by=type><a href="javascript:void(0)"><?php _e( 'Type', 'bookly' ) ?></a></th>
                    <th width=150 order-by=customer><a href="javascript:void(0)"><?php _e( 'Customer', 'bookly' ) ?></a></th>
                    <th width=150 order-by=provider><a href="javascript:void(0)"><?php _e( 'Provider', 'bookly' ) ?></a></th>
                    <th width=150 order-by=service><a href="javascript:void(0)"><?php _e( 'Service', 'bookly' ) ?></a></th>
                    <th width=150 order-by=start_date><a href="javascript:void(0)"><?php _e( 'Appointment Date', 'bookly' ) ?></a></th>
                    <th width=50  order-by=total><a href="javascript:void(0)"><?php _e( 'Amount', 'bookly' ) ?></a></th>
                    <th width=50  order-by=status><a href="javascript:void(0)"><?php _e( 'Status', 'bookly' ) ?></a></th>
                    <th width=50></th>
                </tr>
                </thead>
                <tbody id=ab-tb-body>
                <?php include '_body.php' ?>
                </tbody>
            </table>
        </div>
        <div id="ab_filter_error" class="alert alert-info" style="display: <?php echo ! ( $payments && count( $payments ) ) ? 'block' : 'none' ?>">
            <?php  _e( 'No payments for selected period and criteria.', 'bookly' ) ?>
        </div>
        <div style="display: none" class="loading-indicator">
            <span class="ab-loader"></span>
        </div>
    </div>
</div>
<?php $this->render( '_modal', array( 'modal' =>array( 'id' => 'ab-receipt', 'title' => __( 'Payment', 'bookly' ) ) ) ) ?>