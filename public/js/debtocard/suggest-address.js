$(document).ready(function () {
    let addressesFias;
    let dataUpdate = {};
    $("body").click(function (e) {
        if ($(e.target).attr('id') === 'checkAddressReg') {
            $('#address_reg').show();
        } else {
            $('#address_reg').hide();
        }
    });

    function createOptionsComponent(suggestions = []) {
        let html = ``;
        let i = 0;
        addressesFias = [];
        for (let suggest of suggestions) {
            html += `<li class="list-group-item" code="${i}" value="${suggest.unrestricted_value}">
                        ${suggest.unrestricted_value}
                     </li>`;
            addressesFias.push(suggest.data);
            i++;
        }
        return html;
    }

    function fillAddress(suggestion, reg) {

        const fullAddress = [{
            keyRegAddress: 'zip',
            keyFactAddress: 'fact_zip',
            valueDadata: suggestion.postal_code
        }, {
            keyRegAddress: 'address_region',
            keyFactAddress: 'fact_address_region',
            valueDadata: suggestion.region_with_type
        }, {
            keyRegAddress: 'address_district',
            keyFactAddress: 'fact_address_district',
            valueDadata: suggestion.area_with_type
        }, {
            keyRegAddress: 'address_city',
            keyFactAddress: 'fact_address_city',
            valueDadata: suggestion.city_with_type
        }, {
            keyRegAddress: 'address_city1',
            keyFactAddress: 'fact_address_city1',
            valueDadata: suggestion.settlement_with_type
        }, {
            keyRegAddress: 'address_street',
            keyFactAddress: 'fact_address_street',
            valueDadata: suggestion.street_with_type
        }, {
            keyRegAddress: 'address_house',
            keyFactAddress: 'fact_address_house',
            valueDadata: suggestion.house
        }, {
            keyRegAddress: 'address_building',
            keyFactAddress: 'fact_address_building',
            valueDadata: suggestion.block
        }, {
            keyRegAddress: 'address_apartment',
            keyFactAddress: 'fact_address_apartment',
            valueDadata: suggestion.flat
        }, {
            keyRegAddress: 'okato',
            keyFactAddress: 'fact_okato',
            valueDadata: suggestion.okato
        }, {
            keyRegAddress: 'oktmo',
            keyFactAddress: 'fact_oktmo',
            valueDadata: suggestion.oktmo
        }, {
            keyRegAddress: 'fias_code',
            keyFactAddress: 'fact_fias_code',
            valueDadata: suggestion.fias_code
        }, {
            keyRegAddress: 'fias_id',
            keyFactAddress: 'fact_fias_id',
            valueDadata: suggestion.fias_id
        }, {
            keyRegAddress: 'kladr_id',
            keyFactAddress: 'fact_kladr_id',
            valueDadata: suggestion.kladr_id
        }
        ];

        for (let address of fullAddress) {
            if (reg) {
                document.getElementById(address.keyRegAddress).value = address.valueDadata;
                dataUpdate[address.keyRegAddress] = address.valueDadata;
            } else {
                document.getElementById(address.keyFactAddress).value = address.valueDadata;
                dataUpdate[address.keyFactAddress] = address.valueDadata;
            }
        }
    }

    document.getElementById("checkAddressReg").onclick = function () {
        $.post($.app.url + '/debtors/suggests', {
            address: $('textarea[name="address_reg"]').val()
        })
            .done(function (response) {
                $("#address_reg").html(createOptionsComponent(response));
                $('#address_reg').show();
            });
    }

    document.getElementById("checkAddressFact").onclick = function () {
        $.post($.app.url + '/debtors/suggests', {
            address: $('textarea[name="address_fact"]').val()
        })
            .done(function (response) {
                $("#address_fact").html(createOptionsComponent(response));
                $('#address_fact').show();
            });
    }

    $(document).on('click', '#address_reg li', function () {
        $('#input_address_reg').val($(this).attr('value'));
        $('#address_reg').hide(150);
        let addressFias = addressesFias[$(this).attr('code')];
        fillAddress(addressFias, true);
    });
    $(document).on('click', '#address_fact li', function () {
        $('#input_address_fact').val($(this).attr('value'));
        $('#address_fact').hide(150);
        let addressFias = addressesFias[$(this).attr('code')];
        fillAddress(addressFias, false);
    });


    const updatePassport = (customerId, passportId) => {
        console.log(dataUpdate);
        if (customerId !== undefined && passportId !== undefined) {
            $.post($.app.url + '/ajax/customers/' + customerId + '/passports/' + passportId, dataUpdate)
                .done(function (response) {
                    console.log(response)
                });
        } else {
            alert('Не удалось определить customer или passport');
        }
    }

    window.updatePassport = updatePassport;
});