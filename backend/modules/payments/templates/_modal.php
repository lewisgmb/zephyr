<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="modal fade" id="<?php echo $modal['id'] ?>" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'bookly' ) ?>"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo $modal['title'] ?></h4>
            </div>
            <div class="modal-body">
                <div style="text-align: center;padding: 50px 0 50px 0"><img src="<?php echo includes_url( 'js/tinymce/skins/lightgray/img/loader.gif' ) ?>" alt="<?php esc_attr_e( 'Loading...', 'bookly' ) ?>" /></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e( 'Close', 'bookly' ) ?></button>
            </div>
        </div>
    </div>
    <div id="ab--loader" style="display: none">
        <div style="text-align: center;padding: 50px 0 50px 0"><img src="<?php echo includes_url( 'js/tinymce/skins/lightgray/img/loader.gif' ) ?>" alt="<?php esc_attr_e( 'Loading...', 'bookly' ) ?>" /></div>
    </div>
</div>