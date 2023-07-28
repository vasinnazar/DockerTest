$(document).ready(function () {
    $(document).on('click', '#searchContactsButton', function() {
        $(this).attr('disabled', true);
        $(this).html('Идет поиск, ожидайте...');

        $.post($.app.url + '/ajax/debtors/searchEqualContacts', {debtor_id: $('input[name="debtor_id"]').val()}).done(function(answer) {
            $('#searchContactsContent').html(answer);
        });
    });
});