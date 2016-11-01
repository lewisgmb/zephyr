<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    $total = 0;
?>
<?php if ( $payments && ! empty ( $payments ) ) : ?>
    <?php foreach ( $payments as $payment ) : ?>
        <?php $details = json_decode( $payment['details'], true ); $multiple = count( $details['items'] ) > 1; ?>
    <tr>
        <td><?php echo \Bookly\Lib\Utils\DateTime::formatDateTime( $payment['created'] ) ?></td>
        <td><?php echo \Bookly\Lib\Entities\Payment::typeToString( $payment['type'] ) ?></td>
        <td><?php echo $payment['customer'] ?: $details['customer'] ?></td>
        <td><?php echo $payment['provider'] ?: $details['items'][0]['staff_name'] ?><?php if ( $multiple ) : ?> <span class="glyphicon glyphicon-shopping-cart" aria-hidden="true" title="<?php esc_attr_e( 'See details for more items', 'bookly' ) ?>"></span><?php endif ?></td>
        <td><?php echo esc_html( $payment['service'] ?: $details['items'][0]['service_name'] ) ?><?php if ( $multiple ) : ?> <span class="glyphicon glyphicon-shopping-cart" aria-hidden="true" title="<?php esc_attr_e( 'See details for more items', 'bookly' ) ?>"></span><?php endif ?></td>
        <td><?php echo $payment['start_date'] ? \Bookly\Lib\Utils\DateTime::formatDateTime( $payment['start_date'] ) : \Bookly\Lib\Utils\DateTime::formatDateTime( $details['items'][0]['appointment_date'] ) ?><?php if ( $multiple ) : ?> <span class="glyphicon glyphicon-shopping-cart" aria-hidden="true" title="<?php esc_attr_e( 'See details for more items', 'bookly' ) ?>"></span><?php endif ?></td>
        <td><div class="text-right"><?php echo \Bookly\Lib\Utils\Common::formatPrice( $payment['total'] ) ?></div></td>
        <td><?php if ( $payment['type'] != \Bookly\Lib\Entities\Payment::TYPE_LOCAL ) echo \Bookly\Lib\Entities\Payment::statusToString( $payment['status'] ) ?></td>
        <td><a href="#" class="btn btn-info" data-payment-id="<?php echo $payment['id'] ?>" data-target="#ab-receipt" data-toggle="modal"><?php _e( 'Details', 'bookly' )?></a></td>
        <?php $total += $payment['total'] ?>
    </tr>
    <?php endforeach ?>
    <tr>
        <td colspan=7><div class=pull-right><strong><?php _e( 'Total', 'bookly' ) ?>: <?php echo \Bookly\Lib\Utils\Common::formatPrice( $total ) ?></strong></div></td>
        <td colspan=2></td>
    </tr>
<?php endif ?>