(function ($) {
    $.reportsListCtrl = {};
    $.reportsListCtrl.init = function () {
        $.reportsListCtrl.table = $('#reportsTable').DataTable({
            order: [[1, "desc"]],
            searching: false,
            sDom: 'Rfrtlip',
            lengthMenu: [[25, 50, 100], [25, 50, 100]],
            language: dataTablesRuLang,
            processing: true,
            serverSide: true,
            ajax: {
                url: armffURL + 'ajax/reports/dailycashreportslist',
                data: function (d) {
                    $('#reportsFilter').find('input,select').each(function () {
                        d[$(this).attr('name')] = $(this).val();
                    });
                }
            },
            columns: [
                {data: '0', name: 'matches'},
                {data: '1', name: 'created_at'},
                {data: '2', name: 'start_balance'},
                {data: '3', name: 'end_balance'},
                {data: '4', name: 'responsible'},
                {data: '5', name: 'actions', orderable: false, searchable: false},
            ],
            drawCallback: function () {
//                $(this).find('tbody tr:first').addClass('active');
//                $(this).find('.glyphicon-ok').parents('tr:first').addClass('success');
//                $(this).find('.glyphicon-remove').parents('tr:first').addClass('danger');
            }
        });
        $('#reportsFilterBtn').click(function () {
            $.reportsListCtrl.filterLogs();
        });
    };
    $.reportsListCtrl.filterLogs = function () {
        $.reportsListCtrl.table.draw();
    };

    $.reportsListCtrl.matchWithCashbook = function (id) {
        $.get(armffURL + 'ajax/reports/matchWithCashbook/' + id).done(function (data) {
            $.reportsListCtrl.table.draw();
        });
    };
})(jQuery);