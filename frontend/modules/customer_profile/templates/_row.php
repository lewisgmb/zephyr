<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php foreach ( $appointments as $app ) : ?>
    <?php if ( ! isset( $compound_token[ $app['compound_token'] ] ) ) : ?>
        <?php $app['compound_token'] !== null && $compound_token[ $app['compound_token'] ] = true; ?>
    <tr>
        <?php foreach ( $columns as $column ) : ?>
            <?php
            switch ( $column ) :
                case 'date': ?>
                    <td><?php echo \Bookly\Lib\Utils\DateTime::formatDate( $app['start_date'] ) ?></td><?php
                    break;
                case 'time': ?>
                    <td><?php echo \Bookly\Lib\Utils\DateTime::formatTime( $app['start_date'] ) ?></td><?php
                    break;
                case 'price': ?>
                    <td><?php echo \Bookly\Lib\Utils\Common::formatPrice( $app['price'] + $app['number_of_persons'] * apply_filters( 'bookly_extras_get_total_price', 0, json_decode( $app['extras'], true ) ) ) ?></td><?php
                    break;
                case 'status': ?>
                    <td><?php echo \Bookly\Lib\Entities\CustomerAppointment::statusToString( $app['appointment_status'] ) ?></td><?php
                    break;
                case 'cancel': ?>
                    <td>
                    <?php if ( $app['start_date'] > current_time( 'mysql' ) ) : ?>
                        <?php if( $allow_cancel < strtotime( $app['start_date'] ) ) : ?>
                            <?php if ( $app['appointment_status'] != \Bookly\Lib\Entities\CustomerAppointment::STATUS_CANCELLED ) : ?>
                                <a class="ab-btn" style="background-color: <?php echo $color ?>" href="<?php echo esc_attr( $url_cancel . '&token=' . $app['token'] ) ?>">
                                    <span class="ab_label"><?php _e( 'Cancel', 'bookly' ) ?></span>
                                </a>
                            <?php else : ?>✓<?php endif ?>
                        <?php else : ?>
                            <span class="ab_label"><?php _e( 'Not allowed', 'bookly' ) ?></span>
                        <?php endif ?>
                    <?php else : ?>
                        <?php _e( 'Expired', 'bookly' ) ?>
                    <?php endif ?>
                    </td><?php
                    break;
                default : ?>
                    <td><?php echo $app[ $column ] ?></td>
            <?php endswitch ?>
        <?php endforeach ?>
    <?php endif ?>
    </tr>
<?php endforeach ?>