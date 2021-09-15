(function ($) {
    $.reportCtrl = {
        incomeActions: ["0", "3"]
    };
    $.reportCtrl.init = function () {
        $('#reportForm').find('input,select,textarea').bind('keydown', function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                var inputs = $(this).closest('form').find('input,select,textarea');
                inputs.eq(inputs.index(this) + 1).select();
            }
        });
        var data;
        try {
            data = $.parseJSON($('#reportForm [name="data"]').val());
        } catch (e) {
            data = null;
        }

        $.reportCtrl.dataGrid('#cashbookTable', data);
        $('#reportForm').submit(function (e) {
            $('#reportForm [name="data"]').val(JSON.stringify($.reportCtrl.getGridJSON('#cashbookTable')));
        });
    };
    $.reportCtrl.dataGrid = function (table, data) {
        var $row, row, r, f, tbody = '', income = 0, outcome = 0, total = $('#reportForm [name="start_balance"]').val() / 100;
        if (data) {
            for (r in data) {
                $row = $(table).find('tbody tr:first').clone();
                $(table).find('tbody').append($row);
                for (f in data[r]) {
                    if (typeof (data[r][f]) === 'object') {
                        if (f == 'money') {
                            $(table).find('tr:last [name="' + f + '"]').attr('value', parseFloat(data[r][f]['0']));
                        } else {
                            $(table).find('tr:last [name="' + f + '"]').attr('value', data[r][f]['0']);
                            $(table).find('tr:last [name="' + f + '"] option[value="' + data[r][f]['0'] + '"]').attr('selected', 'selected');
                        }
                    } else {
                        if (f == 'money') {
                            $(table).find('tr:last [name="' + f + '"]').attr('value', parseFloat(data[r][f]) * 100);
                        } else {
                            $(table).find('tr:last [name="' + f + '"]').attr('value', data[r][f]);
                            $(table).find('tr:last [name="' + f + '"] option[value="' + data[r][f] + '"]').attr('selected', 'selected');
                        }
                    }
                }
            }
            $(table).find('tbody tr:first').remove();
        }
        $.reportCtrl.calcBalance();
//        $(table).children('tbody').sortable();
        $(table).find('button[name="remove"]').click(function () {
            if ($(this).parents('tbody').find('tr').length > 1) {
                $(this).parents('tr:first').remove();
            } else {
                $(this).parents('tr:first').find('input:not([type="button"]),select').val('');
            }
            $.reportCtrl.calcBalance();
        });
        $(table).find('button[name="add"]').click(function () {
            $row = $(this).parents('tr:first').clone(true);
            $row.find('input:not([type="button"]),select').val('');
//            $row.find('input[name="money"]').myMoney();
            $(this).parents('tr:first').after($row);
        });
        $(table).find('input[name="money"],[name="action"]').change(function () {
            $.reportCtrl.calcBalance();
        });
    };
    $.reportCtrl.calcBalance = function () {
        var json = $.reportCtrl.getGridJSON('#cashbookTable'),
                $calcRow = $('#dcrIncome').parent(),
                income = 0,
                outcome = 0,
                total = 0,
                startBalance = parseFloat($('#cbStartBalance').text()),
                endBalance = parseFloat($('#cbEndBalance').text());
        for (var r in json) {
            if (json[r]['action'] != "2") {
                if ($.reportCtrl.incomeActions.indexOf(json[r]['action']) >= 0) {
                    income += parseFloat(json[r]['money']);
                } else {
                    outcome += parseFloat(json[r]['money']);
                }
            }
        }
        total = (startBalance - outcome + income).toFixed(2);
        $('#dcrIncome').text(income.toFixed(2));
        $('#dcrOutcome').text(outcome.toFixed(2));
        $('#dcrTotal').text(total);
        if (total == endBalance) {
            $('#dcrTotal').removeClass('danger');
            $('#dcrTotal').addClass('success');
        } else {
            $('#dcrTotal').removeClass('success');
            $('#dcrTotal').addClass('danger');
        }
    };
    $.reportCtrl.getGridJSON = function (table) {
        var json = {}, i = 0;
        $(table).find('tbody>tr').each(function () {
            json[i] = {};
            $(this).find('input:not([type="button"]),select').each(function () {
                json[i][$(this).attr('name')] = $(this).val();
                if ($(this).attr('name') == 'money' && $(this).val() == '') {
                    json[i][$(this).attr('name')] = 0;
                }
            });
            i++;
        });
        return json;
    };
})(jQuery);