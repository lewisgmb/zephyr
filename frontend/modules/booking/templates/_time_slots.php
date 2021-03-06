<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<button class="ab-available-day" value="<?php echo esc_attr( $group ) ?>">
    <?php echo date_i18n( ( $is_all_day_service ? 'M' : 'D, M d' ), strtotime( $group ) ) ?>
</button>
<?php foreach ( $slots as $client_timestamp => $slot ) :
    printf( '<button value="%s" data-group="%s" class="ab-available-hour%s%s" %s>
        <span class="ladda-label"><i class="ab-hour-icon"><span></span></i>%s</span>
    </button>',
        esc_attr( json_encode( $slot['data'] ) ),
        $group,
        $slot['blocked'] ? ' booked' : '',
        $slot['data'][0][2] == $selected_timestamp ? ' ab-bold' : '',
        disabled( $slot['blocked'], true, false ),
        date_i18n( ( $is_all_day_service ? 'D, M d' : get_option( 'time_format' ) ), $client_timestamp )
    );
endforeach ?>