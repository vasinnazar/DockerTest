(function ($) {
    $.app = {
        url: $('meta[name="app_url"]').attr("content"),
        is_admin: $('meta[name="admin"]').attr("content"),
        subdivision_id: $('meta[name="subdivision_id"]').attr("content"),
        user_extension: $('meta[name="user_extension"]').attr("content"),
    };
    $.app.init = function () {
        $.app.last_msg_datetime = $('.chat-window .chat-msg:first .msg-datetime').attr('data-msg-datetime');
        //блокировать все кнопки и ссылки от двойного нажатия
//        $('form').bind('submit', function () {
//            $(this).find('button,input[type="button"]').prop('disabled', true);
//        });
        $('a').bind('click', function () {
            if ($(this).attr('href') != '#') {
//                $(this).addClass('disabled');
            }
            //спрашивать разрешение на удаление при нажатии кнопки с крестиком
            if ($(this).children('span').hasClass('glyphicon-remove')) {
                if (!confirm('Вы действительно хотите удалить?')) {
                    $(this).removeClass('disabled');
                    return false;
                }
            }
        });
        //перевод на следующий инпут по энтеру
        $('form').find('input,select,textarea').bind('keydown', function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                var inputs = $(this).closest('form').find('input,select,textarea,button');
                inputs.eq(inputs.index(this) + 1).select();
            }
        });
//        window.onerror = function () {
//            alert('На странице произошла ошибка, обновите страницу перед дальнейшей работой.');
//        };
        //предупреждение перед выходом со страницы
//        window.addEventListener("beforeunload", function (e) {
//            if (e.target.activeElement.href.replace($.app.url, '') == window.location.href) {
//                var confirmationMessage = "Внимание!";
//
//                (e || window.event).returnValue = confirmationMessage; //Gecko + IE
//                return confirmationMessage;                            //Webkit, Safari, Chrome
//            }
//        });
        $('.modal.open-on-load').each(function () {
            if ($(this).attr('id') === 'addWorkTimeModal') {
                $(this).modal({
                    backdrop: 'static',
                    show: true,
                    keyboard: false,
                });
            } else {
                $(this).modal('show');
            }
        });

        var dataGrid = new DataGridController($('#workTimeForm [name="reason"]').val(), {
            table: $("#workTimeReasonTable"),
            form: $('#workTimeForm'),
            dataField: $('#workTimeForm [name="reason"]')
        });
        $.app.setAutocomplete($('[data-autocomplete]'));
        $('[data-autocompletedebtors]').each(function () {
            $(this).autocomplete({
                source: armffURL + "ajax/debtors/search/autocomplete",
                minLength: 2,
                select: function (event, ui) {
                    //$('[name="' + $(this).attr('name').substr(0, $(this).attr('name').indexOf('_autocomplete')) + '"]').val(ui.item.id);
                }
            });
        });
        if (typeof (localStorage.last_msg_date) === 'undefined') {
            if ($('.chat-window .chat-msg:first').length > 0) {
                localStorage.last_msg_date = $.app.last_msg_datetime;
            }
        } else {
            if ($('.chat-window .chat-msg:first').length > 0) {
                if (moment(localStorage.last_msg_date) < moment($.app.last_msg_datetime)) {
                    localStorage.chat_window_closed = false;
                }
            }

        }
        if (localStorage.chat_window_closed === "true") {
            $('.chat-window').addClass('closed');
        } else {
            $('.chat-window').removeClass('closed');
        }
        $('.chat-window .close, .chat-window-toggler').click(function () {
            var $par = $('.chat-window:first');
            $par.toggleClass('closed');
            if ($par.hasClass('closed')) {
                localStorage.chat_window_closed = true;
                localStorage.last_msg_date = $.app.last_msg_datetime;
            } else {
                localStorage.chat_window_closed = false;
            }
            return false;
        });
        $('.alertdebtor-window-toggler').click(function () {
            $('.alertdebtor-window').removeClass('closed');
            return false;
        });
        $('.alertdebtor-window .close').click(function () {
            $('.alertdebtor-window').addClass('closed');
            return false;
        });
        $('.losscalls-window-toggler').click(function () {
            $('.losscalls-window').removeClass('closed');
            return false;
        });
        $('.losscalls-window .close').click(function () {
            $('.losscalls-window').addClass('closed');
            return false;
        });
        $('.debtoronsite-window-toggler').click(function () {
            $('.debtoronsite-window').removeClass('closed');
            return false;
        });
        $('.debtoronsite-window .close').click(function () {
            $('.debtoronsite-window').addClass('closed');
            return false;
        });
        $.app.initAccordionTables();
//        $.app.initSockets();
    };
    $.app.initAccordionTables = function () {
        $('.accordion-table .header').click(function () {
            var collapsedClass = 'children-collapsed';
            if ($(this).hasClass(collapsedClass)) {
                $(this).removeClass(collapsedClass);
                $(this).next().show();
            } else {
                $(this).addClass(collapsedClass);
                $(this).next().hide();
            }
        });
        $('.accordion-table .header.children-collapsed').next().hide();
    };
    $.app.ajaxResult = function (data) {
        var msg, elemClass, html;
        if (parseInt(data) === 1) {
            elemClass = 'success';
            msg = '<span class="glyphicon glyphicon-ok-sign"></span> Готово';
        } else {
            elemClass = 'danger';
            msg = '<span class="glyphicon glyphicon-exclamation-sign"></span> Ошибка';
        }
        html = '<div class="ajax-result alert alert-';
        html += elemClass + '" style="display:none">';
        html += msg;
        html += '</div>';
        $(html)
                .appendTo('body')
                .fadeIn('slow')
                .delay(1500)
                .fadeOut('slow', function () {
                    $(this).remove();
                });
    };
    $.app.openErrorModal = function (title, msg) {
        if (typeof (title) === 'undefined') {
            title = 'Ошибка!';
        }
        if (typeof (msg) === 'undefined') {
            msg = 'Ошибка!';
        }
        $('#errorModal .modal-title').html(title);
        $('#errorModal .modal-body').html(msg);
        $('#errorModal').modal();
    };
    $.app.blockScreen = function (block) {
        $('#uiBlockerModal').modal({
            backdrop: 'static',
            show: false,
            keyboard: false
        });
        $('#uiBlockerModal').modal((block) ? 'show' : 'hide');
    };
    $.app.openCardFrame = function (params) {
        $.get(armffURL + 'ajax/carddata',
                {'card_number': $('[name="card_number"]').val(),
                    'claim_id': $('[name="claim_id"]').val()}).done(function (data) {
            if (typeof (data) === 'undefined') {
                return false;
            }
            if (typeof (data) === 'string') {
                alert(data);
                return false;
            }
            var params = {
                card: $('[name="card_number"]').val(),
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
                                $.app.showCardFrame(params);
                            }
                        });
                    } else {
                        $.app.showCardFrame(params);
                    }
                }
            });
        });
    };
    $.app.showCardFrame = function (params) {
        $('#cardFrameModal [name="frame_closed"]').val('0');
        var url = 'https://perevod-korona.com/bank2/10.6.1.05/?ext='
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
        $('#cardFrameModal').modal();
        $('#cardFrameModal .modal-body').html('<iframe src="' + url + '" width="100%" style="min-height:520px"></iframe>');
    };
    $.app.onCardFrameClose = function () {
        $('#cardFrameModal [name="frame_closed"]').val('1');
        if ($('#createLoanModal').length > 0) {
            $('#createLoanFormHolder').hide();
            $('#cardApproveHolder').removeClass('hidden');
            $('#createLoanModal form input[type="submit"]').prop('disabled', true);
        }
    };
    $.app.setCurDate = function (elem, withTime) {
        var $elem;
        if ($(elem).attr("type") == 'text') {
            $elem = $(elem);
        } else {
            $elem = $(elem).parents('.input-group:first').find('.form-control:first');
        }
        $elem.val((withTime) ? (moment().format('DD.MM.YYYY HH:mm:ss')) : (moment().format('DD.MM.YYYY')));
    };
    $.app.openWorkTimeModal = function () {
        $('#addWorkTimeModal').modal({
            show: true
        });
        $('#workTimeForm [name="logout"]').val(1);
        $.get(armffURL + 'worktime/today/get').done(function (data) {
            if (data) {
                for (var i in data) {
                    $('#addWorkTimeModal [name="' + i + '"]').val(data[i]);
                }
                $('#workTimeForm').data('dataGrid').fill($.parseJSON($('#workTimeForm [name="reason"]').val()));
            }
//            if ($('#workTimeForm [name="date_end"]').val() == '') {
                $.app.setCurDate($('#workTimeForm [name="date_end"]').get(0), true);
//            }
        });
    };
    /**
     * применяет автокомплит к выборке элементов
     * @param {type} $elems jquery выборка элементов к которым применить автокомплит
     * @returns {undefined}
     */
    $.app.setAutocomplete = function ($elems) {
        $elems.each(function () {
            var tablename = ($(this)[0].hasAttribute('data-autocomplete2')) ? $(this).attr('data-autocomplete2') : $(this).attr('data-autocomplete');
            $(this).autocomplete({
                source: $.app.url + "/ajax/" + tablename + "/autocomplete?col1=" + $(this).attr('data-column'),
                minLength: 2,
                select: function (event, ui) {
                    $(this).prev('[name="' + $(this).attr('name').substr(0, $(this).attr('name').indexOf('_autocomplete')) + '"]').val(ui.item.id);
                }
            });
        });
    };
    /**
     * Возвращает значение get параметра из ссылки
     * @param {type} name имя параметра
     * @param {type} url ссылка
     * @returns {unresolved|String}
     */
    $.app.getUrlParameterByName = function (name, url) {
        if (!url)
            url = window.location.href;
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
                results = regex.exec(url);
        if (!results)
            return null;
        if (!results[2])
            return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    };

    $.app.initSockets = function () {
        var socket = io($.app.url + ':6001');
//        socket.on('connect', function (user) {
//            socket.emit('join', {id: 5, task_id: 1});
//        });
        socket.on('incoming-calls', function (data) {
            console.log(data, $.app.user_extension, data.user_extension);
            if ('user_extension' in data && $.app.user_extension && data.user_extension == $.app.user_extension) {
                $('.answer-incoming-call-btn').attr('data-call-id', data.call_id);
                $('#incomeCallingModal .calling-customer-template').not('.hidden').remove();
                if (Object.keys(data.customers).length > 1) {
                    $('#incomeCallingModal .modal-body .calling-customer-table').removeClass('hidden');
                    for (var i in data.customers) {
                        $('#incomeCallingModal .modal-body .calling-customer-table table').append($('#incomeCallingModal .calling-customer-table table tr.hidden').clone());
                        var $elem = $('#incomeCallingModal .calling-customer-table table tr:last');
                        initIncomeCallCustomer($elem,data.customers[i]);
                    }
                } else {
                    $('#incomeCallingModal .modal-body .calling-customer-table').addClass('hidden');
                    for (var i in data.customers) {
                        $('#incomeCallingModal .modal-body').append($('#incomeCallingModal .calling-customer-template.hidden').clone());
                        var $elem = $('#incomeCallingModal .calling-customer-template:last');
                        initIncomeCallCustomer($elem,data.customers[i]);
                    }
                }
                $('#incomeCallingModal').modal('show');
            }
        });
        var initIncomeCallCustomer = function ($elem,customer_data) {
            $elem.find('.answer-incoming-call-btn').attr('data-debtor-id', customer_data.debtor_id);
            $elem.find('.answer-incoming-call-btn').attr('data-telephone', customer_data.telephone);
            $elem.removeClass('hidden');
            for (var k in customer_data) {
                $elem.find('[data-' + k + ']:not(.answer-incoming-call-btn)').html(customer_data[k]);
            }
            $elem.find('.answer-incoming-call-btn').click(function () {
                incomingCallAnswerBtnHandler($(this));
            });
        };
        var incomingCallAnswerBtnHandler = function ($btn) {
            var call_id = $btn.attr('data-call-id');
            var debtor_id = $btn.attr('data-debtor_id');
            var telephone = $btn.attr('data-telephone');
            if (call_id == '' || debtor_id == '' || telephone == '') {
                return;
            }
            $.get($.app.url + '/ajax/infinity', {type: 'answer', call_id: call_id, debtor_id: debtor_id, telephone: telephone}).done(function (data) {
                console.log(data);
//                window.location.replace($.app.url+data.redirect_url);
            });
        };
    };
})(jQuery);
