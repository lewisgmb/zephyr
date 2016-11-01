;(function() {
    var module = angular.module('appointments', ['ui.utils', 'ui.date', 'ngSanitize']);

    module.factory('dataSource', function($q, $rootScope) {
        var ds = {
            appointments : [],
            total     : 0,
            pages     : [],
            loadData  : function(params) {
                var deferred = $q.defer();
                jQuery.ajax({
                    url  : ajaxurl,
                    type : 'POST',
                    data : jQuery.extend({ action : 'ab_get_appointments' }, params),
                    dataType : 'json',
                    success  : function(response) {
                        if (response.success) {
                            ds.appointments = response.data.appointments;
                            ds.total     = response.data.total;
                            ds.pages     = [];
                            ds.paginator = {beg : false, end: false};
                            var neighbor = 5;
                            var beg      = Math.max(1, response.data.active_page - neighbor);
                            var end      = Math.min(response.data.pages, (response.data.active_page + neighbor));
                            if (beg > 1) {
                                ds.paginator.beg = true;
                                beg++;
                            }
                            for (var i = beg; i < end; i++) {
                                ds.pages.push({ number : i, active : response.data.active_page == i });
                            }
                            if (end >= response.data.pages) {
                                ds.pages.push({number: response.data.pages, active: response.data.active_page == response.data.pages});
                            } else {
                                ds.paginator.end = {number: response.data.pages, active: false};
                            }
                        }
                        $rootScope.$apply(deferred.resolve);
                    },
                    error : function() {
                        ds.appointments = [];
                        ds.total = 0;
                        $rootScope.$apply(deferred.resolve);
                    }
                });

                return deferred.promise;
            }
        };

        return ds;
    });

    module.controller('appointmentsCtrl', function($scope, dataSource) {
        // Set up initial data.
        var params = {
            page   : 1,
            sort   : 'start_date',
            order  : 'desc',
            filter : {
                booking_number : '',
                date_start     : '',
                date_end       : '',
                staff_member   : '',
                customer       : '',
                service        : '',
                status         : ''
            }
        };
        $scope.loading   = true;
        $scope.css_class = {
            id                : '',
            start_date        : 'desc',
            staff_name        : '',
            customer_name     : '',
            service_title     : '',
            service_duration  : '',
            status            : '',
            payment           : ''
        };

        var format = 'YYYY-MM-DD';
        $scope.date_start = moment().startOf('month').format(format);
        $scope.date_end   = moment().endOf('month').format(format);

        // Set up data source (data will be loaded in reload function).
        $scope.dataSource = dataSource;

        $scope.reload = function( opt ) {
            $scope.loading = true;
            if (opt !== undefined) {
                if (opt.sort !== undefined) {
                    if (params.sort === opt.sort) {
                        // Toggle order when sorting by the same field.
                        params.order = params.order === 'asc' ? 'desc' : 'asc';
                    } else {
                        params.order = 'asc';
                    }
                    $scope.css_class = {
                        id                : '',
                        start_date        : '',
                        staff_name        : '',
                        customer_name     : '',
                        service_title     : '',
                        service_duration  : '',
                        status            : '',
                        payment           : ''
                    };
                    $scope.css_class[opt.sort] = params.order;
                }
                jQuery.extend(params, opt);
            }
            params.filter.booking_number = $scope.booking_number;
            params.filter.date_start     = $scope.date_start;
            params.filter.date_end       = $scope.date_end;
            params.filter.staff_member   = $scope.staff_member;
            params.filter.customer       = $scope.customer;
            params.filter.service        = $scope.service;
            params.filter.status         = $scope.status;
            dataSource.loadData(params).then(function() {
                $scope.loading = false;
            });
            $scope.sort = params.sort;
            $scope.order = params.order;
        };

        jQuery('.chosen-filters').chosen();

        $scope.watchers = [
            'status',
            'booking_number',
            'staff_member',
            'customer',
            'service'
        ];

        angular.forEach($scope.watchers, function(watcherName){
            $scope.$watch(watcherName, function() {
                $scope.reload();
            });
        });

        $scope.reload();

        /**
         * New appointment.
         */
        $scope.newAppointment = function() {
            showAppointmentDialog(
                null,
                null,
                moment(),
                function(event) {
                    $scope.$apply(function($scope) {
                        $scope.reload();
                    });
                }
            )
        };

        /**
         * Edit appointment.
         *
         * @param appointment_id
         */
        $scope.editAppointment = function(appointment_id) {
            showAppointmentDialog(
                appointment_id,
                null,
                null,
                function(event) {
                    $scope.$apply(function($scope) {
                        $scope.reload();
                    });
                }
            )
        };

        /**
         * Delete customer appointments.
         */
        $scope.deleteAppointments = function() {
            var ids = [];
            jQuery('table input[type=checkbox]:checked').each(function() {
                ids.push(jQuery(this).data('ca_id'));
            });
            if( ids.length ) {
                $scope.loading = true;
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ab_delete_customer_appointments',
                        ids: ids
                    },
                    dataType: 'json',
                    success: function (response) {
                        $scope.$apply(function ($scope) {
                            $scope.reload();
                        });
                    }
                });
            } else{
                alert(BooklyL10n.please_select_at_least_one_row);
            }
            $scope.selectedAll = false;
        };

        $scope.checkAll = function () {
            angular.forEach(dataSource.appointments, function (item) {
                item.Selected = $scope.selectedAll;
            });
        };

        // Init date range picker.
        var picker_ranges = {};
        picker_ranges[BooklyL10n.yesterday]  = [moment().subtract(1, 'days'), moment().subtract(1, 'days')];
        picker_ranges[BooklyL10n.today]      = [moment(), moment()];
        picker_ranges[BooklyL10n.tomorrow]   = [moment().add(1, 'days'), moment().add(1, 'days')];
        picker_ranges[BooklyL10n.last_7]     = [moment().subtract(7, 'days'), moment()];
        picker_ranges[BooklyL10n.last_30]    = [moment().subtract(30, 'days'), moment()];
        picker_ranges[BooklyL10n.this_month] = [moment().startOf('month'), moment().endOf('month')];
        picker_ranges[BooklyL10n.next_month] = [moment().add(1, 'month').startOf('month'), moment().add(1, 'month').endOf('month')];
        moment.locale('en', {
            months       : BooklyL10n.calendar.longMonths,
            monthsShort  : BooklyL10n.calendar.shortMonths,
            weekdays     : BooklyL10n.calendar.longDays,
            weekdaysShort: BooklyL10n.calendar.shortDays,
            weekdaysMin  : BooklyL10n.calendar.shortDays
        });
        jQuery('#reportrange').daterangepicker(
            {
                startDate: moment().startOf('month'),
                endDate: moment().endOf('month'),
                ranges: picker_ranges,
                locale: {
                    applyLabel : BooklyL10n.apply,
                    cancelLabel: BooklyL10n.cancel,
                    fromLabel  : BooklyL10n.from,
                    toLabel    : BooklyL10n.to,
                    customRangeLabel: BooklyL10n.custom_range,
                    daysOfWeek : BooklyL10n.calendar.shortDays,
                    monthNames : BooklyL10n.calendar.longMonths,
                    firstDay   : parseInt(BooklyL10n.startOfWeek),
                    format     : BooklyL10n.mjsDateFormat
                }
            },
            function (start, end) {
                jQuery('#reportrange span').html(start.format(BooklyL10n.mjsDateFormat) + ' - ' + end.format(BooklyL10n.mjsDateFormat));
                $scope.$apply(function ($scope) {
                    $scope.date_start = start.format(format);
                    $scope.date_end = end.format(format);
                    $scope.reload();
                });
            }
        );
    });

    // Bootstrap 'appointmentForm' application.
    angular.bootstrap(document.getElementById('ab-appointment-form'), ['appointmentForm']);

    jQuery('#ab_export_appointments_dialog').on('click', '.export-appointments', function () {
        jQuery('#ab_export_appointments_dialog').modal('hide');
    });
    jQuery('#ab_print_appointments_dialog').on('click', '.print-appointments', function () {
        jQuery('#ab_print_appointments_dialog').modal('hide');
    });

    jQuery('#ab-receipt').on('show.bs.modal', function (e) {
        var $button = jQuery(e.relatedTarget);
        var $body = jQuery(this).find('.modal-body');
        jQuery.ajax({
            url:      ajaxurl,
            data:     {action: 'ab_get_payment', payment_id: $button.data('payment-id')},
            dataType: 'json',
            success:  function (response) {
                if (response.success) {
                    $body.html(response.data.html);
                }
            }
        });
    }).on('hidden.bs.modal', function () {
        jQuery(this).find('.modal-body').html(jQuery(this).find('#ab--loader').html());
    });
})();