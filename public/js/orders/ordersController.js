(function ($) {
    $.ordersCtrl = {};
    $.ordersCtrl.init = function () {
        $('#editOrderModal input.money').myMoney();
        $('#findCustomersModal form').submit(function () {
            var targetForm = '#editOrderModal form';
            $.app.blockScreen(true);
            $.get($(this).attr('action'), $(this).serialize()).done(function (data) {
                $.app.blockScreen(false);
                var i, str, attrs;
                $('#findCustomersResult').empty();
                for (i in data) {
                    str = data[i].fio + ' ' + data[i].series + ' ' + data[i].number;
                    attrs = 'data-passport_id="' + data[i].passport_id + '" data-series="' + data[i].series + '" ' + 'data-number="' + data[i].number + '" ' + 'data-fio="' + data[i].fio + '"';
                    $('<a href="#" class="list-group-item" ' + attrs + '>' + str + '<span class="btn btn-primary btn-sm pull-right" style="margin-top:-5px">Выбрать</span></a>').appendTo('#findCustomersResult').click(function () {
                        $(targetForm + ' [name="passport_id"]').val($(this).attr('data-passport-id'));
                        $(targetForm + ' [name="passport_series"]').val($(this).attr('data-series'));
                        $(targetForm + ' [name="passport_number"]').val($(this).attr('data-number'));
                        $(targetForm + ' [name="contragent_fio"]').val($(this).attr('data-fio'));
                        $('#findCustomersModal').attr('data-target-form', '');
                        $('#findCustomersModal').modal('hide');
                        return false;
                    });
                }
            });
            return false;
        });
        var dataGrid = new DataGridController($('#issueClaimDetailsTable [name="issue_claim_data"]').val(), {
            table: $("#issueClaimDetailsTable"),
            form: $('#editOrderForm'),
            dataField: $('#editOrderForm [name="issue_claim_data"]')
        });
        $('#editOrderModal [name="type"]').change(function () {
            var $selectedOption = $(this).children('option:selected');
            var orderType = $selectedOption.attr('data-text-id');
            var $issueAlertHolder = $('.issue-claim-alert');
            var $issueClaimDetailsTable = $("#issueClaimDetailsTable");
            if (orderType == 'PKO') {
                $('#editOrderModal .purpose-holder').show();
            } else {
                $('#editOrderModal .purpose-holder').hide();
                $('#editOrderModal [name="purpose"]').val('');
            }
            if (orderType == 'VPKO') {
                $('#editOrderModal [name="passport_series"]').parents('.form-group:first').hide();
            } else {
                $('#editOrderModal [name="passport_series"]').parents('.form-group:first').show();
            }
            $issueAlertHolder.empty();
            $('#editOrderModalMoneyHolder').show();
            $issueClaimDetailsTable.hide();
            if ($selectedOption.attr('data-is-issue') === '1') {
                $('#editOrderModal [name="reason"]').prop('required',true);
                $issueClaimDetailsTable.show();
                $('#editOrderModalMoneyHolder').hide();
                $issueAlertHolder.append('<div class="alert alert-warning"><strong>Внимание будет создана заявка на создание ордера в подотчет!</strong><br><small>Вы можете посмотреть список заявок <a href="/orders/issueclaims/index" target="_blank">здесь</a></small></div>');
            } else {
                $('#editOrderModal [name="reason"]').prop('required',false);
            }
        }).change();
        $('#editOrderModal form').submit(function (e) {
            $.app.blockScreen(true);
        });
    };
    $.ordersCtrl.editOrder = function (order_id) {
        $.get(armffURL + 'ajax/orders/order/' + order_id).done(function (data) {
            if (data) {
                for (var d in data) {
                    $('#editOrderModal form').find('[name="' + d + '"]').val(data[d]);
                }
                for (var p in data['passport']) {
                    $('#editOrderModal form').find('[name="passport_' + p + '"]').val(data['passport'][p]);
                }
                $('#editOrderModal form [name="user_id_autocomplete"]').attr('placeholder', data['user']['name']);
                $('#editOrderModal form [name="subdivision_id_autocomplete"]').attr('placeholder', data['subdivision']['name']);
                if ('passport' in data && data['passport'] != null && 'fio' in data['passport']) {
                    $('#editOrderModal form').find('[name="contragent_fio"]').val(data['passport']['fio']);
                }
                $('#editOrderModal form .purpose-holder').show();
                $('#editOrderModal input.money').myMoney();
                $('#editOrderModal').modal();
            }
        });
    };
    $.ordersCtrl.uploadForToday = function () {
        $('#ordersFilter input').val('');
        $('#ordersFilter [name="created_at_min"]').val(moment().format('YYYY-MM-DD'));
        $('#ordersFilter [name="created_at_max"]').val(moment().add(1, 'd').format('YYYY-MM-DD'));
        $('#ordersFilterBtn').click();
        $('#ordersFilter [name="created_at_min"]').val('');
        $('#ordersFilter [name="created_at_max"]').val('');
    };
})(jQuery);