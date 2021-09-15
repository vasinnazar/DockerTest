(function ($) {
    $.subdivCtrl = {};
    $.subdivCtrl.subdivsList = function () {
        $('#subdivisionsFilterBtn').click(function(){
            $.subdivCtrl.filterTable();
            return false;
        });
        $.subdivCtrl.table = $('#subdivisionsTable').DataTable({
            order: [[0, "desc"]],
            searching: false,
            sDom: 'Rfrtlip',
            lengthMenu: [[25, 50, 100], [25, 50, 100]],
            language: dataTablesRuLang,
            processing: true,
            serverSide: true,
            ajax: {
                url: armffURL + 'ajax/adminpanel/subdivisions/list',
                data: function (d) {
                    d.name = $('#subdivisionsFilter input[name="name"]').val();
                    d.name_id = $('#subdivisionsFilter input[name="name_id"]').val();
                }
            },
            columns: [
                {data: '0', name: 'name_id'},
                {data: '1', name: 'name'},
                {data: '2', name: 'actions', orderable: false, searchable: false},
            ]
        });
    };
    $.subdivCtrl.filterTable = function () {
        $.subdivCtrl.table.draw();
    };
})(jQuery);