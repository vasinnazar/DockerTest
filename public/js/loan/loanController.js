(function ($) {
    $.loanCtrl = {};
    $.loanCtrl.init = function () {
        $('#loanEditForm [name="in_cash"]').change(function () {
            $('#cardInputHolder').toggleClass('hidden', $(this).val());
        });
        $('#loanEditForm [name="card_number"]').mask('0000000000000', {reverse: false}).focus(function () {
            if ($(this).val() == '') {
                $(this).val('2700');
            }
        });
        $('#getLastCardBtn').click(function(){
            if($('#loanEditForm [name="last_card_number"]').val() == ''){
                alert('Карты на клиенте нет');
                return false;
            }
            $('#loanEditForm [name="card_number"]').val($('#loanEditForm [name="last_card_number"]').val());
            $('#loanEditForm [name="secret_word"]').val($('#loanEditForm [name="last_secret_word"]').val());
            return false;
        });
        $('#addCardBtn').click(function(){
            $.app.openCardFrame();
            return false;
        });
        $('#createPromocodeBtn').click(function(){
            var $btn = $(this);
            $.post(armffURL+'ajax/promocodes/create',{loan_id:$('#loanEditForm [name="id"]').val()}).done(function(data){
                if(data){
                    $('#loanEditForm [name="promocode_id"]').val(data.id);
                    $('#loanEditForm [name="promocode_number"]').fadeIn().val(data.number);
                    $btn.hide();
                }
            });
            return false;
        });
    };
})(jQuery);