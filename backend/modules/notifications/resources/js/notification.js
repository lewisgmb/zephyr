jQuery(function($) {

    Ladda.bind( 'button[type=submit]' );

    // menu fix for WP 3.8.1
    $('#toplevel_page_ab-system > ul').css('margin-left', '0px');

    /* exclude checkboxes in form */
    var $checkboxes = $('.bookly-notifications .panel-title > input:checkbox');

    $checkboxes.change(function () {
        $(this).parents('.panel-heading').next().collapse(this.checked ? 'show' : 'hide');
    });

    $('.ab-popover').popover({
        trigger : 'hover'
    });

});