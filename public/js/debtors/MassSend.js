$(document).ready(function () {
    $.debtorsCtrl.init();
    $.debtorsCtrl.initDebtorMassSmsTable();
    $.debtorsCtrl.changeDebtorMassSmsFilter();

    $(document).on('change', '#formSendSMS input[type="radio"]', function () {
        $('input[name="template_id"]').val($(this).val());
        $('input[name="is_sms"]').val(1);
        $('#sendMass').val('Отправить SMS');
        $('#formSendEmail input[type="radio"]').prop('checked', false);
        $('#sendInfo').text('Отправка СМС начата. Ожидайте...')
    });

    $(document).on('change', '#formSendEmail input[type="radio"]', function () {
        $('input[name="template_id"]').val($(this).val());
        $('input[name="is_sms"]').val(0);
        $('#sendMass').val('Отправить Email');
        $('#formSendSMS input[type="radio"]').prop('checked', false);
        $('#sendInfo').text('Отправка Email начата. Ожидайте...')
    });

    $(document).on('change', '#formSendSMS', function () {
        if ($('#old_user_id').val() != '' && $('input[name="template_id"]').val() != '') {
            $('#sendMass').prop('disabled', false);
        } else {
            $('#sendMass').prop('disabled', true);
        }
    });

    $(document).on('change', '#formSendEmail', function () {
        if ($('#old_user_id').val() != '' && $('input[name="template_id"]').val() != '') {
            $('#sendMass').prop('disabled', false);
        } else {
            $('#sendMass').prop('disabled', true);
        }
    });

    $(document).on('change', 'input[name="dateSend"]', function () {
        var arVal = $(this).val().split('-');
        $('.email_text').each(function () {
            $(this).text($(this).text().replace(/\d{1,2}\.\d{1,2}\.\d{4}/, arVal[2] + '.' + arVal[1] + '.' + arVal[0]));
        });
        $('.sms_text').each(function () {
            $(this).text($(this).text().replace(/\d{1,2}\.\d{1,2}\.\d{4}/, arVal[2] + '.' + arVal[1] + '.' + arVal[0]));
        });
        $('input[name="date_send"]').val(arVal[2] + '.' + arVal[1] + '.' + arVal[0]);
    });

    $(document).on('click', '#sendMass', function () {
        $(this).prop('disabled', true);
        $('#massFilter').prop('disabled', true);
        $('#smsTpls').prop('disabled', true);
        $('#emailTpls').prop('disabled', true);
        $('#sendInfoBlock').show();
        $.ajax({
            type: "POST",
            url: "/ajax/debtors/masssms/send",
            data: {
                isSms: $('input[name="is_sms"]').val(),
                templateId : $('input[name="template_id"]').val(),
                sendDate : $('input[name="date_send"]').val(),
                responsibleUserId : $('#old_user_id').val(),
                debtorsIds : $('#debtormasssmsTable').dataTable().api().rows().ids().toArray()
            },
            dataType: "json",
            success: function (data) {
                if (data.error == 'success') {
                    $('#sendInfo').attr('class', 'alert alert-success');
                    $('#sendInfo').text('Сообщения отправлены. Кол-во: ' + data.cnt);
                } else {
                    $('#sendInfo').attr('class', 'alert alert-danger');
                    $('#sendInfo').text('Ошибка: ' + data);
                }
            },
            error : function () {
                $('#sendInfo').attr('class', 'alert alert-danger');
                $('#sendInfo').text('Ошибка: Не удалось отправить сообщения');
            }
        });
    });

});