$(document).ready(function () {
    $.debtorsCtrl.init();
    $.debtorsCtrl.initDebtorMassSmsTable();
    $.debtorsCtrl.changeDebtorMassSmsFilter();

    $(document).on('change', '#formSendSMS input[type="radio"]', function () {
        $('input[name="sms_tpl_id"]').val($(this).val());
    });

    $(document).on('change', '#formSendSMS', function () {
        if ($('#old_user_id').val() != '' && $('input[name="sms_tpl_id"]').val() != '') {
            $('#sendMassSms').prop('disabled', false);
        } else {
            $('#sendMassSms').prop('disabled', true);
        }
    });
    $(document).on('change', 'input[name="sms_date"]', function () {
        var arVal = $(this).val().split('-');
        $('.sms_text').each(function () {
            $(this).text($(this).text().replace(/\d{1,2}\.\d{1,2}\.\d{4}/, arVal[2] + '.' + arVal[1] + '.' + arVal[0]));
        });
        $('input[name="sms_tpl_date"]').val(arVal[2] + '.' + arVal[1] + '.' + arVal[0]);
    });
    $(document).on('click', '#sendMassSms', function () {
        $(this).prop('disabled', true);
        $('#smsFilter').prop('disabled', true);
        $('#smsTpls').prop('disabled', true);

        $('#smsInfoBlock').show();

        console.log($('#debtormasssmsTable').dataTable().api().rows().ids().toArray());
        $.ajax({
            type: "POST",
            url: "/ajax/debtors/masssms/send",
            data: {
                smsId : $('input[name="sms_tpl_id"]').val(),
                smsDate : $('input[name="sms_tpl_date"]').val(),
                responsibleUserId : $('#old_user_id').val(),
                debtorsIds : $('#debtormasssmsTable').dataTable().api().rows().ids().toArray()
            },
            dataType: "json",
            success: function (data) {
                if (data.error == 'success') {
                    $('#smsInfo').attr('class', 'alert alert-success');
                    $('#smsInfo').text('СМС отправлены. Кол-во: ' + data.cnt);
                } else {

                    $('#smsInfo').attr('class', 'alert alert-danger');
                    $('#smsInfo').text('Ошибка: ' + data);
                }
            },
            error : function () {
                $('#smsInfo').attr('class', 'alert alert-danger');
                $('#smsInfo').text('Ошибка: Не удалось отправить смс');
            }
        });
    });
});