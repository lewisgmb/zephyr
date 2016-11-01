<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<table width="100%">
    <thead>
    <tr>
        <?php foreach ( $titles as $title ) : ?>
            <td><b><?php echo $title ?></b></td>
        <?php endforeach ?>
    </tr>
    </thead>
    <tbody>
    <?php foreach ( $response as $item ) : ?>
        <tr>
            <?php foreach ( $values as $value ) : ?>
                <td><?php echo $item[ $value ] ?></td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
<script>
    window.print();
</script>