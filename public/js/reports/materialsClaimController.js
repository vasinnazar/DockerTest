(function ($) {
    $.dataGridCtrl = {};
    $.dataGridCtrl.init = function () {
        var data;
        try {
            data = $.parseJSON($('#reportForm [name="data"]').val());
        } catch (e) {
            data = null;
        }

        $.dataGridCtrl.dataGrid('#materialsClaimTable',data);
        $('#reportForm').submit(function (e) {
            $('#reportForm [name="data"]').val(JSON.stringify($.dataGridCtrl.getGridJSON('#materialsClaimTable')));
        });
    };
    $.dataGridCtrl.dataGrid = function (table, data) {
        var $row, row, r, f, tbody = '';
        if (data) {
            for (r in data) {
                $row = $(table).find('tbody tr:first').clone();
                $(table).find('tbody').append($row);
                for (f in data[r]) {
                    $(table).find('tr:last [name="' + f + '"]').attr('value', data[r][f]);
                    $(table).find('tr:last [name="' + f + '"] option[value="' + data[r][f] + '"]').attr('selected', 'selected');
                }
            }
            $(table).find('tbody tr:first').remove();
        }
        $(table).find('button[name="remove"]').click(function () {
            if ($(this).parents('tbody').find('tr').length > 1) {
                $(this).parents('tr:first').remove();
            } else {
                $(this).parents('tr:first').find('input:not([type="button"]),select').val('');
            }
        });
        $(table).find('button[name="add"]').click(function () {
            $row = $(this).parents('tr:first').clone(true);
            $row.find('input:not([type="button"]),select').val('');
            $row.find('input[name="money"]').myMoney();
            $(this).parents('tr:first').after($row);
        });
        $(table).find('input[name="money"]').myMoney();
    };
    $.dataGridCtrl.getGridJSON = function (table) {
        var json = {}, i = 0;
        $(table).find('tbody>tr').each(function () {
            json[i] = {};
            $(this).find('input:not([type="button"]),select').each(function () {
                json[i][$(this).attr('name')] = $(this).val();
            });
            i++;
        });
        return json;
    };
})(jQuery);