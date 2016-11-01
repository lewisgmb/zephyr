jQuery(function($) {
    var data          = {},
        $report_range = $('#reportrange span'),
        picker_ranges = {};

    picker_ranges[BooklyL10n.yesterday]  = [moment().subtract(1, 'days'), moment().subtract(1, 'days')];
    picker_ranges[BooklyL10n.today]      = [moment(), moment()];
    picker_ranges[BooklyL10n.last_7]     = [moment().subtract(7, 'days'), moment()];
    picker_ranges[BooklyL10n.last_30]    = [moment().subtract(30, 'days'), moment()];
    picker_ranges[BooklyL10n.this_month] = [moment().startOf('month'), moment().endOf('month')];
    picker_ranges[BooklyL10n.last_month] = [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')];

    $('.selectpicker').selectpicker({style: 'btn-info', size: 16});

    moment.locale('en', {
        months:        BooklyL10n.calendar.longMonths,
        monthsShort:   BooklyL10n.calendar.shortMonths,
        weekdays:      BooklyL10n.calendar.longDays,
        weekdaysShort: BooklyL10n.calendar.shortDays,
        weekdaysMin:   BooklyL10n.calendar.shortDays
    });
    function ajaxData(object) {
        data['provider'] = $('#ab-provider-filter').val();
        data['service']  = $('#ab-service-filter').val();
        data['range']    = $report_range.data('date'); //text();
        data['type']     = $('#ab-type-filter').val();
        data['key']      = $('#search_customers').val();

        if ( object ) {
            var $parent = $(object).parent();
            data['order_by'] = $parent.attr('order-by');
            if ($parent.hasClass('active')) {
                data['sort_order'] = $parent.hasClass('desc') ? 'asc' : 'desc';
            } else {
                data['sort_order'] = 'asc';
            }
            $('#ab_payments_list th.active').removeClass('active asc desc');
            $parent.addClass('active ' + data['sort_order']);
        }

        return data;
    }

    // sort order
    $('#ab_payments_list th a').on('click', function() {
        var data = {action: 'ab_sort_payments', data: ajaxData(this)};
        $('.loading-indicator').show();
        $('#ab_payments_list tbody').load(ajaxurl, data, function() {$('.loading-indicator').hide();});
    });

    $('#reportrange').daterangepicker(
        {
            startDate: moment().subtract(30, 'days'), // by default selected is "Last 30 days"
            ranges: picker_ranges,
            locale: {
                applyLabel:  BooklyL10n.apply,
                cancelLabel: BooklyL10n.cancel,
                fromLabel:   BooklyL10n.from,
                toLabel:     BooklyL10n.to,
                customRangeLabel: BooklyL10n.custom_range,
                daysOfWeek:  BooklyL10n.calendar.shortDays,
                monthNames:  BooklyL10n.calendar.longMonths,
                firstDay:    parseInt(BooklyL10n.startOfWeek),
                format:      BooklyL10n.mjsDateFormat
            }
        },
        function(start, end) {
            var format = 'YYYY-MM-DD';
            $report_range
                .data('date', start.format(format) + ' - ' + end.format(format))
                .html(start.format(BooklyL10n.mjsDateFormat) + ' - ' + end.format(BooklyL10n.mjsDateFormat));
        }
    );

    $('#ab-filter-submit').on('click', function() {
        var data = {action: 'ab_filter_payments', data: ajaxData()};
        $('.loading-indicator').show();
        $('#ab_payments_list tbody').load(ajaxurl, data, function(res) {
            $('#ab_filter_error').css('display', res.length ? 'none':'block');
            $('.loading-indicator').hide();
        });
    });

    $('#ab-receipt').on('show.bs.modal', function (e) {
        var $button = $(e.relatedTarget);
        var $body = $(this).find('.modal-body');
        $.ajax({
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
        $(this).find('.modal-body').html($(this).find('#ab--loader').html());
    });
});