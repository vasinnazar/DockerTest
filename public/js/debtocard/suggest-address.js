$(document).ready(function () {
    function createOptionsComponent (suggestions = []) {
        let html = ``;
        for (let suggest of suggestions) {
            html += `<li class="list-group-item" value="${suggest.value}">${suggest.value}</li>`;
        }
        return html;
    }
    $(document).on('click', '#checkAddressReg', function() {
        $.post($.app.url + '/debtors/suggests', {
            address: $('textarea[name="address_reg"]').val()
        })
            .done(function(response) {
                console.log(response)
                $("#address_reg").html(createOptionsComponent(response));
                $('#address_reg').show();
        });
    });
    $(document).on('click', '#checkAddressFact', function() {
        $.post($.app.url + '/debtors/suggests', {
            address: $('textarea[name="address_fact"]').val()
        })
            .done(function(response) {
                $("#address_fact").html(createOptionsComponent(response));
                $('#address_fact').show();
            });
    });
    $(document).on('click','#address_reg li', function(){
        $('#input_address_reg').val($(this).attr('value'));
        $('#address_reg').hide(150);
    });
    $(document).on('click','#address_fact li', function(){
        $('#input_address_fact').val($(this).attr('value'));
        $('#address_fact').hide(150);
    });
});