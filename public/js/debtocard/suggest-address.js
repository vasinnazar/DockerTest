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
        document.getElementById('zip').innerText = suggestion.postal_code;
        document.getElementById('address_region').innerText = suggestion.region_with_type;
        document.getElementById('address_district').innerText = suggestion.area_with_type;
        document.getElementById('address_city').innerText = suggestion.city_with_type;
        document.getElementById('address_city1').innerText = suggestion.settlement_with_type;
        document.getElementById('address_street').innerText = suggestion.street_with_type;
        document.getElementById('address_house').innerText = suggestion.house;
        document.getElementById('address_building').innerText = suggestion.block;
        document.getElementById('address_apartment').innerText = suggestion.flat;
        document.getElementById('okato').innerText = suggestion.okato;
        document.getElementById('oktmo').innerText = suggestion.oktmo;
        document.getElementById('fias_code').innerText = suggestion.fias_code;
        document.getElementById('fias_id').innerText = suggestion.fias_id;
        document.getElementById('kladr_id').innerText = suggestion.kladr_id;
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

    const categoryViewForm = () => {
        console.log(46);
    }

    window.categoryViewForm = categoryViewForm;
});