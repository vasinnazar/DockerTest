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
    $.debtorsCtrl.openModalDeleteDebtorEvent = function (eventId, debtorId) {
        $('#deleteDebtorEventModal').modal('show');
        $('#eventId').attr('value', eventId);
        $('#debtorId').attr('value', debtorId);
    };
    $.debtorsCtrl.deleteDebtorEvent = function () {
        const eventId = $('#eventId').val();
        const debtorId = $('#debtorId').val();
        const csrfToken = $('#csrfToken').val();
        $.app.blockScreen(true);
        fetch(
            `${$.app.url}/ajax/debtors/${debtorId}/events/${eventId}`,
            {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                redirect: 'manual',
            }
        )
            .finally(() => {
                window.location.reload();
            })
    };
});