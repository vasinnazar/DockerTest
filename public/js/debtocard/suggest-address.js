$(document).ready(function () {
    let addressesFias;
    $("body").click(function(e) {
        if($(e.target).attr('id') === 'checkAddressReg') {
            $('#address_reg').show();
        }
        else {
            $('#address_reg').hide();
        }
    });
    function createOptionsComponent (suggestions = []) {
        let html = ``;
        let i = 0;
        addressesFias = [];
        for (let suggest of suggestions) {
            html += `<li class="list-group-item" code="${i}" value="${suggest.value}">${suggest.value}</li>`;
            addressesFias.push(suggest.data);
            i++;
        }
        return html;
    }
    function fillAddressFields(suggestion) {
        document.getElementsByName('zip')[0].value = suggestion.postal_code;
        document.getElementsByName('address_region')[0].value = suggestion.region_with_type;
        document.getElementsByName('address_district')[0].value = suggestion.area_with_type;
        document.getElementsByName('address_city')[0].value = suggestion.city_with_type;
        document.getElementsByName('address_city1')[0].value = suggestion.settlement_with_type;
        document.getElementsByName('address_street')[0].value = suggestion.street_with_type;
        document.getElementsByName('address_house')[0].value = suggestion.house;
        document.getElementsByName('address_building')[0].value = suggestion.block;
        document.getElementsByName('address_apartment')[0].value = suggestion.flat;
        document.getElementsByName('okato')[0].value = suggestion.okato;
        document.getElementsByName('oktmo')[0].value = suggestion.oktmo;
        document.getElementsByName('fias_code')[0].value = suggestion.fias_code;
        document.getElementsByName('fias_id')[0].value = suggestion.fias_id;
        document.getElementsByName('kladr_id')[0].value = suggestion.kladr_id;
    }
    $(document).on('click', '#checkAddressReg', function() {
        $.post($.app.url + '/debtors/suggests', {
            address: $('textarea[name="address_reg"]').val()
        })
            .done(function(response) {
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
        let addressFias = addressesFias[$(this).attr('code')];
        console.log(addressFias);
        fillAddressFields(addressFias);
    });
    $(document).on('click','#address_fact li', function(){
        $('#input_address_fact').val($(this).attr('value'));
        $('#address_fact').hide(150);
    });
    function fullAddress()
    {
        const formElement = document.getElementById('addressCustomer');
        const inputForm = formElement.getElementsByTagName("input");
        let address = {};
        for (let input of inputForm) {
            address[input.getAttribute('name')] = input.getAttribute('value');
        }
        return address;
    }

    const updatePassport = (customerId, passportId) => {
        if (customerId !== undefined && passportId !== undefined) {
            $.post($.app.url + '/ajax/customers/' + customerId + '/passports/' + passportId, {
                data: fullAddress()
            })
                .done(function(response) {
                    console.log(response)
                });
        }
        else {
            alert('Не удалось определить customer или passport');
        }
    }

    window.updatePassport = updatePassport;
});