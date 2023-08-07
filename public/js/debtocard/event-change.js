$(document).ready(function () {
    $.debtorsCtrl.openDebtorEvent = function (id) {
        $.app.blockScreen(true);
        $.get($.app.url + '/ajax/debtors/event/data', {id: id}).done(function (data) {
            for (var k in data) {
                if (k == 'user_id_1c') {
                    $('#editDebtorEventModal [name="search_field_users@id_1c"]').val(data[k]);
                }
                if (k == 'user_fio') {
                    $('#editDebtorEventModal [name="users@name"]').val(data[k]);
                }
                $('#editDebtorEventModal [name="' + k + '"]').val(data[k]);
            }
            $('#editDebtorEventModal').modal('show');
        }).always(function () {
            $.app.blockScreen(false);
        });
    };
    $.debtorsCtrl.openModalDeleteDebtorEvent = function (eventId) {
        $('#deleteDebtorEventModal').modal('show');
        $('#deleteEvent').attr('value', eventId);
    };
    $.debtorsCtrl.deleteDebtorEvent = function () {
        const eventId = $('#deleteEvent').val();
        $.app.blockScreen(true);
        $.ajax({
            url: $.app.url + '/ajax/debtors/delete/event/' + eventId,
            type: 'GET',
            success: function () {
                $('#deleteDebtorEventModal').modal('toggle');
                location.reload();
            }
        });
    };
});