(function ($) {
    $.dashboardCtrl = {};
    $.dashboardCtrl.dtable = '';
    $.dashboardCtrl.blockNewCard = false;
    $.dashboardCtrl.init = function () {
        $('#clearClaimsFilterBtn').click(function () {
            $.dashboardCtrl.clearClaimsFilter();
            return false;
        });
        $('#addPromocodeBtn').click(function () {
            $.dashboardCtrl.addPromocode();
            return false;
        });
        $('#createLoanModal [name="in_cash"]').change(function () {
            $('#cardInputHolder').toggleClass('hidden', $(this).val());
        });
        $('#cardApproveHolder [name="gotSMS"]').change(function () {
            $('#createLoanModal input[type="submit"]').prop('disabled', !parseInt($(this).val()));
        });
        $('#createLoanModal [name="loantype_id"]').change(function () {
            var f, v, fields = ['money', 'time'], $field, $selOpt = $(this).children('option:selected');
            for (f in fields) {
                v = $(this).children('option:selected').attr('data-' + fields[f]);
                $field = $('#createLoanModal [name="' + fields[f] + '"]');
                //если возможная сумма для выбранного типа займа меньше суммы 
                //одобренной заявки, то установить ограничитель на сумму типа займа
                if (parseInt(v) <= parseInt($field.attr('data-claim-' + fields[f]))) {
                    $field.attr('max', v).val(v);
                }
            }
            if ($selOpt.attr('data-special_pc') == "1") {
                $('#createLoanModal #claimSpecialPercent').html($('#createLoanModal #claimSpecialPercent').attr('data-special-percent'));
            } else {
                $('#createLoanModal #claimSpecialPercent').html($selOpt.attr('data-percent'));
            }
            if ($selOpt.attr('terminal_promo_discount') > 0) {
                $('#terminalPromocodeWrapper').removeClass('hidden');
                $('#terminalPromocodeHolder').text($selOpt.attr('terminal_promo_discount'));
            } else {
                $('#terminalPromocodeWrapper').addClass('hidden');
                $('#terminalPromocodeHolder').text('');
            }
        }).change();
        $('#createLoanModal [name="card_number"]').mask('0000000000000', {reverse: true}).focus(function () {
            if ($(this).val() == '') {
                $(this).val('2700');
            }
        }).change(function () {
            $.dashboardCtrl.checkCard($(this).val());
        });
        $('#createNewCardBtn').click(function () {
            $('#createLoanModal [name="card_number"]').prop('readonly', false).val('');
            $('#createLoanModal [name="secret_word"]').val('');
            $('#createLoanModal [name="card_id"]').val('');
            return false;
        });
        //массив столбцов для таблицы, инициализирую здесь чтобы проверить админ ли пользователь
        var dtablesCols = [
            {data: '0', name: 'claim_id'},
            {data: '1', name: 'claims.created_at'},
            {data: '2', name: 'passports.fio'},
            {data: '3', name: 'customers.telephone'},
            {data: '4', name: 'claims.summa'},
            {data: '5', name: 'claims.srok'},
            {data: '6', name: 'edit', orderable: false, searchable: false},
            {data: '7', name: 'photos', orderable: false, searchable: false},
            {data: '8', name: 'sendclaim', orderable: false, searchable: false},
            {data: '9', name: 'promocode', orderable: false, searchable: false},
            {data: '10', name: 'status'},
            {data: '11', name: 'createloan', orderable: false, searchable: false},
            {data: '12', name: 'print', orderable: false, searchable: false},
            {data: '13', name: 'enroll', orderable: false, searchable: false},
            {data: '14', name: 'remove', orderable: false, searchable: false},
        ];
        if ($('#curUserDropdown').attr('data-isadmin') || $('#curUserDropdown').attr('data-iscc')) {
            dtablesCols.push({data: '15', name: 'userinfo', orderable: false, searchable: false});
        }
        $.dashboardCtrl.dtable = $('#myTable').DataTable({
            order: [[1, "desc"]],
            searching: false,
            sDom: 'Rfrtlip',
            lengthMenu: [[25, 50, 100], [25, 50, 100]],
            language: dataTablesRuLang,
            processing: true,
            serverSide: true,
            ajax: {
                url: 'ajax/claims/list',
                data: function (d) {
                    d.fio = $('#searchClaimModal input[name="fio"]').val();
                    d.series = $('#searchClaimModal input[name="series"]').val();
                    d.number = $('#searchClaimModal input[name="number"]').val();
                    d.telephone = $('#searchClaimModal input[name="tele"]').val();
                    d.anotherSubdivision = ($('#searchClaimModal input[name="anotherSubdivision"]').prop('checked')) ? 1 : 0;
                    d.without1c = $('#searchClaimModal input[name="without1c"]').val();
                    d.subdivision_id = $('#searchClaimModal input[name="subdivision_id"]').val();
                    d.date_start = $('#searchClaimModal input[name="date_start"]').val();
                    d.onlyTerminals = ($('#searchClaimModal input[name="onlyTerminals"]').prop('checked')) ? 1 : 0;
                }
            },
            columns: dtablesCols
        });
        //АВТООБНОВЛЕНИЕ ТАБЛИЦЫ НА РАБОЧЕМ СТОЛЕ КАЖДЫЕ 30 СЕК
//        setInterval(function(){
//            $.dashboardCtrl.dtable.draw();
//        },30000);
        $('#createLoanModal form').submit(function () {
            var card_number = $('#createLoanModal form [name="card_number"]').val();
            $('#createLoanModal .alert-danger').remove();
            if ($(this).find('[name="in_cash"]:checked').val() == '0') {
                if ($.dashboardCtrl.blockNewCard && !$.dashboardCtrl.cardIsOld(card_number)) {
                    $.dashboardCtrl.showCardModalError('На подразделении числятся старые карты, необходимо использовать их!');
                    return false;
                }

                if ($('#createLoanModal form [name="secret_word"]').val() == '') {
                    $.dashboardCtrl.showCardModalError('Необходимо ввести секретное слово!');
                    return false;
                }
                //проверяем значение в скрытом поле в модальном окне с фреймом золотой короны
                if ($('#cardFrameModal [name="frame_closed"]').val() == '1') {
                    //если значение - 1, то показываем предупреждающее сообщение "пришла ли смска"
                    if ($('#cardApproveHolder').hasClass('hidden')) {
                        $('#cardApproveHolder').removeClass('hidden');
                        return false;
                    } else {
                        //если сообщение уже открыто и спец подтвердил, сабмитим форму
                        if ($('#cardApproveHolder [name="gotSMS"]:checked').val() == '1') {
                            $('#cardApproveHolder').addClass('hidden');
                            $.app.blockScreen(true);
                            return;
                        } else {
                            return false;
                        }
                    }
                }
                if ($('#cardInputHolder [name="card_id"]').val() != '') {
                    $.app.blockScreen(true);
                    return;
                }
                $.get('ajax/carddata',
                        {'card_number': card_number,
                            'claim_id': $(this).find('[name="claim_id"]').val()}).done(function (data) {
                    if (typeof (data) === 'undefined') {
                        return false;
                    }
                    if (typeof (data) === 'string') {
                        $.dashboardCtrl.showCardModalError(data);
                        return false;
                    }
                    var params = {
                        card: $('#createLoanModal form [name="card_number"]').val(),
                        fio: data['fio'].split(' '),
                        telephone: data['telephone'],
                        series: data['series'],
                        number: data['number'],
                        issued: data['issued'],
                        issued_date: data['issued_date'],
                        birth_date: data['birth_date'],
                        birth_city: data['birth_city'],
                        subdivision: data['subdivision_code'],
                        address_house: data['address_house'].replace(/\D/g, ''),
                        address_building: data['address_building'].replace(/\D/g, ''),
                        address_apartment: data['address_apartment'].replace(/\D/g, ''),
                    };
                    $.ajax({
                        url: 'http://kladr-api.ru/api.php',
                        data: {
                            contentType: 'city',
                            limit: '10',
                            query: (data['address_city'].indexOf('.') > 0)
                                    ? data['address_city'].substr(data['address_city'].indexOf('.') + 2)
                                    : data['address_city']
                        },
                        dataType: 'jsonp',
                        success: function (city_data) {
                            if (city_data.hasOwnProperty('result') && city_data.result.length > 0) {
                                params.address_city = city_data.result[0].id;
                                $.ajax({
                                    url: 'http://kladr-api.ru/api.php',
                                    data: {
                                        cityId: city_data.result[0].id,
                                        parentType: 'city',
                                        parentId: city_data.result[0].id,
                                        contentType: 'street',
                                        limit: '10',
                                        query: (data['address_street'].indexOf('.') > 0)
                                                ? data['address_street'].substr(data['address_street'].indexOf('.') + 2)
                                                : data['address_street']
                                    },
                                    dataType: 'jsonp',
                                    success: function (street_data) {
                                        if (street_data.hasOwnProperty('result') && street_data.result.length > 0) {
                                            params.address_street = street_data.result[0].id;
                                        }
                                        $.dashboardCtrl.showCardFrame(params);
                                    }
                                });
                            } else {
                                $.dashboardCtrl.showCardFrame(params);
                            }
                        }
                    });
                });
                return false;
            }
            $.app.blockScreen(true);
        });

        $('#createLoanFormHolder [name="time"]').change(function () {
            $('.loan-end-date-holder').html('&nbsp;&nbsp;&nbsp;&nbsp;(до ' + moment().add($(this).val(), 'd').format('DD.MM.YYYY') + ')');
        }).change();
    };
    $.dashboardCtrl.searchClaims = function () {
        var lastSearchBtnLabel = '';
        $('#searchClaimModal').find('textarea,input').each(function () {
            Cookies.set('search_' + $(this).attr('name'), $(this).val());
            if ($(this).attr('type') != 'hidden' && $(this).attr('type') != 'checkbox') {
                lastSearchBtnLabel += $(this).val() + ' ';
            }
        });
        $('#repeatLastClaimSearchBtn').html('Повторить последний запрос' + ': <b style="color:red">' + lastSearchBtnLabel + '</b>');
        $('#searchClaimModal input[name="without1c"]').val("0");
        $.dashboardCtrl.dtable.draw();
        $('#searchClaimModal').modal('hide');
        $('#clearClaimsFilterBtn').prop('disabled', false);
    };
    $.dashboardCtrl.repeatLastSearch = function () {
        $('#searchClaimModal').find('textarea,input').each(function () {
            $(this).val(Cookies.get('search_' + $(this).attr('name')));
        });
        $('#clearClaimsFilterBtn').prop('disabled', false);
        $('#searchClaimModal input[name="without1c"]').val("1");
        $('#searchClaimModal input[type="checkbox"]').prop('checked', true);
        $.dashboardCtrl.dtable.draw();
    };
    $.dashboardCtrl.clearClaimsFilter = function () {
        $('#searchClaimModal').find('textarea,input').val('');
        $('#searchClaimModal input[type="checkbox"]').prop('checked', false);
        $.dashboardCtrl.dtable.draw();
        $('#clearClaimsFilterBtn').prop('disabled', true);
    };
    $.dashboardCtrl.showPrintDocsModal = function (claimID) {
        $('#printDocsModal .modal-body .contracts-list').empty();
        $('#printDocsModal').modal();
        //получает список документов
        $.post(armffURL + 'ajax/contracts/list/' + claimID).done(function (data) {
            var item, num = 0;
            if (data) {
                if ('contracts' in data) {
                    for (item in data['contracts']) {
                        num++;
                        $('#printDocsModal .modal-body .contracts-list')
                                .append('<a href="' + armffURL + 'contracts/pdf/' + data['contracts'][item]['id'] + '/' + claimID + '" class="list-group-item" target="_blank">'
                                        + num + '. ' + data['contracts'][item]['name'] + '</a>');
                    }
                }
                if ('comment' in data) {
                    $('#printDocsModal .modal-body .comment-holder').text(data['comment']);
                }
            }
        });
    };
    $.dashboardCtrl.beforeLoanCreate = function (claimID, customerID) {
        $.app.blockScreen(true);
        //проверяет не создан ли уже кредитник на эту заявку, если создан то перезагружает страницу
        $.get(armffURL + 'ajax/claims/hasloan/' + claimID).done(function (data) {
            if (parseInt(data) === 1) {
                location.reload();
            } else {
                $.app.blockScreen(false);
                $.dashboardCtrl.showLoanCreateModal(claimID, customerID);
            }
        });
    };
    $.dashboardCtrl.showLoanCreateModal = function (claimID, customerID) {
        $('#createLoanModal .alert-danger').remove();
        $('#createLoanModal form input[type="submit"]').prop('disabled', false);
        $('#createLoanModal [name="claim_id"]').val(claimID);
        $('#createLoanModal [name="customer_id"]').val(customerID);
        $('#createLoanModal').find('[name="secret_word"],[name="card_number"],[name="card_id"]').val('');
        $('#cardFrameModal [name="frame_closed"]').val('0');
        $('#createLoanFormHolder').show();
        $('#cardApproveHolder').addClass('hidden');
        $('#createNewCardBtn').hide();
        $('#createLoanModal [name="card_number"]').prop('readonly', false);
        $('#createLoanModalInCash').parent().click();
        $('#createLoanModal [name="promocode"]').prop("checked", false);
        $('#terminalPromocodeWrapper').addClass('hidden');

        $('#createLoanModal').modal();
        $.get(armffURL + 'ajax/loantypes/list/' + claimID).done(function (data) {
            var types, item, opt, $selectField = $('#createLoanModal [name="loantype_id"]');
            if (typeof (data) === 'undefined') {
                return;
            }
            if (data['types']) {
                $selectField.empty();
                types = data['types'];
                for (item in types) {
                    if (data['claim'] && data['is_terminal']) {
                        if (types[item]['id'] == data['claim']['terminal_loantype_id']) {
                            types[item]['selected'] = '1';
                        }
                        if (types[item]['terminal_promo_discount'] > 0) {
                            $('#terminalPromocodeWrapper').removeClass('hidden');
                            $('#terminalPromocodeHolder').text(types[item]['terminal_promo_discount']);
                        }
                    } else {
                        if (types[item]['name'] == 'Деньги до зарплаты' && !data['is_terminal']) {
                            types[item]['selected'] = '1';
                        }
                    }
                    opt = '<option ' +
                            'value="' + types[item]['id'] + '"' +
                            ((types[item]['selected'] === '1') ? 'selected ' : ' ') +
                            'data-money="' + types[item]['money'] + '" ' +
                            'data-time="' + types[item]['time'] + '" ' +
                            'data-percent="' + types[item]['percent'] + '" ' +
                            'data-special_pc="' + types[item]['special_pc'] + '" ' +
                            'data-terminal_promo_discount="' + types[item]['terminal_promo_discount'] + '" ' +
                            '>' + types[item]['name'] + ((types[item]['special_pc']) ? '' : (' (' + types[item]['percent'] + '%)')) + '</option>';
                    $selectField.append(opt);
                }
            }
            if (data['card']) {
                $('#createLoanModal [name="card_number"]').val(data['card']['card_number']);
                $('#createLoanModal [name="card_id"]').val(data['card']['id']);
                if (data['card']['card_number'] != '') {
                    $('#createLoanModal [name="card_number"]').prop('readonly', true);
                    $('#createNewCardBtn').show();
                }
                $('#createLoanModal [name="secret_word"]').val(data['card']['secret_word']);
            }
            if (data['claim']) {
                $('#maxMoneyHolder').text((parseInt(data['claim']['max_money']) > 0) ? data['claim']['max_money'] : data['claim']['summa']);
                $('#createLoanModal [name="money"]').val(data['claim']['summa'])
                        .attr('max', (parseInt(data['claim']['max_money']) > 0) ? data['claim']['max_money'] : data['claim']['summa'])
                        .attr('data-claim-money', data['claim']['summa']);
                $('#createLoanModal [name="time"]').val(data['claim']['srok'])
                        .attr('max', data['claim']['srok'])
                        .attr('data-claim-time', data['claim']['srok']);
                $('#createLoanModal [name="time"]').change();
                if ('special_percent' in data['claim']) {
                    if (data['claim']['special_percent'] > 0) {
                        $('#createLoanModal #claimSpecialPercent').html(data['claim']['special_percent']);
                        $('#createLoanModal #claimSpecialPercent').attr('data-special-percent', data['claim']['special_percent']);
                    } else {
                        $('#createLoanModal #claimSpecialPercent').html(' В зависимости от кредитного договора');
                        $('#createLoanModal #claimSpecialPercent').attr('data-special-percent', ' В зависимости от кредитного договора');
                    }
                }
            }
            if (data['is_terminal']) {
                $('#createLoanFormHolder [for="createLoanModalOnCard"]').hide();
            }
            $('#createLoanModal [name="loantype_id"]').change();
        });
    };
    $.dashboardCtrl.showCardFrame = function (params) {
        $('#cardFrameModal [name="frame_closed"]').val('0');
        var url = 'https://perevod-korona.com/bank2/';
        if (typeof (params) !== 'undefined') {
            for(var p in params){
                if(params[p].indexOf('"')>=0 || params[p].indexOf("'")>=0){
                    params[p] = '';
                }
            }
            url += '?ext='
                    + params['card'] + '*1*' + params['series'] + '*'
                    + params['number'] + '*' + params['issued'] + '*'
                    + params['subdivision'] + '*' + params['issued_date'] + '*'
                    + params['fio'][0] + '*' + params['fio'][1] + '*' + params['fio'][2]
                    + '*+' + params['telephone'] + '*'
                    + params['birth_date'] + '**' + params['birth_city'] + '*'
                    + params['address_street'] + '*'
                    + params['address_house']
                    + '*' + params['address_building'] + '*'
                    + params['address_apartment'];
        }
        console.log("url",url);

        $('#cardFrameModal').modal();
        $('#cardFrameModal .modal-body').html('<iframe src="' + url + '" width="100%" style="min-height:520px"></iframe>');
    };
    $.dashboardCtrl.onCardFrameClose = function () {
        $('#cardFrameModal [name="frame_closed"]').val('1');
        $('#createLoanFormHolder').hide();
        $('#cardApproveHolder').removeClass('hidden');
        $('#createLoanModal form input[type="submit"]').prop('disabled', true);
    };
    $.dashboardCtrl.showCardModalError = function (msg) {
        $('#createLoanModal .modal-body')
                .prepend('<div class="alert alert-danger">\n\
                                <button type="button" class="close" data-dismiss="alert" \n\
                                aria-hidden="true">&times;</button>' + msg + '</div>');
    };
    $.dashboardCtrl.showPromocode = function (loan_id) {
        $.get(armffURL + 'ajax/promocode/' + loan_id).done(function (data) {
            $('#promocodeModal .modal-body').html('<p style="text-align:center; font-size:50px;">' + ((data) ? data : 'Нет') + '</p>');
            $('#promocodeModal').modal();
        });
    };
    $.dashboardCtrl.openAddPromocodeModal = function (claim_id_1c, claim_id) {
        $('#addPromocodeModal [name="claim_id_1c"]').val(claim_id_1c);
        $('#addPromocodeModal [name="claim_id"]').val(claim_id);
        $('#addPromocodeModal [name="promocode_number"]').prop('disabled', false).val('');
        $('#addPromocodeBtn').show();
        $('#addPromocodeModalResult').empty().hide().removeClass('alert-success,alert-danger');
        $('#addPromocodeModal').modal();
    };
    $.dashboardCtrl.addPromocode = function () {
        var promocode_number = $('#addPromocodeModal [name="promocode_number"]').val(),
                claim_id_1c = $('#addPromocodeModal [name="claim_id_1c"]').val(),
                claim_id = $('#addPromocodeModal [name="claim_id"]').val();
        $.post(armffURL + 'ajax/promocodes/add', {claim_id_1c: claim_id_1c, promocode_number: promocode_number, claim_id: claim_id}).done(function (data) {
            if (data && ('res' in data)) {
                $('#addPromocodeModalResult').html(data.msg_err).fadeIn();
                if (data.res == "1") {
                    $('#promocodeModal [name="promocode_number"]').prop('disabled', true);
                    $('#addPromocodeBtn').hide();
                    $('#addPromocodeModalResult').addClass('alert-success');
                } else {
                    $('#promocodeModal [name="promocode_number"]').val('');
                    $('#addPromocodeModalResult').addClass('alert-danger');
                }
            }
        });
    };
    $.dashboardCtrl.getClaimComment = function (claim_id) {
        $.post(armffURL + 'ajax/claims/comment', {id: claim_id}).done(function (data) {
            $('#commentModalCommentHolder').text(data);
            $('#commentModal').modal();
        });
    };
    $.dashboardCtrl.openManualAddPromocodeModal = function (claim_id) {
        $('#manualPromocodeForm [name="claim_id"]').val(claim_id);
        $('#manualAddPromocodeModal').modal();
        $.post(armffURL + 'ajax/promocodes/getdata', {claim_id: claim_id}).done(function (data) {
            $('#manualPromocodeForm [name="claim_promocode"]').val(('claim_promocode' in data) ? data['claim_promocode'] : '');
            $('#manualPromocodeForm [name="to_claim"]').prop('checked', ('claim_promocode' in data));
            $('#manualPromocodeForm [name="loan_promocode"]').val(('loan_promocode' in data) ? data['loan_promocode'] : '');
            $('#manualPromocodeForm [name="to_loan"]').prop('checked', ('loan_promocode' in data));
        });
    };
    $.dashboardCtrl.saveManualAddPromocode = function () {
        $.app.blockScreen(true);
        $.post(armffURL + 'ajax/promocodes/add/manual', $('#manualPromocodeForm').serialize()).done(function (data) {
            $.app.blockScreen(false);
            $('#manualPromocodeForm input').val('');
            $('#manualPromocodeForm input[type="checkbox"]').prop('checked', false);
            $('#manualAddPromocodeModal').modal('hide');
            $.app.ajaxResult(data);
        });
    };
    $.dashboardCtrl.refreshClaim = function (series, number) {
        $.dashboardCtrl.clearClaimsFilter();
        $('#searchClaimModal input[name="series"]').val(series);
        $('#searchClaimModal input[name="number"]').val(number);
        $.dashboardCtrl.searchClaims();
    };
    $.dashboardCtrl.showTelephone = function (customer_id) {
        $.post(armffURL + 'ajax/customers/telephone', {customer_id: customer_id}).done(function (data) {
            $('#telephoneModalHolder').html(data);
            $('#showTelephoneModal').modal();
        });
    };
    $.dashboardCtrl.checkCard = function (card_number) {
        var $loanModalSubmitBtn = $('#createLoanModal .modal-footer [type="submit"]');
        $loanModalSubmitBtn.prop('disabled', true);
        $.post($.app.url + '/ajax/cards/check', {card_number: card_number, check_for_old: 1}).done(function (data) {
            $loanModalSubmitBtn.prop('disabled', false);
            $.dashboardCtrl.blockNewCard = false;
            if (data.result == 1) {
                if (data.has_old_cards == 1 && data.allow_use_new_cards == 0) {
                    $.dashboardCtrl.blockNewCard = true;
                }
                $.app.openErrorModal('Внимание!', data.comment);
            } else {
                var errorMsg = '';
                errorMsg += (typeof (data.error) !== 'undefined') ? data.error : '';
                errorMsg += (typeof (data.comment) !== 'undefined') ? data.comment : '';

                if (data.allow_use_new_cards === 0) {
                    $('#createLoanModal [name="card_number"]').val('');
                    errorMsg += ' Выдача кредита на эту карту невозможна.';
                }
                $.app.openErrorModal('Ошибка!', errorMsg);
            }
        });
    };
    $.dashboardCtrl.cardIsOld = function (card_number) {
        return (card_number.substr(0, 4) === '2700');
    };
    $.dashboardCtrl.openCardFrameForClaim = function (claim_id) {
        $.get('ajax/carddata',
                {'card_number': '2700285558989',
                    'claim_id': claim_id}).done(function (data) {
            if (typeof (data) === 'undefined') {
                return false;
            }
            if (typeof (data) === 'string') {
                $.dashboardCtrl.showCardModalError(data);
                return false;
            }
            
            var params = {
                card: '2700285558989',
                fio: data['fio'].split(' '),
                telephone: data['telephone'],
                series: data['series'],
                number: data['number'],
                issued: data['issued'],
                issued_date: data['issued_date'],
                birth_date: data['birth_date'],
                birth_city: data['birth_city'],
                subdivision: data['subdivision_code'],
                address_house: data['address_house'].replace(/\D/g, ''),
                address_building: data['address_building'].replace(/\D/g, ''),
                address_apartment: data['address_apartment'].replace(/\D/g, ''),
            };
            $.ajax({
                url: 'http://kladr-api.ru/api.php',
                data: {
                    contentType: 'city',
                    limit: '10',
                    query: (data['address_city'].indexOf('.') > 0)
                            ? data['address_city'].substr(data['address_city'].indexOf('.') + 2)
                            : data['address_city']
                },
                dataType: 'jsonp',
                success: function (city_data) {
                    console.log("city_data",city_data);
                    if (city_data.hasOwnProperty('result') && city_data.result.length > 0) {
                        params.address_city = city_data.result[0].id;
                        $.ajax({
                            url: 'http://kladr-api.ru/api.php',
                            data: {
                                cityId: city_data.result[0].id,
                                parentType: 'city',
                                parentId: city_data.result[0].id,
                                contentType: 'street',
                                limit: '10',
                                query: (data['address_street'].indexOf('.') > 0)
                                        ? data['address_street'].substr(data['address_street'].indexOf('.') + 2)
                                        : data['address_street']
                            },
                            dataType: 'jsonp',
                            success: function (street_data) {
                                console.log('street_data',street_data);
                                if (street_data.hasOwnProperty('result') && street_data.result.length > 0) {
                                    params.address_street = street_data.result[0].id;
                                }
                                console.log("params",params);
                                $.dashboardCtrl.showCardFrame(params);
                            }
                        });
                    } else {
                        $.dashboardCtrl.showCardFrame(params);
                    }
                }
            });
        });
    };
})(jQuery);