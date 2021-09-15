(function ($) {
    $.advRepCtrl = {
        tables: []
    };
    $.advRepCtrl.initList = function () {
        var tableCtrl = new TableController('advancereports', [
            {data: '0', name: 'ar_created_at'},
            {data: '1', name: 'user_name'},
            {data: '2', name: 'subdiv_name'},
            {data: '3', name: 'actions', searchable: false, orderable: false},
        ], {order: [[0, "desc"]], listURL: 'ajax/reports/advancereports/list'});
    };
    $.advRepCtrl.initEditor = function () {
        $.advRepCtrl.calculateGoodsTotalPrice($('#goodsTable tbody tr:first'));
        $.advRepCtrl.updateOrderMoney($('#advanceTable tbody tr:first'));
        $.advRepCtrl.nomenclaturesAutocomplete($('#goodsTable tbody tr,#otherTable tbody tr'));
        
        var data_names = ['advance', 'goods', 'other'];
        data_names.forEach(function (item) {
//            $("#"+item+"Table").find('[data-autocomplete]').find('input').autocomplete('destroy');
            var props = {
                table: $("#" + item + "Table"),
                form: $('#advRepForm'),
                dataField: $('#advRepForm [name="' + item + '_data"]'),
                copyEvents: false
            };
            if (item == 'goods') {
                props.onAddEvent = function ($row) {
                    $.advRepCtrl.calculateGoodsTotalPrice($row);
                    $.advRepCtrl.nomenclaturesAutocomplete($row);
                };
            }
            if (item == 'advance') {
                props.onAddEvent = function ($row) {
                    $.advRepCtrl.updateOrderMoney($row);
                };
            }
            if (item == 'other') {
                props.onAddEvent = function ($row) {
                    $.advRepCtrl.nomenclaturesAutocomplete($row);
                };
            }
            $.advRepCtrl.tables.push(new DataGridController($('#advRepForm [name="' + item + '_data"]').val(), props));
        });
        $('.money').myMoney();

        $('#advRepFormSubmitBtn').click(function () {
            $.advRepCtrl.tables.forEach(function (item) {
                item.updateDataField();
            });
            $('#advRepForm').submit();
        });
    };
    $.advRepCtrl.updateOrderMoney = function ($row) {
        $row.find('[name="order_id"]').change(function () {
            var v = $(this).find('option:selected').attr('data-money') / 100;
            $(this).parents('tr:first').find('[name="advance_money"]').val(isNaN(v) ? 0 : v).blur();
            $(this).parents('tr:first').find('[name="advance_issue"]').val(isNaN(v) ? 0 : v).blur();
            $(this).parents('tr:first').find('[name="advance_spent"]').val(isNaN(v) ? 0 : v).blur();
        }).change();
    };
    $.advRepCtrl.calculateGoodsTotalPrice = function ($row) {
        console.log($row);
        $row.find('[name="amount"],[name="price"]').change(function () {
            var $price = $row.find('[name="price"]');
            var $amount = $row.find('[name="amount"]');
            var $total = $row.find('[name="total_money"]');
            if ($price.val() > 0 && $amount.val() > 0) {
                $total.val(parseFloat($price.val()) * parseFloat($amount.val()));
            }
        });
    };
    $.advRepCtrl.ordersAutocomplete = function () {
        $('[name="order_id"]').autocomplete({
            source: $.app.url + '/ajax/reports/advancereports/issueorders',
            minLength: 2,
            select: function (event, ui) {
                $(this).prev('[name="order_id"]').val(ui.item.id);

            }
        });
    };
    $.advRepCtrl.nomenclaturesAutocomplete = function ($row) {
        $row.find('[name="nomenclature_id_autocomplete"]').each(function () {
            console.log($(this));
            $(this).autocomplete({
                source: $.app.url + '/ajax/nomenclatures/autocomplete?type=' + $(this).attr('data-type'),
                minLength: 2,
                select: function (event, ui) {
                    $(this).prev('[name="nomenclature_id"]').val(ui.item.id);
                }
            });
        });
    };

})(jQuery);