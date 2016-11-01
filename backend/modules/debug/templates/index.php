<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div id="ab_coupons_wrapper" class="panel panel-default">

    <div class="panel-heading">
        <h3 class="panel-title">Database Integrity</h3>
    </div>

    <div class="panel-body">

        <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">

            <?php foreach ( $debug as $tableName => $table ) : ?>

                <div class="panel <?php echo $table['status'] == 1 ? 'panel-success' : 'panel-danger' ?>">
                    <div class="panel-heading" role="tab" id="heading_<?php echo $tableName ?>">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#<?php echo $tableName ?>" aria-expanded="true" aria-controls="<?php echo $tableName ?>">
                                <?php echo $tableName ?>
                            </a>
                        </h4>
                    </div>
                    <div id="<?php echo $tableName ?>" class="panel-collapse collapse" role="tabpanel" aria-labelledby="<?php echo $tableName ?>">
                        <div class="panel-body">
                            <?php if ( $table['status'] ) : ?>
                                <h4>Columns</h4>
                                <table class="table table-condensed">
                                    <thead>
                                        <tr>
                                            <th>Column name</th>
                                            <th width="50">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $table['fields'] as $field => $status ) :?>
                                            <tr class="<?php echo $status ? 'default' : 'danger' ?>">
                                                <td><?php echo $field ?></td>
                                                <td><?php echo $status ? 'OK' : 'ERROR' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php if ( $table['constraints'] ) : ?>
                                    <h4>Constraints</h4>
                                    <table class="table table-condensed">
                                        <thead>
                                            <tr>
                                                <th>Column name</th>
                                                <th>Referenced table name</th>
                                                <th>Referenced column name</th>
                                                <th width="50">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ( $table['constraints'] as $key => $constraint ) : ?>
                                                <tr class="<?php echo $constraint['status'] ? 'default' : 'danger' ?>">
                                                    <td><?php echo $constraint['column_name'] ?></td>
                                                    <td><?php echo $constraint['referenced_table_name'] ?></td>
                                                    <td><?php echo $constraint['referenced_column_name'] ?></td>
                                                    <td><?php echo $constraint['status'] ? 'OK' : 'ERROR' ?></td>
                                                </tr>
                                            <?php endforeach ?>
                                        </tbody>
                                    </table>
                                <?php endif;?>
                            <?php else: ?>
                                Table does not exist
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>

        </div>

    </div>
</div>