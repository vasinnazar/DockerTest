(function ($) {
    $.claimCtrl = {};
    $.claimCtrl.init = function () {
        $.claimCtrl.confirmForm = false;
        $.claimCtrl.form = $('#loanCreateForm,#loanEditForm').first();
        if ($('#loanCreateForm').length > 0) {
            $('#loanStepHolder').bsStepsWizardCtrl();
        }
        $.claimCtrl.form.validator({
            errors: {
                minlength: 'Нужно больше символов',
                snils: 'Неверное значение СНИЛС'
            },
            custom: {
                snils: function ($el) {
                    if ($el.val() == '00000000000') {
                        return false;
                    }
                    if ($el.val() != '') {
                        return $.claimCtrl.checkSnils($el.val());
                    } else {
                        return true;
                    }
                }
            }
        });
        $.claimCtrl.form.find('[name="promocode_number"]').change(function () {
            if ($(this).val() == "") {
                $(this).parents('.form-group:first').removeClass('has-error');
            }
        }).focus(function () {
            $(this).popover('destroy');
        }).blur(function () {
            $.claimCtrl.checkPromocode();
        });
        $('#checkPromocodeBtn').click(function () {
            $.claimCtrl.checkPromocode();
            return false;
        });
        //маски для полей
        $.claimCtrl.form.find('[name="telephone"]').mask("7(999)999-9999");
        $.claimCtrl.form.find('[name="recomend_phone_1"]').mask("7(999)999-9999");
        $.claimCtrl.form.find('[name="recomend_phone_2"]').mask("7(999)999-9999");
        $.claimCtrl.form.find('[name="recomend_phone_3"]').mask("7(999)999-9999");
        $.claimCtrl.form.find('[name="subdivision_code"],[name="check_subdivision_code"]').mask("000-000");
        $.claimCtrl.form.find('[name="innorganizacia"]').mask("000000000000");
        $('.auto-year').mask("00.00.0000");
        $('.auto-year').bind('blur', function (e) {
            var d, m, year, date = $(this).val();
            d = parseInt(date.substr(0, date.indexOf('.')));
            m = parseInt(date.substr(date.indexOf('.') + 1, date.lastIndexOf('.')));
            year = parseInt(date.substr(date.lastIndexOf('.') + 1));
            if (year < 10) {
                year = '200' + year;
            } else if (year > 9 && year < 30) {
                year = '20' + year;
            } else if (year >= 30 && year < 100) {
                year = '19' + year;
            }
            $(this).val(((d < 10) ? '0' + d : d) + '.' + ((m < 10) ? '0' + m : m) + '.' + year);
//            if (new Date($(this).val()) > Date.now()) {
//                $(this).val('');
//            }
            $(this).change();
        });
//        $.loanCtrl.form.find('[name="dolznost"],[name="fio"],[name="fiorukovoditel"],[name="fiosuprugi"]').yaSpeller();
        $.claimCtrl.form.find('[name="dolznost"]').yaSpeller();
        //проводит валидацию формы, и добавляет индикаторы с количеством ошибок в кнопки шагов
        if ($.claimCtrl.form.attr('data-validate-on-ready') === 'true') {
            $.claimCtrl.form.validator('validate');
            $('#loanCreateForm .tab-content>.tab-pane').each(function () {
                var erNum = $(this).find('.help-block.with-errors>ul').length;
                if (erNum > 0) {
                    $('#loanCreateForm .nav-wizard a[href="#' + $(this).attr('id') + '"]')
                            .append('<span class="badge error-num-badge" title="Обнаружено ' + erNum + ' ошибок.">' + erNum + '</span>');
                }
            });
        }
        //переставить галочку совпадения адресов регистрации и проживания
        var equal = true;
        var allEmpty = true;
        $('#registrationResidence').find('input,select,textarea').each(function () {
            if($(this).val()!=''){
                allEmpty = false;
            }
            if ($(this).val() !== $('#factResidence [name="fact_' + $(this).attr('name') + '"]').val()) {
                equal = false;
            }
        });
        if (equal && !allEmpty) {
            $.claimCtrl.form.find('[name="copyFactAddress"]').prop('checked', true);
        }
        //сабмитит форму на нажатие кнопки "Все равно продолжить" в модальном окне с предупреждением
        $('#claimFormWarningModal [name="confirmBtn"]').click(function () {
            $.claimCtrl.confirmForm = true;
            $.claimCtrl.form.submit();
        });
        $.claimCtrl.form.find('select[name="adsource"]').click(function () {
            $.claimCtrl.form.find('select[name="adsource"]').attr('data-changed', '1');
        });
        //показывает модальное окно с предупреждением перед отправкой формы
        $.claimCtrl.form.submit(function (e) {
            var item, warningText, $warningModal, $warningModalBody, $emptyInputs = [];
            $.claimCtrl.form.find('.kladr-error').each(function(){
                $(this).parents('.form-group:first').addClass('has-error');
            });
            $.claimCtrl.form.find('select[name="srok"],select[name="sum"]').each(function () {
                if ($(this).val() == '0') {
                    $emptyInputs.push($(this));
                }
            });
            var $adsource = $.claimCtrl.form.find('select[name="adsource"]');
            if ($adsource.attr('data-changed') != '1' && $adsource.val() == '1') {
                $.app.openErrorModal('Ошибка!', 'Внимание! Перевыберите источник рекламы!');
                return false;
            }

            if ($emptyInputs.length > 0) {
                $.app.openErrorModal('Ошибка!', 'Внимание! Выберите сумму и срок займа!');
                return false;
            } else {
                $emptyInputs = [];
            }
            if ($.claimCtrl.form.find('.form-group.has-error, .form-group.spell-error, .kladr-error').length > 0) {
                $.app.openErrorModal('Ошибка!', 'Исправьте все ошибки на форме!');
                return false;
            }
            $.claimCtrl.form.find('input:not([type="hidden"],[name="copyFactAddress"])').each(function () {
                if (!$(this).get(0).validity.valid) {
                    $emptyInputs.push($(this));
                    $(this).parents('.form-group').addClass('has-error');
                }
            });
            if ($emptyInputs.length > 0) {
                alert('Заполните все обязательные поля!');
                $.claimCtrl.form.validator('validate');
                return false;
            }
            $.claimCtrl.form.find('input:not([type="hidden"],[name="copyFactAddress"]),select,textarea').each(function () {
                if ($(this).val() == '') {
                    $emptyInputs.push($(this));
                }
            });
            if ($emptyInputs.length > 0 && !$.claimCtrl.confirmForm) {
                e.preventDefault();
                $.claimCtrl.showControlModal($emptyInputs);
                return;
            } else {
                $.app.blockScreen(true);
                return;
            }
        });
        //предупредить пользователя об ошибках на текущей вкладке заявки
        $('.nav-wizard>li a').bind('click', function (e) {
            if ($($('.nav-wizard>li a.active').attr('href')).find('.has-error,.spell-error').length > 0) {
                alert('Исправьте ошибки в текущей вкладке!');
                return false;
            }
        });
        //перевод на следующий инпут по энтеру
        $('#loanCreateForm').find('input,select,textarea').bind('keydown', function (e) {
            if (e.keyCode == 13) {
                e.preventDefault();
                var inputs = $(this).closest('form').find('input,select,textarea');
                inputs.eq(inputs.index(this) + 1).select();
            }
        });
        //блокировать поля в фактическом адресе если изначально установлен чекбокс совпадения
        if ($('#loanCreateForm [name="copyFactAddress"]').prop("checked")) {
            $('#factResidence').find('input,select,textarea').prop('readonly', true);
        }
        $('#registrationResidence').find('input,select,textarea').bind('change', function () {
            if ($('#loanCreateForm [name="copyFactAddress"]').prop("checked")) {
                $('#factResidence [name="fact_' + $(this).attr('name') + '"]').val($(this).val());
            }
        });
        $('#registrationResidence [name="zip"]').bind('change', function () {
            if ($('#loanCreateForm [name="copyFactAddress"]').prop("checked")) {
                $('#registrationResidence').find('input,select,textarea').each(function () {
                    $('#factResidence [name="fact_' + $(this).attr('name') + '"]').val($(this).val());
                });
            }
        });
        $('#loanData [name="srok"]').change(function () {
            $('.loan-end-date-holder').html('&nbsp;&nbsp;&nbsp;&nbsp;(до ' + moment().add($(this).val(), 'd').format('DD.MM.YYYY') + ')');
        }).change();
        if ($.claimCtrl.form.find('[name="loank_customer_id_1c"]').val() != '') {
            $.post(armffURL + 'ajax/claims/autoapprove', {
                claim_id_1c: $.claimCtrl.form.find('[name="loank_claim_id_1c"]').val(),
                loan_id_1c: $.claimCtrl.form.find('[name="loank_loan_id_1c"]').val(),
                customer_id_1c: $.claimCtrl.form.find('[name="loank_customer_id_1c"]').val()
            }).done(function (data) {
                console.log(data);
            });
        }
        $('[name="telephonerodstv"],[name="anothertelephone"],[name="telephone"]').change(function () {
            $(this).parent().find('.btn .glyphicon').attr('class', 'glyphicon glyphicon-refresh');
        });
        //очистить поля в проживании и регистрации в новой заявке
        if ($('#loanStepHolder [name="claim_id"]').val() == '') {
            $('#registrationResidence,#factResidence').find('input,select,textarea').val('');
            $('[name="dohod"],[name="dopdohod"]').val('');
        }
    };
    $.claimCtrl.clearAddressForm = function(input){
        var fieldNames = '[name="address_region"],[name="address_district"],[name="address_city"],'+
                '[name="address_city1"],[name="address_street"],[name="address_house"],'+
                '[name="fact_address_region"],[name="fact_address_district"],[name="fact_address_city"],'+
                '[name="fact_address_city1"],[name="fact_address_street"],[name="fact_address_house"]';
        $(input).parents('.row:first').find(fieldNames).each(function(){
            var $input = $(this);
            $input.removeClass('kladr-error');
            $input.parents('.form-group:first').removeClass('has-error');
            $input.next('div.help-block.with-errors').remove();
            $input.attr('data-kladr-type','').attr('data-kladr-id','').val('').change();
        });
    };
    /**
     * показывает форму для проверки заполненности полей
     * @param {type} $emptyInputs
     * @returns {undefined}
     * 
     */
    $.claimCtrl.showControlModal = function ($emptyInputs) {
        var $warningModal = $('#claimFormWarningModal');
        var $warningModalBody = $('#emptyInputsAlert');
        var warningText = '<strong>В форме не заполнены следующие поля:</strong><br>';
        var $warningModalConfirmBtn = $warningModal.find('[name="confirmBtn"]');
        $('#checkPassportDataAlert').hide();
        if ($('#checkPassportDataForm').length > 0) {
            $warningModalConfirmBtn.prop('disabled', true);

            var $checkInputs = $('#checkPassportDataForm input');
            $checkInputs.keyup();
            $checkInputs.each(function () {
                var $formInput = $('#loanCreateForm [name="' + ($(this).attr('name').replace('check_', '')) + '"]');
                $formInput.css('color', 'white');
            });
            if ($warningModal.attr('data-not-opened') == '1') {
                $checkInputs.on('paste', function (e) {
                    e.preventDefault();
                });
                $checkInputs.change(function () {
                    var $formInput = $('#loanCreateForm [name="' + ($(this).attr('name').replace('check_', '')) + '"]');
                    if ($(this).val() != $formInput.val()) {
                        $(this).parent().addClass('has-error');
                        $warningModalConfirmBtn.prop('disabled', true);
                        $('#checkPassportDataAlert').show();
                    } else {
                        $(this).parent().removeClass('has-error');
                        if ($('#checkPassportDataForm .has-error').length == 0 && $('#checkPassportDataForm input:text').filter(function () {
                            return this.value == "";
                        }).length == 0) {
                            $warningModalConfirmBtn.prop('disabled', false);
                            $('#checkPassportDataAlert').hide();
                        }
                    }
                });
                $checkInputs.keyup(function (e) {
                    $(this).change();
                });
                $warningModal.attr('data-not-opened', '0');
            }
        } else {
            $('#checkPassportDataForm').hide();
        }
        for (var item in $emptyInputs) {
            warningText += $emptyInputs[item].parent().children('label').text() + ', ';
        }

        $warningModalBody.html(warningText);
        $warningModal.on('hidden.bs.modal', function (e) {
            if ($('#checkPassportDataForm').length > 0) {
                $checkInputs.each(function () {
                    var $formInput = $('#loanCreateForm [name="' + ($(this).attr('name').replace('check_', '')) + '"]');
                    $formInput.attr('style', '');
                });
            }
        });
        $warningModal.modal();
    };
    $.claimCtrl.copyFactAddress = function (box) {
        if (box.checked) {
            $('#factResidence').find('input,select,textarea').prop('readonly', true);
            $('#registrationResidence').find('input,select,textarea').each(function () {
                $('#factResidence [name="fact_' + $(this).attr('name') + '"]').val($(this).val());
            });
        }
        if (!box.checked) {
            $('#factResidence').find('input,select,textarea').not('[name="fact_address_region"],[name="fact_address_district"],[name="fact_address_city"],[name="fact_zip"]').prop('readonly', false).val('');
        }
    };
    $.claimCtrl.checkPromocode = function () {
        var $input = $.claimCtrl.form.find('[name="promocode_number"]');
        if ($input.val() == "") {
            return false;
        }
        $.get(armffURL + 'ajax/promocodes/check', {
            promocode_number: $input.val(),
            series: $.claimCtrl.form.find('[name="series"]').val(),
            number: $.claimCtrl.form.find('[name="number"]').val(),
        }).done(function (data) {
            var popoverParams = {content: '', title: '', placement: 'top', trigger: 'manual'};
            if (data === null) {
                return false;
            }
            if ('msg_err' in data) {
                popoverParams.content = data['msg_err'];
                popoverParams.title = 'Ошибка!';
                $input.popover(popoverParams).popover('show');
            }
            if ('res' in data && data.res == '1') {
                popoverParams.content = 'Промокод действителен';
                popoverParams.title = 'Готово!';
                $input.popover(popoverParams).popover('show');
            }
        });
    };
    $.claimCtrl.getCustomerData = function () {
        $.app.blockScreen(true);
        $.post(armffURL + 'ajax/claims/customer', {
            series: $.claimCtrl.form.find('[name="series"]').val(),
            number: $.claimCtrl.form.find('[name="number"]').val(),
            claim_id: $.claimCtrl.form.find('[name="claim_id"]').val(),
            about_client_id: $.claimCtrl.form.find('[name="about_client_id"]').val()
        }).done(function (data) {
            if (data) {
                if (data['redirect'] == '1') {
                    window.location.replace(armffURL + "/home");
                }
                $.app.blockScreen(false);
                for (var d in data) {
                    $.claimCtrl.form.find('[name="' + d + '"]').attr('type');
                    if (d != 'telephone' && d != 'series' && d != 'number') {
                        if ($.claimCtrl.form.find('[name="' + d + '"]').attr('type') == 'checkbox' && data[d]) {
                            $.claimCtrl.form.find('[name="' + d + '"]').prop('checked', true);
                        } else {
                            $.claimCtrl.form.find('[name="' + d + '"]').val(data[d]);
                        }
                    }
                }
            }
        });
    };
    $.claimCtrl.checkSnils = function (checkedValue) {
        var checkSum = parseInt(checkedValue.slice(9), 10);

        //строка как массив(для старых браузеров)
        checkedValue = "" + checkedValue;
        checkedValue = checkedValue.split('');

        var sum = (checkedValue[0] * 9 + checkedValue[1] * 8 + checkedValue[2] * 7 + checkedValue[3] * 6 + checkedValue[4] * 5 + checkedValue[5] * 4 + checkedValue[6] * 3 + checkedValue[7] * 2 + checkedValue[8] * 1);

        if (sum < 100 && sum == checkSum) {
            return true;
        } else if ((sum == 100 || sum == 101) && checkSum == 0) {
            return true;
        } else if (sum > 101 && (sum % 101 == checkSum || (sum % 101 == 100 && checkSum == 0))) {
            return true;
        } else {
            return false;
        }
    };
    $.claimCtrl.testFill = function () {
        var ps = $('#loanCreateForm [name="series"]').val();
        var pn = $('#loanCreateForm [name="number"]').val();
        $('#loanCreateForm [name="fio"]').val('Тестов Тест Тестович' + ps);
        $('#loanCreateForm [name="telephone"]').val('7(000)000-' + ps);
        $('#loanCreateForm [name="birth_date"]').val('01.01.1990');
        $('#loanCreateForm [name="issued_date"]').val('01.01.1990');
        $('#loanCreateForm [name="address_reg_date"]').val('01.01.1990');
        $('#loanCreateForm [name="birth_city"]').val('г. Кемерово');
        $('#loanCreateForm [name="issued"]').val('отд. УФМС');
        $('#loanCreateForm [name="subdivision_code"]').val('123-123');
        $('#loanCreateForm [name="subdivision_code"]').val('123-123');
        $('#loanCreateForm [name="address_region"]').val('Кемеровская обл');
        $('#loanCreateForm [name="address_city"]').val('Кемерово г');
        $('#loanCreateForm [name="address_street"]').val('Дарвина ул');
        $('#loanCreateForm [name="address_house"]').val('160а');
        $('#loanCreateForm [name="fact_address_region"]').val('Кемеровская обл');
        $('#loanCreateForm [name="fact_address_city"]').val('Кемерово г');
        $('#loanCreateForm [name="fact_address_street"]').val('Дарвина ул');
        $('#loanCreateForm [name="fact_address_house"]').val('160а');
        $('#loanCreateForm [name="anothertelephone"]').val('123-123');
        $('#loanCreateForm [name="organizacia"]').val('"Рога и Копыта"');
        $('#loanCreateForm [name="sum"]').val('3000');
        $('#loanCreateForm [name="srok"]').val('5');
    };
    $.claimCtrl.checkPhone = function (btn) {
        var $telInput = $(btn).parents('.input-group:first').find('input:first[type="text"]');
        var $btn = $(btn);
        $.app.blockScreen(true);
        $.post($.app.url + '/ajax/claims/check/telephone', {telephone: $telInput.val()}).done(function (data) {
            $.app.blockScreen(false);
            if (data.result == 1) {
                $telInput.data('hlr_id', data.hlr_id);
                $btn.children('.glyphicon').attr('class', 'glyphicon glyphicon-refresh');
//                $telInput.parent().find('.ajax-wait-addon').remove();
//                $telInput.parent().prepend('<span class="input-group-addon ajax-wait-addon">Подождите...</span>');
                $telInput.data('checksNum', 1);
                $btn.prop('disabled', true);
                setTimeout(function () {
                    $.claimCtrl.checkPhoneUpdate($telInput);
                }, 3000);
            } else {
                $.app.ajaxResult(data);
            }
        });
    };
    $.claimCtrl.checkPhoneUpdate = function ($telInput) {
        $.post($.app.url + '/ajax/claims/check/telephone', {
            telephone: $telInput.val(), 
            hlr_id: $telInput.data('hlr_id'),
            postclient:(($('[name="postclient"]').prop('checked'))?1:0),
            series:$('[name="series"]').val(),
            number:$('[name="number"]').val()
        }).done(function (data) {
            var $ajaxWaitAddon = $telInput.parent().find('.ajax-wait-addon');
            var $btn = $telInput.parent().find('.btn');
            console.log("CHECK PHONE UPDATE", data);
            if (data.result == 1) {
                if (data.status == 'В сети') {
                    $btn.children('.glyphicon').attr('class', 'glyphicon glyphicon-ok');
                } else {
                    $btn.children('.glyphicon').attr('class', 'glyphicon glyphicon-remove');
                }
//                $ajaxWaitAddon.text(data.status);
                $telInput.data('checksNum', 0);
                $telInput.removeData('hlr_id');
                $btn.prop('disabled', false);
            } else {
                if ($telInput.data('checksNum') < 2) {
                    $telInput.data('checksNum', $telInput.data('checksNum') + 1);
                    setTimeout(function () {
                        $.claimCtrl.checkPhoneUpdate($telInput);
                    }, 31000);
                } else {
                    $ajaxWaitAddon.remove();
                    $telInput.parent().find('.btn').prop('disabled', true);
                }
            }
        });
    };
})(jQuery);
