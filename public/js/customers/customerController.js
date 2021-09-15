(function ($) {
    $.custCtrl = {};
    $.custCtrl.init = function () {
        $.custCtrl.form = $('#customerEditForm');
        $.custCtrl.form.validator({
            errors: {
                minlength: 'Нужно больше символов'
            }
        });
//        $.custCtrl.form.find('[name="telephone"]').mask("7(999)999-9999");
        $.custCtrl.form.find('[name="subdivision_code"]').mask("000-000");
        if ($('#loanCreateForm [name="copyFactAddress"]').prop("checked")) {
            $('#factResidence').find('input,select,textarea').prop('readonly', true);
        }
        $('#registrationResidence').find('input,select,textarea').bind('change', function () {
            if ($('[name="copyFactAddress"]').prop("checked")) {
                $('#factResidence [name="fact_' + $(this).attr('name') + '"]').val($(this).val());
            }
        });
        $('#registrationResidence [name="zip"]').bind('change', function () {
            if ($('[name="copyFactAddress"]').prop("checked")) {
                $('#registrationResidence').find('input,select,textarea').each(function () {
                    $('#factResidence [name="fact_' + $(this).attr('name') + '"]').val($(this).val());
                });
            }
        });

        $('[name="copyFactAddress"]').change(function () {
            if (this.checked) {
                $('#factResidence').find('input,select,textarea').prop('readonly', true);
                $('#registrationResidence').find('input,select,textarea').each(function () {
                    $('#factResidence [name="fact_' + $(this).attr('name') + '"]').val($(this).val());
                });
            }
            if (!this.checked) {
                $('#factResidence').find('input,select,textarea').prop('readonly', false).val('');
            }
        });
//                .prop('checked', true).change();
    };
    $.custCtrl.showCardsListModal = function (customerID) {
        $.app.blockScreen(true);
        $.get(armffURL + 'customers/cards/' + customerID).done(function (data) {
            $.app.blockScreen(false);
            $('#cardsListHolder').empty();
            $('#cardsListModal').modal();
            for (var card in data) {
                var html = '';
                if(data[card].status==1){
                    html += '<div data-card-id="' + data[card].id + '" class="list-group-item list-group-item-danger">\n\
                    <h4 class="list-group-item-heading">Номер карты: ' + data[card].card_number + '<span class="label label-danger">Заблокирована</span></h4>\n\
                    <p class="list-group-item-text">Секретное слово: ' + data[card].secret_word + '</p>\n\
                    <button onclick="$.custCtrl.disableCard(' + data[card].id + ')" class="btn btn-default btn-remove" disabled><span class="glyphicon glyphicon-remove"></span> Заблокировать</button>\n\
                    <button onclick="$.custCtrl.enableCard(' + data[card].id + ')" class="btn btn-success btn-enable" title="Разблокировать"><span class="glyphicon glyphicon-ok"></span> Разблокировать</button>\n\
                    </div>';
                } else {
                    html += '<div data-card-id="' + data[card].id + '" class="list-group-item ' + '">\n\
                    <h4 class="list-group-item-heading">Номер карты: ' + data[card].card_number + '</h4>\n\
                    <p class="list-group-item-text">Секретное слово: ' + data[card].secret_word + '</p>\n\
                    <button onclick="$.custCtrl.disableCard(' + data[card].id + ')" class="btn btn-default btn-remove"><span class="glyphicon glyphicon-remove"></span> Заблокировать</button>\n\
                    <button onclick="$.custCtrl.enableCard(' + data[card].id + ')" class="btn btn-success btn-enable" title="Разблокировать" disabled><span class="glyphicon glyphicon-ok"></span> Разблокировать</button>\n\
                    </div>';
                }
                $('#cardsListHolder').append(html);
            }
        });
    };
    $.custCtrl.disableCard = function (cardID) {
        $.app.blockScreen(true);
        $.post(armffURL + 'cards/disable/' + cardID).done(function (data) {
            $.app.blockScreen(false);
            if (parseInt(data) === 1) {
                var $item = $('#cardsListHolder [data-card-id="' + cardID + '"]');
                $item.addClass('list-group-item-danger');
                $item.children('.list-group-item-heading').append('<span class="label label-danger">Заблокирована</span>');
                $item.find('.btn-remove').prop('disabled', true);
                $item.find('.btn-enable').prop('disabled', false);
            }
        });
    };
    $.custCtrl.enableCard = function (cardID) {
        $.app.blockScreen(true);
        $.post(armffURL + 'cards/enable/' + cardID).done(function (data) {
            $.app.blockScreen(false);
            if (parseInt(data) === 1) {
                var $item = $('#cardsListHolder [data-card-id="' + cardID + '"]');
                $item.removeClass('list-group-item-danger');
                $item.find('.label-danger').remove();
                $item.find('.btn-remove').prop('disabled', false);
                $item.find('.btn-enable').prop('disabled', true);
            }
        });
    };
    $.custCtrl.openAddCardModal = function (customerID) {
        $('#addCardModal form').find('input,textarea').val('');
        $('#addCardModal form input[name="customer_id"]').val(customerID);
        $('#addCardModal').modal();
    };
    $.custCtrl.addCard = function () {
        $.post($('#addCardModal form').attr('action'), $('#addCardModal form').serializeArray()).done(function (data) {
            if (parseInt(data) === 1) {
                var $form = $('#addCardModal form');
                $('#customersTable td:contains(' + $form.find('[name="customer_id"]').val() + ')').parents('tr:first').children('td:nth-child(7)').text($form.find('[name="card_number"]').val());
            }
            $('#addCardModal').modal('hide');
            $.app.ajaxResult(data);
        });
    };
})(jQuery);