function createOptionsComponent(idContainerList, suggestions = [], isFact = false) {
    const containerList = document.getElementById(idContainerList);
    containerList.innerHTML = '';
    const createNode = (text, data = null) => {
        const newNode = document.createElement('li');
        newNode.classList.add('list-group-item');
        newNode.innerText = text;
        if (data) {
            newNode.onclick = () => {fillAddress(text, data, isFact)};
        } else {
            newNode.style.pointerEvents = 'none';
        }
        containerList.appendChild(newNode);
    };

    if (!suggestions.length) {
        createNode('Не найдено');
    }
    suggestions.forEach(suggest => createNode(suggest.unrestricted_value, suggest.data));
}

function fillAddress(selectAddress, suggestion, isFact = false) {
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

    fullAddress.forEach(address => {
        const element = document.getElementById(isFact ?  address.keyFactAddress : address.keyRegAddress);
        element.value = address.valueDadata ?? '';
        element.readOnly = true;
        element.disabled = false;
    });
    document.getElementById(isFact ? 'address_fact' : 'address_reg').style.borderColor = "#58c689";
    document.getElementById(isFact ? 'address_fact' : 'address_reg').value = selectAddress;
}

function showInfoBlock(idElement, textInfo, styleInfo) {
    const divInfo = document.getElementById(idElement);
    divInfo.classList.add(styleInfo);
    divInfo.style.display = 'block';
    document.getElementById('textInfo').innerHTML = textInfo;
}

function updatePassport (event, csrfToken, customerId, passportId) {
    event.preventDefault();
    const values = new FormData(event.target);
    if (!customerId || !passportId) {
        showInfoBlock('infoBlock', 'Не удалось определить контрагента или паспорт', 'alert-danger');
        $('#changeAddress').modal('hide');
        return;
    }
    $.app.blockScreen(true);
    fetch($.app.url + '/ajax/customers/' + customerId + '/passports/' + passportId, {
        body: values,
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        }
    })
        .then(async (response) => {
            const body = await response.json();
            if (!response.ok || body.error) {
                $('#changeAddress').modal('hide');
                showInfoBlock('infoBlock', 'Не удалось обновить паспорт armId: ' + passportId, 'alert-danger');
            } else {
                $('#changeAddress').modal('hide');
                showInfoBlock('infoBlock', 'Паспорт сохранен, обновите страницу', 'alert-success');
            }
        })
        .catch(() => {
            $('#changeAddress').modal('hide');
            showInfoBlock('infoBlock', 'Не удалось обновить паспорт armId: ' + passportId, 'alert-danger');
        })
        .finally(() => {
            $.app.blockScreen(false);
        });
}
function showSuggestions(idInputAddress, idListAddress, isFact = false) {
    $.post($.app.url + '/debtors/suggests', {
        address: document.getElementById(idInputAddress).value
    })
        .done(function (response) {
            createOptionsComponent(idListAddress, response, isFact);
            $(`#${idListAddress}`).show();
        });
}

window.onload = function () {
    $("body").click(function (e) {
        if (document.getElementById('list_address_reg').style.display !== 'none') {
            $('#list_address_reg').hide();
        }
        if (document.getElementById('list_address_fact').style.display !== 'none') {
            $('#list_address_fact').hide();
        }
    });
};