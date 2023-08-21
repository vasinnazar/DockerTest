window.onload = function () {
    let addressesFias;
    let dataUpdate = {};

    function createOptionsComponent(suggestions = []) {
        let html = ``;
        let i = 0;
        addressesFias = [];
        if (suggestions.length == 0) {
            html = `<li class="list-group-item" style="pointer-events: none">Не найдено</li>`;
        }
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
            if (address.valueDadata == null) {
                address.valueDadata = '';
            }
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
            address: document.getElementById("address_reg").value
        })
            .done(function (response) {
                document.getElementById("list_address_reg").innerHTML = createOptionsComponent(response);
                $('#list_address_reg').show();
            });
    }

    document.getElementById("checkAddressFact").onclick = function () {
        $.post($.app.url + '/debtors/suggests', {
            address: document.getElementById("address_fact").value
        })
            .done(function (response) {
                document.getElementById("list_address_fact").innerHTML = createOptionsComponent(response);
                $('#list_address_fact').show();
            });
    }

    $(document).on('click', '#list_address_reg li', function () {
        $('#address_reg').val($(this).attr('value'));
        $('#list_address_reg').hide();
        let addressFias = addressesFias[$(this).attr('code')];
        fillAddress(addressFias, true);
    });
    $(document).on('click', '#list_address_fact li', function () {
        $('#address_fact').val($(this).attr('value'));
        $('#list_address_fact').hide();
        let addressFias = addressesFias[$(this).attr('code')];
        fillAddress(addressFias, false);
    });

    function showInfoBlock(idElement, textInfo, styleInfo) {
        const divInfo = document.getElementById(idElement);
        divInfo.classList.add(styleInfo);
        divInfo.style.display = 'block';
        document.getElementById('textInfo').innerHTML = textInfo;
    }
    const updatePassport = (customerId, passportId) => {
        console.log(dataUpdate);
        if (customerId !== undefined && passportId !== undefined) {
            $.post($.app.url + '/ajax/customers/' + customerId + '/passports/' + passportId, dataUpdate)
                .done(function (response) {
                    if (response.error) {
                        $('#changeAddress').modal('hide');
                        showInfoBlock('infoBlock', 'Не удалось обновить паспорт armId: ' + passportId, 'alert-danger');
                    } else {
                        $('#changeAddress').modal('hide');
                        showInfoBlock('infoBlock', 'Паспорт сохранен, обновите страницу', 'alert-success');
                    }
                });
        } else {
            showInfoBlock('infoForUser', 'Не удалось определить контрагента или паспорт', 'alert-danger');
        }
    }

    window.updatePassport = updatePassport;

    $("body").click(function (e) {
        if ($(e.target).attr('id') === 'checkAddressReg') {
            $('#list_address_reg').show();
        } else {
            $('#list_address_reg').hide();
        }
    });
    $("body").click(function (e) {
        if ($(e.target).attr('id') === 'checkAddressFact') {
            $('#list_address_fact').show();
        } else {
            $('#list_address_fact').hide();
        }
    });
};