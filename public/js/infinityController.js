(function ($) {
    $.infinityController = {};
    $.infinityController.init = (userExtension) => {
        window.Echo.channel('infinity_calls_' + userExtension)
            .listen('incoming_call', (data) => {

                const accept = $('#infinity_call_accept');
                $('#infinity-incoming-call-fio').text(data.fio);
                $('#infinity-incoming-call-phone').text(data.number);
                $('#infinity-incoming-responsibleUser').text(data.username);

                accept.attr('data-number', data.number);
                accept.attr('data-debtorid', data.debtorid);
                //accept.attr('data-money', data.money);
                accept.attr('data-call', data.call_id);
                //accept.attr('data-lead', data.lead_id);
                accept.attr('data-fio', data.fio);
                accept.attr('data-username', data.username);
                //accept.attr('data-passport-series', data.passport_series);
                //accept.attr('data-passport-number', data.passport_number);
                //accept.attr('data-teleport', data.id_teleport);
                accept.attr('data-extension', userExtension);

                $('#infinity-incoming-call').modal('show');
            });
    };
    $.infinityController.openCard = () => {
        const accept = $('#infinity_call_accept');
        $('#infinity-incoming-call').modal('hide');
        window.open('/debtors/debtorcard/' + accept.data('debtorid'));
        
        $.post('/debtors/infinity/closingModals', {
            user_extension: accept.data('extension')
        }).done(function() {
            
        });
        /*
        $.post('/infinity/accept', {
            fio: accept.data('fio'),
            number: accept.data('number'),
            money: accept.data('money'),
            call_id: accept.data('call'),
            user_extension: accept.data('extension'),
        })
            .done((data) => {
                window.open('/reports/phonecall?show_id=' + data.phone_call_id
                    + '&id_teleport_from_call=' + accept.data('teleport')
                    + '&passport_series_from_call=' + accept.data('passport-series')
                    + '&passport_number_from_call=' + accept.data('passport-number')
                    + '&lead_id_from_call=' + accept.data('lead')
                    + '&fio_from_call=' + accept.data('fio')
                    + '&phone_from_call=' + accept.data('number'), '_blank');
            })
            .always(() => {
                $.app.blockScreen(false);
            })*/
    };
    $.infinityController.closingModalsInit = (userExtension) => {
        window.Echo.channel('infinity_calls_' + userExtension)
            .listen('closing_modals', (data) => {
                $('#infinity-incoming-call').modal('hide');
            });
    };
})(jQuery);
