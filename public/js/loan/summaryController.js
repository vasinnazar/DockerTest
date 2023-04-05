(function ($) {
    $.summaryCtrl = {};
    $.summaryCtrl.init = function () {
        $.summaryCtrl.addRepModal = $('#addRepaymentModal');
        $.summaryCtrl.addRepModalMonths = $.summaryCtrl.addRepModal.find('[name="months"]');
        $.summaryCtrl.addRepModalTime = $.summaryCtrl.addRepModal.find('[name="time"]');
        $.summaryCtrl.addRepModalDiscount = $.summaryCtrl.addRepModal.find('[name="discount"]');
        $.summaryCtrl.addRepModalEndDate = $.summaryCtrl.addRepModal.find('[name="end_date"]');
        $.summaryCtrl.addRepModalMoney = $.summaryCtrl.addRepModal.find('[name="paid_money"]');
        $.summaryCtrl.addRepModalInfo = $('#addRepaymentModalInfo');
        $.summaryCtrl.addRepModalCreateDate = $.summaryCtrl.addRepModal.find('[name="create_date"]');
        $('input.money').myMoney();
        $('.photo-gallery .photo-preview').click($.summaryCtrl.handlePhotoClick);
        $('.photo-gallery .photo-preview[data-main="1"]').click();

        /**
         * при смене скидки, меняет требуемую сумму в модальном окне создания договора
         */
        $.summaryCtrl.addRepModalDiscount.change($.summaryCtrl.handleDiscountChange);

        $('.add-repayment-btn').click($.summaryCtrl.handleAddRepaymentBtn);
        $.summaryCtrl.addRepModalCreateDate.blur($.summaryCtrl.updateReqMoneyOnDateChange);
        $('.show-contract-orders-btn').click($.summaryCtrl.handleToggleOrdersBtnClick);
        $('#loanRepaymentsTools .edit-btn').click($.summaryCtrl.handleRepaymentEditBtnClick);
        $('#addRepaymentModal form, #addOrderModal form').submit(function () {
            $.app.blockScreen(true);
        });
        $('#addRepaymentModal form [name="discount_anyway"]').change($.summaryCtrl.handleDiscountAnywayChange);
        $('#addRepaymentModal form [name="discount_anyway"]').prop('checked', !$.summaryCtrl.addRepModalDiscount.parent().prop('disabled'));

        //инициализируем добавление графика для суза
        var dataGrid = new DataGridController($('#addSuzScheduleForm [name="suzschedule"]').val(), {
            table: $("#addSuzScheduleTable"),
            form: $('#addSuzScheduleForm'),
            dataField: $('#addSuzScheduleForm [name="suzschedule"]')
        });
        $('#addSuzScheduleForm [name="change_suz"]').change(function () {
            $('#addSuzScheduleForm [type="text"]').prop('disabled', !$(this).prop('checked'));
        });
        //изменение срока документа в окне создания документа
        $.summaryCtrl.addRepModalTime.change($.summaryCtrl.handleAddRepaymentTimeChange);
        
//        if($('[name="spisan"]').val()=='1'){
//            $('.contract-pc,.contract-exp-pc,.contract-od,.contract-fine,.contract-tax').text('');
//        }
    };
    $.summaryCtrl.handleToggleOrdersBtnClick = function () {
        $(this).children('.glyphicon').toggle();
        $(this).parents('tr:first').next('tr.contract-orders-row').toggle();
        return false;
    };
    /**
     * нажатие на кнопку редактирования документа
     * @returns {Boolean}
     */
    $.summaryCtrl.handleRepaymentEditBtnClick = function () {
        var $modal = $('#editRepaymentModal'), $row = $(this).parents('tr:first');
        $.get(armffURL + 'ajax/repayments/get/' + $(this).attr('data-repayment-id')).done(function (data) {
            if (data) {
                for (var i in data) {
                    $modal.find('[name="' + i + '"]').val(data[i]);
                }
                $modal.modal('show');
                $modal.find('.money').myMoney();
            }
        });
        return false;
    };
    /**
     * реакция на галочку "все равно применить промокод"
     * @returns {undefined}
     */
    $.summaryCtrl.handleDiscountAnywayChange = function () {
        if ($(this).prop('checked')) {
            $.summaryCtrl.addRepModalDiscount.prop('disabled', false).parent().show();
        } else {
            $.summaryCtrl.addRepModalDiscount.prop('disabled', true).parent().hide();
        }
    };
    /**
     * смена срока в окне создания нового документа
     * @returns {undefined}
     */
    $.summaryCtrl.handleAddRepaymentTimeChange = function () {
        $.summaryCtrl.addRepModalEndDate.val(moment().add($(this).val(), 'days').format('YYYY-MM-DD'));
        if ($('#addRepaymentModalInfo').attr('data-rtype-text-id') == 'dop_commission') {
            $.summaryCtrl.updateDopCommissionInfo($(this).val());
        }
    };
    /**
     * нажатие на превью фотографий
     * @returns {Boolean}
     */
    $.summaryCtrl.handlePhotoClick = function () {
        var $gallery = $(this).parents('.photo-gallery:first');
        $gallery.find('.main-photo>img').attr('src', $(this).attr('src'));
        $gallery.find('.photo-preview').removeClass('selected');
        $(this).addClass('selected');
        return false;
    };
    /**
     * Смена скидки
     * @returns {undefined}
     */
    $.summaryCtrl.handleDiscountChange = function () {
        var rtypeTextID = $('#addRepaymentModalInfo').attr('data-rtype-text-id'),
                mDet = $.summaryCtrl.getRepaymentReqMoneyDetails(rtypeTextID),
                reqMoney = $.summaryCtrl.getRepaymentReqMoney(rtypeTextID);
        reqMoney = reqMoney - mDet.pc * ($(this).val() / 100);
        $('#addRepaymentModalInfo').text(((rtypeTextID == 'closing') ? 'Необходимая сумма взноса: ' : 'Минимальная сумма взноса: ') + (Math.round(reqMoney) / 100) + ' руб.');
    };
    /**
     * нажатие на кнопку создания нового документа
     * @returns {Boolean}
     */
    $.summaryCtrl.handleAddRepaymentBtn = function () {
        var rtypeID = $(this).attr('data-rtype-id'),
                rtypeType = $(this).attr('data-rtype-type'),
                rtypeTextID = $(this).attr('data-rtype-text-id');
        $('#addRepaymentModal input[name="repayment_type_id"]').val(rtypeID);
        $('#addRepaymentModalInfo').attr('data-rtype-text-id', rtypeTextID);
        $('#addRepaymentModalInfo').show();

        //очищаем поля
        $('[name="paid_money"]').val('');
        $('[name="paid_money"]').prop('readonly', false);
        $('[name="discount"]').prop('disabled', false);
        $.summaryCtrl.addRepModalTime.attr('max', '');
        $('#dopCommissionInfo').empty();

        //запоминает предыдущее значения поля "дата создания документа" и если оно изменяется, запрашивает данные о задолженности
        if($.summaryCtrl.addRepModal.find('[name="create_date"]').length>0){
            var oldCreateDateVal = $.summaryCtrl.addRepModalCreateDate.val();
            $.summaryCtrl.addRepModalCreateDate.val('');
            //если создается допник прошлым днем 
            if (rtypeTextID == 'exdopnik') {
                $.summaryCtrl.addRepModalCreateDate.parent().show();
                if ($('#exDopnikData').length > 0) {
                    $.summaryCtrl.addRepModalCreateDate.val($('#exDopnikData [name="dopnik_create_date"]').val());
                    $.summaryCtrl.addRepModalTime.prop('readonly', true).attr('max', '');
                    $.summaryCtrl.addRepModalCreateDate.prop('readonly',true);
                }
            } else {
                $.summaryCtrl.addRepModalCreateDate.parent().hide();
            }
            $.summaryCtrl.addRepModalCreateDate.blur();
        }

        $.summaryCtrl.addRepModalMoney.prop('disabled', false).parent().show();
        $.summaryCtrl.addRepModalInfo.show();
        if (rtypeType == 'peace') {
            $.summaryCtrl.addRepModalMonths.prop('disabled', false).parent().show();
            $.summaryCtrl.addRepModalTime.prop('disabled', true).parent().hide();
            $.summaryCtrl.addRepModalMoney.prop('disabled', true).parent().hide();
            $.summaryCtrl.addRepModalInfo.hide();
        } else {
            $.summaryCtrl.addRepModalMonths.prop('disabled', true).parent().hide();
            $.summaryCtrl.addRepModalTime.prop('disabled', false).parent().show();
        }
//        if (($('#contractsTable tbody tr:last').prev().hasClass('bg-danger') && !(parseInt($('[name="cc_call"]').val()))) || rtypeType == 'claim' || rtypeType == 'peace') {
        if (rtypeType == 'claim' || rtypeType == 'peace') {
            $.summaryCtrl.addRepModalDiscount.prop('disabled', true).parent().hide();
            if(!$.app.is_admin){
                $.summaryCtrl.addRepModalMoney.prop('disabled', true).parent().hide();
            }
        } else {
            $.summaryCtrl.addRepModalDiscount.prop('disabled', false).parent().show();
            $.summaryCtrl.addRepModalMoney.prop('disabled', false).parent().show();
        }
        if (rtypeTextID == 'closing' || rtypeType == 'peace') {
            $.summaryCtrl.addRepModalTime.prop('disabled', true).parent().hide();
            $.summaryCtrl.addRepModalEndDate.parent().hide();
        } else {
            $.summaryCtrl.addRepModalTime.prop('disabled', false).parent().show();
            $.summaryCtrl.addRepModalEndDate.parent().show();
        }
        if ($(this).attr('data-rtype-def-time') > 0) {
            $.summaryCtrl.addRepModalTime.prop('readonly', true).val($(this).attr('data-rtype-def-time')).change();
        } else {
            $.summaryCtrl.addRepModalTime.prop('readonly', false).val('1');
        }
        if (rtypeTextID.indexOf('dopnik') > -1) {
            $.summaryCtrl.addRepModal.find('[name="create_date"]').parent().show();
            $.summaryCtrl.addRepModalTime.prop('readonly', false).parent().show();
            //заблокировать редактирование срока и выставить срок до окончания 120 дневного порога
//            if($.summaryCtrl.addRepModalCreateDate.val()==''){
            var lastContractDate = moment($('.contract-row:first .contract-date').text(), 'DD.MM.YYYY');
            if (lastContractDate > moment().subtract(120, 'days')) {
                var daysBefore120 = 120 - Math.abs(lastContractDate.hours(0).minutes(0).seconds(0).diff(moment().hours(0).minutes(0).seconds(0), 'days'));
                if (daysBefore120 <= 30) {
                    $.summaryCtrl.addRepModalTime.attr('max', daysBefore120);
                    $.summaryCtrl.addRepModalTime.val(daysBefore120);
                }
            }
//            }
        } else {
            $.summaryCtrl.addRepModal.find('[name="create_date"]').parent().hide();
        }
        $.summaryCtrl.addRepModalDiscount.prop('disabled', false).val(0);
        var mDet = $.summaryCtrl.getRepaymentReqMoneyDetails(rtypeTextID);
        if (mDet.pc == 0 || (rtypeTextID == 'closing' && mDet.hasPromo)) {
            $.summaryCtrl.addRepModalDiscount.prop('disabled', true);
        }

        $.summaryCtrl.updateReqMoneyLabel(rtypeTextID);
        if ($('#ngBezDolgov').length > 0) {
            $('[name="paid_money"]').val(mDet.total / 100);
            $('[name="paid_money"]').prop('readonly', true).blur();
            $('[name="discount"]').prop('disabled', true);
        }
        if (rtypeTextID == 'dop_commission') {
            $('[name="paid_money"]').val($.summaryCtrl.getRepaymentReqMoney(rtypeTextID) / 100);
            $('[name="paid_money"]').prop('readonly', true).blur();
            $('[name="discount"]').prop('disabled', true);
            $.summaryCtrl.addRepModalTime.prop('readonly', false).parent().show();
            $.summaryCtrl.addRepModalTime.attr('max', 15);
            $('#addRepaymentModalInfo').hide();
        }

        $('#addRepaymentModal').modal();

        return false;
    };
    /**
     * запросить суммы задолженности из 1с на смену даты в окне создания нового документа
     * @returns {undefined}
     */
    $.summaryCtrl.updateReqMoneyOnDateChange = function () {
        var $createDateInput = $(this);
        $.post($.app.url + '/ajax/loans/get/debt', {id: $('[name="loan_id"]').val(), date: $('[name="create_date"]').val()}).done(function (data) {
            var json = $.parseJSON(data);
            $('#reqMoneyDetails [name="reqPc"]').val(json['pc']);
            $('#reqMoneyDetails [name="reqExpPc"]').val(json['exp_pc']);
            $('#reqMoneyDetails [name="reqFine"]').val(json['fine']);
            $('#reqMoneyDetails [name="reqOD"]').val(json['od']);
            $('#reqMoneyDetails [name="reqUki"]').val(json['uki']);
            $('#reqMoneyDetails [name="reqMoney"]').val(json['money']);
            for (var item in json) {
                $('#addRepaymentModalDebt_' + item).text((parseFloat(json[item]) / 100).toFixed(2));
            }
            $.summaryCtrl.updateReqMoneyLabel($('#addRepaymentModalInfo').attr('data-rtype-text-id'));
            $.summaryCtrl.addRepModal.find('[name="time"]').val(Math.abs(moment($createDateInput.val()).hours(0).minutes(0).seconds(0).diff(moment(), 'days')));
        });
    };
    /**
     * обновляет строчку с суммой для взноса при создании допника с комиссией
     * @param {type} time
     * @returns {undefined}
     */
    $.summaryCtrl.updateDopCommissionInfo = function (time) {
        var $dopComInfo = $('#dopCommissionInfo');
        var mDet = $.summaryCtrl.getRepaymentReqMoneyDetails($('#addRepaymentModalInfo').attr('data-rtype-text-id'));
        $.post($.app.url + '/repayments/dopcommission/calculate', {time: time, loan_id: $('[name="loan_id"]').val(), od: mDet.od}).done(function (data) {
            $dopComInfo.html(data.msg);
            $('[name="paid_money"]').val((data.money_to_pay / 100).toFixed(2));
            $('[name="paid_money"]').change();
        });
    };
    /**
     * обновляет строчку с необходимой суммой взноса
     * @param {type} rtypeTextID
     * @param {type} money
     * @returns {undefined}
     */
    $.summaryCtrl.updateReqMoneyLabel = function (rtypeTextID, money) {
        if (typeof (money) === "undefined") {
            money = ($.summaryCtrl.getRepaymentReqMoney(rtypeTextID) / 100).toFixed(2);
        }
        if (rtypeTextID == 'dop_commission') {
            money = (Math.round($.summaryCtrl.getRepaymentReqMoneyDetails(rtypeTextID).od * 0.3) / 100).toFixed(2);
        }
        var label = 'Минимальная сумма взноса: ';
        if (rtypeTextID == 'closing' || rtypeTextID == 'dop_commission') {
            label = 'Необходимая сумма взноса: ';
        }
        $('#addRepaymentModalInfo').text(label + money + ' руб.');
    };
    /**
     * возвращает минимальную или полную сумму гашения, в зависимости от переданного типа договора. 
     * если допник, то проценты и просроченные, если закрытие, то полная сумма, иначе один рубль
     * @param {string} rTypeTextID текстовый идентификатор договора (claim,dopnik,peace,closing..)
     * @returns {int}
     */
    $.summaryCtrl.getRepaymentReqMoney = function (rTypeTextID) {
        var mDet = $.summaryCtrl.getRepaymentReqMoneyDetails(rTypeTextID);
        if (rTypeTextID.indexOf('dopnik') > -1) {
            return mDet.pc + mDet.exp_pc;
        } else if (rTypeTextID == 'closing') {
            return mDet.pc + mDet.exp_pc + mDet.fine + mDet.od + mDet.uki;
        } else if (rTypeTextID == 'dop_commission') {
            return Math.round($.summaryCtrl.getRepaymentReqMoneyDetails(rTypeTextID).od * 0.3);
        }
        return 100;
    };
    /**
     * собирает данные с формы по требуемой сумме гашения. возвращает объект с детализацией требуемой суммы
     * @param {string} rTypeTextID текстовый идентификатор договора (claim,dopnik,peace,closing..)
     * @returns {summaryController_L1.$.summaryCtrl.getRepaymentReqMoneyDetails.summaryControllerAnonym$0}
     */
    $.summaryCtrl.getRepaymentReqMoneyDetails = function (rTypeTextID) {
        var hasPromo = (parseInt($('[name="promocode_id"]').val()) > 0) ? true : false,
                promoDiscount = $('[name="promocode_discount"]').val(),
                pc = parseInt($('#reqMoneyDetails [name="reqPc"]').val());
        if (rTypeTextID == 'closing') {
            pc = (hasPromo) ? ((pc - promoDiscount < 0) ? 0 : pc - promoDiscount) : pc;
        }
        if ($('#ngBezDolgov').length > 0) {
            return {
                pc: parseInt($('#ngBezDolgov [name="ngm_pc"]').val()),
                exp_pc: parseInt($('#ngBezDolgov [name="ngm_exp_pc"]').val()),
                fine: 0,
                od: parseInt($('#ngBezDolgov [name="ngm_od"]').val()),
                uki: 0,
                total: parseInt($('#ngBezDolgov [name="ngm_total"]').val()),
                promoDiscount: promoDiscount,
                hasPromo: hasPromo
            };
        } else {
            return {
                pc: pc,
                exp_pc: parseInt($('#reqMoneyDetails [name="reqExpPc"]').val()),
                fine: parseInt($('#reqMoneyDetails [name="reqFine"]').val()),
                od: parseInt($('#reqMoneyDetails [name="reqOD"]').val()),
                uki: parseInt($('#reqMoneyDetails [name="reqUki"]').val()),
                total: parseInt($('#reqMoneyDetails [name="reqMoney"]').val()),
                promoDiscount: promoDiscount,
                hasPromo: hasPromo
            };
        }
    };
    /**
     * запрашивает с сервака данные по платежу в мировом и открывает модальное окно для их редактирования
     * @param {int} id идентификатор платежа
     * @returns {undefined}
     */
    $.summaryCtrl.editPeacePay = function (id) {
        $.get(armffURL + 'ajax/repayments/peacepays/get/' + id).done(function (data) {
            $('#editPeacePayModal').modal('show');
            for (var d in data) {
                $('#editPeacePayModal input[name="' + d + '"]').val(data[d]);
                if (d == 'closed') {
                    $('#editPeacePayModal input[name="closed"]').prop('checked', data[d]);
                }
            }
            $('#editPeacePayModal .money').myMoney();
        });
    };
    /**
     * запрашивает данные по кредитнику и открывает модальное окно для его редактирования
     * @param {int} id идентификатор кредитника
     * @returns {undefined}
     */
    $.summaryCtrl.editLoan = function (id) {
        $.get(armffURL + 'ajax/loans/get/' + id).done(function (data) {
            $('#loanEditorModal').modal('show');
            for (var d in data) {
                //сумма в кредитнике хранится в рублях, а плагин расчитан на копейки
                if (d == 'money') {
                    data[d] *= 100;
                }
                if (d == 'created_at') {
                    data[d] = moment(data[d]).format('YYYY-MM-DD');
                }
                $('#loanEditorModal input[name="' + d + '"]').val(data[d]);
                if (d == 'closed') {
                    $('#loanEditorModal input[name="closed"]').prop('checked', data[d]);
                }
            }
            $('#loanEditorModal .money').myMoney();
        });
    };
    /**
     * запрашивает данные по договору и открывает модальное окно для его редактирования
     * @param {int} id идентификатор договора
     * @returns {undefined}
     */
    $.summaryCtrl.editRepayment = function (id, ispeace) {
        $.get(armffURL + 'ajax/repayments/get/' + id).done(function (data) {
            $('#editRepaymentModal').modal('show');
            for (var d in data) {
                $('#editRepaymentModal input[name="' + d + '"]').val(data[d]);
            }
            if (ispeace) {
                $('#editRepaymentModal input[name="months"]').show();
            } else {
                $('#editRepaymentModal input[name="months"]').hide();
            }
            $('#editRepaymentModal .money').myMoney();
        });
    };
    /**
     * запрашивает данные по договору и показывает окно с комментарием
     * @param {int} id идентификатор договора
     * @returns {undefined}
     */
    $.summaryCtrl.showComment = function (id) {
        $.get(armffURL + 'ajax/repayments/get/' + id).done(function (data) {
            $('#repaymentCommentModal').modal('show');
            $('#repaymentCommentModal .repayment-comment').text(data.comment);
        });
    };
    $.summaryCtrl.getDebt = function () {

    };
})(jQuery);
