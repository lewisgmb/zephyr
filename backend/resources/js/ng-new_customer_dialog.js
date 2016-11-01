;(function() {

    angular.module('newCustomerDialog', []).directive('newCustomerDialog', function() {
        return {
            restrict : 'A',
            replace  : true,
            scope    : {
                callback  : '&newCustomerDialog',
                backdrop  : '@',
                btn_class : '@btnClass'
            },
            templateUrl : ajaxurl + '?action=ab_get_ng_new_customer_dialog_template',
            // The linking function will add behavior to the template.
            link: function(scope, element, attrs) {
                // Init properties.
                var init = function() {
                    // Form fields.
                    scope.form = {
                        wp_user_id : '',
                        name       : '',
                        phone      : '',
                        email      : ''
                    };
                    // Custom fields.
                    element.find('input.ab-custom-field:text, textarea.ab-custom-field, select.ab-custom-field').val('');
                    element.find('input.ab-custom-field:checkbox, input.ab-custom-field:radio').prop('checked', false);
                    element.find('label.ab_service > input:checkbox').prop('checked', false);
                    if (BooklyL10n.intlTelInput.enabled) {
                        element.find('#phone').intlTelInput({
                            preferredCountries: [BooklyL10n.intlTelInput.country],
                            defaultCountry: BooklyL10n.intlTelInput.country,
                            geoIpLookup: function (callback) {
                                jQuery.get(ajaxurl, {action: 'ab_ip_info'}, function () {
                                }, 'json').always(function (resp) {
                                    var countryCode = (resp && resp.country) ? resp.country : '';
                                    callback(countryCode);
                                });
                            },
                            utilsScript: BooklyL10n.intlTelInput.utils
                        });
                    }
                    // Form errors.
                    scope.errors = {
                        name : {
                            required : false
                        }
                    };
                    // Loading indicator.
                    scope.loading = false;
                };

                // Run init.
                init();

                // On 'Cancel' button click.
                scope.closeDialog = function() {
                    // Close the dialog.
                    element.children('#ab_new_customer_dialog').modal('hide');
                    // Re-init all properties.
                    init();
                };

                /**
                 * Send form to server.
                 */
                scope.processForm = function() {
                    scope.errors  = {};
                    scope.loading = true;
                    scope.form.phone = BooklyL10n.intlTelInput.enabled ? jQuery('#phone').intlTelInput('getNumber') : jQuery('#phone').val();
                    jQuery.ajax({
                        url  : ajaxurl,
                        type : 'POST',
                        data : jQuery.extend({ action : 'ab_save_customer' }, scope.form),
                        dataType : 'json',
                        success : function ( response ) {
                            scope.$apply(function(scope) {
                                if (response.success) {
                                    response.customer.custom_fields = [];
                                    response.customer.extras = [];
                                    response.customer.status = BooklyL10n.default_status;
                                    // Send new customer to the parent scope.
                                    scope.callback({customer : response.customer});
                                    // Close the dialog.
                                    scope.closeDialog();
                                } else {
                                    // Set errors.
                                    jQuery.each(response.errors, function(field, errors) {
                                        scope.errors[field] = {};
                                        jQuery.each(errors, function(key, error) {
                                            scope.errors[field][error] = true;
                                        });
                                    });
                                }
                                scope.loading = false;
                            });
                        },
                        error : function() {
                            scope.$apply(function(scope) {
                                scope.loading = false;
                            });
                        }
                    });
                };
            }
        };
    });

})();