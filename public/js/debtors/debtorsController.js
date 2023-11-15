(function ($) {
    $.debtorsCtrl = {};
    $.debtorsCtrl.init = function () {
        $.debtorsCtrl.initDebtorsTable();
        $.debtorsCtrl.initDebtorsTableSearchForm();
        $.debtorsCtrl.changeResponsibleUser();
        $.debtorsCtrl.changeDebtorTransferTable();
        $.debtorsCtrl.submitEventsFilter();
        $.debtorsCtrl.changeLoadStatus();
        $.debtorsCtrl.changePersonalData();
        $.debtorsCtrl.debtorsCounter();
    };
    $.debtorsCtrl.initDebtorsTableSearchForm = function () {
        $('#debtorsFilter .autocomplete, #debtorEventsFilter .autocomplete, #debtorTransferFilter .autocomplete, #debtorMassSendFilter .autocomplete, #debtorTransferAction .autocomplete, #editDebtorEventModal .autocomplete, #chief_event_field .autocomplete, #forgottenDebtorFilter .autocomplete, #recommendsDebtorFilter .autocomplete').each(function () {
            var fieldName = $(this).attr('name');
            var valFieldName = $(this).attr('data-hidden-value-field');
            $(this).autocomplete({
//                source: $.app.url + '/ajax/debtors/search/autocomplete',
                source: function (request, response) {
                    $.ajax({
                        url: $.app.url + '/ajax/debtors/search/autocomplete',
//                        dataType: "jsonp",
                        data: {
                            field: fieldName,
                            term: request.term,
                            valfield: valFieldName
                        },
                        success: function (data) {
                            response(data);
                        }
                    });
                },
                focus: function (event, ui) {
                    $(this).val(ui.item.label);
                    $(this).next('[name="' + valFieldName + '"]').val(ui.item.value);
                    return false;
                },
                select: function (event, ui) {
                    $(this).val(ui.item.label);
                    $(this).next('[name="' + valFieldName + '"]').val(ui.item.value);
                    return false;
                }
            });
            $(this).autocomplete("instance")._renderItem = function (ul, item) {
                return $("<li>").append("<div>" + item.label + "</div>").appendTo(ul);
            };
        });
    };
    $.debtorsCtrl.initDebtorsTable = function () {
        $.debtorsCtrl.debtorsTableCtrl = new TableController('debtors', [
            {data:'debtors_fixation_date', name: 'debtors_fixation_date'},
            {data:'passports_fio', name: 'passports_fio'},
            {data:'debtors_loan_id_1c', name: 'debtors_loan_id_1c'},
            {data:'debtors_qty_delays', name: 'debtors_qty_delays'},
            {data:'debtors_sum_indebt', name: 'debtors_sum_indebt'},
            {data:'debtors_od', name: 'debtors_od'},
            {data:'debtors_base', name: 'debtors_base'},
            {data:'customers_telephone', name: 'customers_telephone'},
            {data:'debtors_debt_group_id', name: 'debtors_debt_group_id'},
            {data:'debtors_username', name: 'debtors_username'},
            {data: 'debtor_str_podr', name: 'debtor_str_podr'},
            {data: 'actions', name: 'actions', searchable: false, orderable: false},
        ], {
            dtData: {
                scrollY: 300,
                scrollCollapse: false
            },
            //order: [ [1, 'asc'] ],
            stateSave: true,
            stateSaveCallback: function (settings, data) {
                localStorage.datatables_debtors_data = JSON.stringify(data)
            },
            stateLoadCallback: function (settings) {
                return JSON.parse(localStorage.datatables_debtors_data)
            },
            clearFilterBtn: $('#debtorsClearFilterBtn'),
//            repeatLastSearchBtn: $('#repeatLastSearchBtn'),
        });
        $.debtorsCtrl.eventsTableCtrl = new TableController('debtorevents', [
            {data: 'de_date', name: 'de_date'},
            {data: 'de_type_id', name: 'de_type_id'},
            {data: 'passports_fio', name: 'passports_fio'},
            {data: 'de_created_at', name: 'de_created_at'},
            {data: 'de_amount', name: 'de_amount'},
            {data: 'de_username', name: 'de_username'},
            {data: 'actions', name: 'actions', searchable: false, orderable: false},
        ], {
            dtData: {
                scrollY: 300,
                scrollCollapse: false,
            },
            clearFilterBtn: $('#debtorEventsClearFilterBtn'),
            filterBtn: $('#debtorEventsFilterBtn'),
            filterHolder: $('#debtorEventsFilter')
        });
        $.debtorsCtrl.selectTotalPlannedColumn($('.planned-table-selected-btn:first'));
    };
    $.debtorsCtrl.uploadOldDebtorEvents = function () {
        $.app.blockScreen(true);
        $.post($.app.url + '/ajax/debtors/oldevents/upload').done(function (data) {
            $.app.blockScreen(false);
        });
    };

    $.debtorsCtrl.initDebtorsCard = function () {
        var $debtCalcDate = $('.debtor-debt-table [name="debt_calc_date"]');
        $debtCalcDate.change(function () {
            $('#multi_loan_block').html('<p style="text-align: center; color: blue; font-weight: bold;">Получение информации...</p>');
            $.post($.app.url + '/ajax/debtors/loans/getmultisum', {
                loan_id_1c: $('input[name="loan_id_1c"]').val(),
                customer_id_1c: $('input[name="customer_id_1c"]').val(),
                date: $(this).val()
            }).done(function (data) {
                if (data == '0') {
                    $('#multi_loan_block').html('');
                } else {
                    $('#multi_loan_block').html(data);


                }
            });

            $.app.blockScreen(true);
            $.debtorsCtrl.updateDebtTable($(this).val());
        });

        $debtCalcDate.val(moment().format('YYYY-MM-DD'));
        $.debtorsCtrl.updateDebtTable($debtCalcDate.val());

    };
    $.debtorsCtrl.updateDebtTable = function (date) {
        $.post($.app.url + '/ajax/loans/get/debt', {
            date: date,
            loan_id_1c: $('.debtor-data [name="loan_id_1c"]').val(),
            customer_id_1c: $('.debtor-data [name="customer_id_1c"]').val()
        }).done(function (data) {
            var calc_data = $.parseJSON(data);
            var params = ['pc', 'fine', 'exp_pc', 'tax', 'od', 'money', 'exp_days', 'time', 'overpayments'];
            for (var d in params) {
                if (params[d] != 'exp_days') {
                    var val = calc_data[params[d]];
                    if (isNaN(val)) {
                        val = 0;
                    }
                    $('.debt-' + params[d] + '-ondate').text((val / 100).toFixed(2) + ' руб.');
                } else {
                    $('.debt-' + params[d] + '-ondate').text(calc_data[params[d]]);
                }
            }

            var currentTotalDebt = parseFloat($('#current-total-debt').text());
            var totalDebtOnDate = parseFloat(calc_data['money'] / 100);
            var diffOnDate = totalDebtOnDate - currentTotalDebt;

            $('.debt-diffpay-ondate').text(diffOnDate.toFixed(2) + ' руб.');

            if (typeof (calc_data['pays']) != "undefined" && calc_data['pays'] !== null) {
                var table_html = '';

                var fullpay_sum = 0;

                var open_debt = false;

                var debt_od_sum = 0;
                var debt_pc_sum = 0;
                var debt_exp_pc_sum = 0;
                var debt_fine_sum = 0;
                var debt_total = 0;

                for (var payment in calc_data['pays']) {
                    if (typeof calc_data['pays'][payment]['od'] === 'object') {
                        calc_data['pays'][payment]['od'] = 0;
                    }
                    if (typeof calc_data['pays'][payment]['pc'] === 'object') {
                        calc_data['pays'][payment]['pc'] = 0;
                    }
                    if (typeof calc_data['pays'][payment]['exp_pc'] === 'object') {
                        calc_data['pays'][payment]['exp_pc'] = 0;
                    }
                    if (typeof calc_data['pays'][payment]['fine'] === 'object') {
                        calc_data['pays'][payment]['fine'] = 0;
                    }

                    if (calc_data['pays'][payment]['expired'] == 1 && calc_data['pays'][payment]['closed'] == 1) {
                        var pay_sum = parseFloat(calc_data['pays'][payment]['total']);

                        table_html += '<tr style="background: #62DA9A;"><td>' + moment(calc_data['pays'][payment]['date']).format("DD.MM.YY") + '</td>';
                        table_html += '<td>-</td>';
                        table_html += '<td>-</td>';
                        table_html += '<td>' + calc_data['pays'][payment]['exp_pc'] + '</td>';
                        table_html += '<td>' + calc_data['pays'][payment]['fine'] + '</td>';
                        table_html += '<td>' + calc_data['pays'][payment]['days_overdue'] + '</td>';
                        table_html += '<td>' + pay_sum.toFixed(2) + '</td>';

                        debt_exp_pc_sum += parseFloat(calc_data['pays'][payment]['exp_pc']);
                        debt_fine_sum += parseFloat(calc_data['pays'][payment]['fine']);

                        fullpay_sum = fullpay_sum + parseFloat(calc_data['pays'][payment]['exp_pc']) + parseFloat(calc_data['pays'][payment]['fine']);
                        debt_total += parseFloat(calc_data['pays'][payment]['exp_pc']) + parseFloat(calc_data['pays'][payment]['fine']);
                    }

                    if (calc_data['pays'][payment]['expired'] == 1 && calc_data['pays'][payment]['closed'] == 0) {
                        open_debt = true;

                        fullpay_sum = fullpay_sum + parseFloat(calc_data['pays'][payment]['od']) + parseFloat(calc_data['pays'][payment]['pc']) + parseFloat(calc_data['pays'][payment]['exp_pc']) + parseFloat(calc_data['pays'][payment]['fine']);

                        debt_od_sum += parseFloat(calc_data['pays'][payment]['od']);
                        debt_pc_sum += parseFloat(calc_data['pays'][payment]['pc']);
                        debt_exp_pc_sum += parseFloat(calc_data['pays'][payment]['exp_pc']);
                        debt_fine_sum += parseFloat(calc_data['pays'][payment]['fine']);

                        var pay_sum = parseFloat(calc_data['pays'][payment]['od']) + parseFloat(calc_data['pays'][payment]['pc']) + parseFloat(calc_data['pays'][payment]['exp_pc']) + parseFloat(calc_data['pays'][payment]['fine']);

                        debt_total += parseFloat(pay_sum);

                        table_html += '<tr style="background: #FF7373;"><td>' + moment(calc_data['pays'][payment]['date']).format("DD.MM.YY") + '</td>';
                        table_html += '<td>' + calc_data['pays'][payment]['od'] + '</td>';
                        table_html += '<td>' + calc_data['pays'][payment]['pc'] + '</td>';
                        table_html += '<td>' + calc_data['pays'][payment]['exp_pc'] + '</td>';
                        table_html += '<td>' + calc_data['pays'][payment]['fine'] + '</td>';
                        table_html += '<td>' + calc_data['pays'][payment]['days_overdue'] + '</td>';
                        table_html += '<td>' + pay_sum.toFixed(2) + '</td>';
                    }

                    if (calc_data['pays'][payment]['expired'] == 0 && calc_data['pays'][payment]['closed'] == 1) {

                        table_html += '<tr style="background: #62DA9A;"><td>' + moment(calc_data['pays'][payment]['date']).format("DD.MM.YY") + '</td>';
                        table_html += '<td>-</td>';
                        table_html += '<td>-</td>';
                        table_html += '<td>-</td>';
                        table_html += '<td>-</td>';
                        table_html += '<td>-</td>';
                        table_html += '<td>' + calc_data['pays'][payment]['total'] + '</td>';
                    }

                    if (calc_data['pays'][payment]['expired'] == 0 && calc_data['pays'][payment]['closed'] == 0) {
                        if (open_debt) {
                            open_debt = false;
                            fullpay_sum = fullpay_sum + parseFloat(calc_data['pays'][payment]['pc']);

                            table_html += '<tr style="background: #FF7373;"><td></td><td><b>' + debt_od_sum.toFixed(2) + '</b></td><td><b>' + debt_pc_sum.toFixed(2) + '</b></td><td><b>' + debt_exp_pc_sum.toFixed(2) + '</b></td><td><b>' + debt_fine_sum.toFixed(2) + '</b></td><td><b>' + calc_data['exp_days'] + '</b></td><td><b>' + debt_total.toFixed(2) + '</b></td></tr>';
                        }

                        fullpay_sum = fullpay_sum + parseFloat(calc_data['pays'][payment]['od']);

                        table_html += '<tr><td>' + moment(calc_data['pays'][payment]['date']).format("DD.MM.YY") + '</td>';
                        table_html += '<td>' + calc_data['pays'][payment]['od'] + '</td>';
                        table_html += '<td>' + calc_data['pays'][payment]['pc'] + '</td>';
                        table_html += '<td>-</td>';
                        table_html += '<td>-</td>';
                        table_html += '<td>-</td>';
                        table_html += '<td>' + calc_data['pays'][payment]['total'] + '</td>';
                    }

                    table_html += '</tr>';
                }

                table_html += '<tr><td colspan="7" style="text-align: center;">Сумма для досрочного закрытия договора: <b>' + fullpay_sum.toFixed(2) + '</b></td></tr>';

                $('#schedule_current_rows').html(table_html);
            }
        }).always(function () {
            $.app.blockScreen(false);
        });
    };
    /**
     * получает данные по платежам для текущего пользователя и выводит в табличке
     * @returns {undefined}
     */
    $.debtorsCtrl.getUserPayments = function () {
        var holder = $('#userPaymentsHolder tbody');
        $.app.blockScreen(true);
        let data = $('#userPaymentsForm').serialize();
        $.post($.app.url + '/ajax/debtors/userpayments', data).done(function (data) {
            var html = '';
            var totalMoney = 0;
            //если в хмл пришел ноль то вывести ошибку
            if (data.result == '0') {
                $.app.blockScreen(false);
                $.app.openErrorModal('Ошибка', "Выберите корректный период! Введенный интервал не должен превышать месяца");
                return;
            }
            data.payments.forEach(function (item) {
                if (!isNaN(parseFloat(item.money))) {
                    html += '<tr><td>' + moment(item.date).format("DD.MM.YY") + '</td><td>' +
                        '<a href="/debtors/debtorcard/' + item.debtor_id + '" target="_blank" style="color: blue;">' + item.fio + '</a></td><td>' +
                        item.doc_number + '</td><td>' +
                        item.loan_id_1c + '</td><td>' +
                        (parseFloat(item.money / 100)).toFixed(2) +
                        ' руб. </td></tr>';
                    totalMoney += parseFloat(item.money);
                }
            });
            html += '<tr><td colspan="3"></td><td><b>Итого</b></td><td><b>' + (totalMoney / 100).toFixed(2) + ' руб.</b></td>';
            holder.html(html);
            $.app.blockScreen(false);
        }).error(function () {
            $.app.blockScreen(false);
            $.app.openErrorModal('Ошибка', "Невалидные данные");
            return;
        });
    };
    /**
     * подставляет дату в поиск
     * @param {type} date дата которую нужно подставить в поиск
     * @param {type} btn кнопка на которую нажали
     * @returns {Boolean}
     */
    $.debtorsCtrl.changeEventsDate = function (date, btn) {
        var dateString = moment(date, "DD.MM.YY").format('YYYY-MM-DD');
        $('#debtorEventsFilter [name="search_field_debtor_events@date"]').val(dateString);
        $.debtorsCtrl.selectTotalPlannedColumn(btn);
        $.debtorsCtrl.eventsTableCtrl.filterTable();
        return false;
    };
    /**
     * подсвечивает столбец с общем планировании
     * @param {type} btn кнопка на которую нажали
     * @returns {undefined}
     */
    $.debtorsCtrl.selectTotalPlannedColumn = function (btn) {
        var th = $(btn).parents('th:first').index() + 1;
        var $table = $(btn).parents('.table:first');
        var selectedClass = 'bg-primary';
        var selectedBtnClass = 'planned-table-selected-btn';
        $('.' + selectedBtnClass).removeClass('selectedBtnClass');
        $(btn).addClass('.' + selectedBtnClass);
        $table.find('.' + selectedClass).removeClass(selectedClass);
        $table.find('td:nth-child(' + th + '),th:nth-child(' + th + ')').addClass(selectedClass);
    };
    $.debtorsCtrl.changeDebtorTransferFilter = function () {
        $(document).on('click', '#debtorTransferFilterButton', function () {
            //$(document).on('change', '#debtorTransferFilter [name="old_user_id"], #debtorTransferFilter [name="new_user_id"], #debtorTransferFilter [name="debt_group_id"], #debtorTransferFilter [name="act_number"], #debtorTransferFilter [name="overdue_from"], #debtorTransferFilter [name="overdue_till"], #debtorTransferFilter [name="search_field_cities@id"], #debtorTransferFilter [name="base"]', function(){
            var data = $('#debtorTransferFilter').serialize();
            $.get($.app.url + '/ajax/debtors/transfer/list', data).done(function () {

            });
        });
        return false;
    };
    /**
     * Инициализация таблицы передачи должников
     * @returns {undefined}
     */
    $.debtorsCtrl.initDebtorTransferTable = function () {
        $.debtorsCtrl.debtorTransferTableCtrl = new TableController('debtortransfer', [
                {data:'actions', name: 'actions', searchable: false, orderable: false},
                {data:'links', name: 'links', searchable: false, orderable: false},
                {data:'passports_fio', name: 'passports_fio'},
                {data:'debtors_od', name: 'debtors_od'},
                {data:'debtors_base', name: 'debtors_base'},
                {data:'passports_fact_address_city', name: 'passports_fact_address_city'},
                {data:'debtors_fixation_date', name: 'debtors_fixation_date'},
                {data:'debtors_qty_delays', name: 'debtors_qty_delays'},
                {data:'debtors_responsible_user_id_1c', name: 'debtors_responsible_user_id_1c'},
                {data:'debtors_last_user_id', name: 'debtors_last_user_id'},
                {data: 'debtors_debt_group_id', name: 'debtors_debt_group_id'},
                {data: 'debtors_str_podr', name: 'debtors_str_podr'}
            ],
            {
                dtData: {
                    scrollY: 600,
                    scrollCollapse: false,
                    lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "Все"]]
                },
                clearFilterBtn: $('#debtorTransferClearFilterBtn'),
                filterBtn: $('#debtorTransferFilterButton'),
                filterHolder: $('#debtorTransferFilter')
//            repeatLastSearchBtn: $('#repeatLastSearchBtn'),
            }
        );
        $('#allDebtorsCheckToggler').change(function () {
            $('#debtortransferTable input[name="debtor_checkbox_id[]"]').prop('checked', $(this).prop('checked')).change();
        });
    };
    /**
     * Инициализация таблицы должников в передаче должников
     * @returns {undefined}
     */
    $.debtorsCtrl.changeDebtorTransferTable = function () {
        $(document).on('change', 'input[name="debtor_checkbox_id[]"]', function () {
            var cnt = 0;
            var required = false;
            $('input[name="debtor_checkbox_id[]"]').each(function () {
                if ($(this).is(':checked')) {
                    cnt++;
                }
            });

            if ($('#new_user_id').val() != '' && $('input[name="search_field_users@id"]').val() != '' && $('input[name="search_field_debtors@base"]').val() != '') {
                required = true;
            }

            if (cnt > 0 && required) {
                $('#changeResponsibleUser').attr('disabled', false);
                $('#printResponsibleUser').attr('disabled', false);
            } else {
                $('#changeResponsibleUser').attr('disabled', true);
                $('#printResponsibleUser').attr('disabled', true);
            }
        });
    };
    $.debtorsCtrl.debtorsCounter = function () {
        $(document).on('change', 'input[name="debtor_checkbox_id[]"], #allDebtorsCheckToggler', function () {
            $('#debtorsCounter').html($('input[name="debtor_checkbox_id[]"]:checked').length);
        });
    };
    $.debtorsCtrl.changeDebtorMassSendFilter = function () {
        $(document).on('click', '#debtorMassSendFilterButton', function () {
            //$(document).on('change', '#debtorTransferFilter [name="old_user_id"], #debtorTransferFilter [name="new_user_id"], #debtorTransferFilter [name="debt_group_id"], #debtorTransferFilter [name="act_number"], #debtorTransferFilter [name="overdue_from"], #debtorTransferFilter [name="overdue_till"], #debtorTransferFilter [name="search_field_cities@id"], #debtorTransferFilter [name="base"]', function(){
            var data = $('#debtorMassSendFilter').serialize();
            $.get($.app.url + '/ajax/debtormasssms/list', data).done(function () {

            });
        });
        return false;
    };

    $.debtorsCtrl.initDebtorMassSmsTable = function () {
        $.debtorsCtrl.debtorTransferTableCtrl = new TableController('debtormasssms', [
                {data: 'actions', name: 'actions', searchable: false, orderable: false},
                {data: 'links', name: 'links', searchable: false, orderable: false},
                {data: 'passports_fio', name: 'passports_fio'},
                {data: 'debtors_od', name: 'debtors_od'},
                {data: 'debtors_base', name: 'debtors_base'},
                {data: 'passports_fact_address_city', name: 'passports_fact_address_city'},
                {data: 'debtors_fixation_date', name: 'debtors_fixation_date'},
                {data: 'debtors_qty_delays', name: 'debtors_qty_delays'},
                {data: 'debtors_responsible_user_id_1c', name: 'debtors_responsible_user_id_1c'},
                {data: 'debtors_debt_group_id', name: 'debtors_debt_group_id'},
                {data: 'debtors_str_podr', name: 'debtors_str_podr'},
            ],
            {
                dtData: {
                    scrollY: 600,
                    scrollCollapse: false,
                    lengthMenu: [[-1],['Все']],
                },
                rowId : 'DT_RowID',
                clearFilterBtn: $('#debtorMassSmsClearFilterBtn'),
                filterBtn: $('#debtorMassSendFilterButton'),
                filterHolder: $('#debtorMassSendFilter')
            }
        );
        $('#allDebtorsCheckToggler').change(function () {
            $('#debtormasssmsTable input[name="debtor_checkbox_id[]"]').prop('checked', $(this).prop('checked')).change();
        });
    };
    /**
     * Инициализация таблицы забытых должников
     * @returns {undefined}
     */
    $.debtorsCtrl.initDebtorForgottenTable = function () {
        $.debtorsCtrl.debtorforgotten = new TableController('debtorforgotten', [
                {data: 'links', name: 'links', searchable: false, orderable: false},
                {data: 'fixation_date', name: 'fixation_date'},
                {data: 'passports_fio', name: 'passports_fio'},
                {data: 'debtors_username', name: 'debtors_username'},
                {data: 'str_podr', name: 'str_podr'},
            ],
            {
                dtData: {
                    scrollY: 600,
                    scrollCollapse: false,
                    lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "Все"]],
                },
                filterBtn: $('#forgottenDebtorFilterButton'),
                filterHolder: $('#forgottenDebtorFilter')
            }
        );
    };
    /**
     * Инициализация таблицы должников с рекомендациями
     * @returns {undefined}
     */
    $.debtorsCtrl.initDebtorRecommendsTable = function () {
        $.debtorsCtrl.debtorrecommends = new TableController('debtorrecommends', [
                {data:'links', name: 'links', searchable: false, orderable: false},
                {data:'debtors_fixation_date', name: 'debtors_fixation_date'},
                {data:'passports_fio', name: 'passports_fio'},
                {data:'debtors_loan_id_1c', name: 'debtors_loan_id_1c'},
                {data:'debtors_qty_delays', name: 'debtors_qty_delays'},
                {data:'debtors_sum_indebt', name: 'debtors_sum_indebt'},
                {data:'debtors_od', name: 'debtors_od'},
                {data:'debtors_base', name: 'debtors_base'},
                {data:'customers_telephone', name: 'customers_telephone'},
                {data:'debtors_debt_group_id', name: 'debtors_debt_group_id'},
                {data: 'debtors_responsible_user_id_1c', name: 'debtors_responsible_user_id_1c'},
                {data: 'debtors_str_podr', name: 'debtors_str_podr'},
                {data: 'debtors_rec_completed', name: 'debtors_rec_completed'},
            ],
            {
                dtData: {
                    scrollY: 600,
                    scrollCollapse: false,
                    lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "Все"]]
                },
                //clearFilterBtn: $('#debtorTransferClearFilterBtn'),
                filterBtn: $('#recommendsDebtorFilterButton'),
                filterHolder: $('#recommendsDebtorFilter')
//            repeatLastSearchBtn: $('#repeatLastSearchBtn'),
            }
        );
        //$('#allDebtorsCheckToggler').change(function () {
        //    $('#debtortransferTable input[name="debtor_transfer_id[]"]').prop('checked', $(this).prop('checked')).change();
        //});
    };
    /**
     * Передача должников
     * @returns {undefined}
     */
    $.debtorsCtrl.changeResponsibleUser = function () {
        $(document).on('click', '#changeResponsibleUser', function () {
            var checked = [];
            $('input[name="debtor_checkbox_id[]"]:checked').each(function () {
                checked.push($(this).val());
            });
            var postData = {
                new_user_id: $('#new_user_id').val(),
                old_user_id: $('#old_user_id').val(),
                act_number: $('input[name="act_number"]').val(),
                debtor_ids: checked
            };
            //если поле автокомплита не равно значению в скрытом поле под автокомплитом то не отправлять базу
            if ($('#debtorTransferAction [name="debtors@base"]').val() == $('[name="search_field_debtors@base"]').val()) {
                postData.base = $('[name="search_field_debtors@base"]').val();
            }
            $.post($.app.url + '/ajax/debtors/transfer/changeResponsibleUser', postData).done(function (data) {
                $.app.ajaxResult(data);
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
            });
            return false;
        });

        $(document).on('click', '#printResponsibleUser', function () {
            var checked = [];
            $('input[name="debtor_checkbox_id[]"]:checked').each(function () {
                checked.push($(this).val());
            });

            var serialized = $('input[name="debtor_checkbox_id[]"]:checked').serialize().replace(/%5B%5D/g, '[]');

            $.get(window.open($.app.url + '/ajax/debtors/transfer/printResponsibleUser/?new_user_id=' + $('#new_user_id').val() + '&old_user_id=' + $('#old_user_id').val() + '&act_number=' + $('input[name="act_number"]').val() + '&' + serialized), {}).done(function (data) {

            });
            return false;
        });
    };

    /**
     *
     * @returns boolean
     */
    $.debtorsCtrl.changePersonalData = function () {
        $(document).on('click', '.change-personal-data', function () {
            var elem = $(this);
            var action = elem.data('action');
            var link = elem.data('link');

            $.post(link + '/' + action).done(function () {
                if (action === 'on_non_interaction' || action === 'on_by_agent' || action === 'on_recall_personal_data' || action === 'on_non_interaction_nf') {
                    var actWord = action.substr(3);
                    elem.removeClass('btn-default');
                    elem.addClass('btn-danger');
                    elem.data('action', 'off_' + actWord);
                    elem.attr('style', 'color: #ffffff');
                }

                if (action === 'off_non_interaction' || action === 'off_by_agent' || action === 'off_recall_personal_data' || action === 'off_non_interaction_nf') {
                    var actWord = action.substr(4);
                    elem.removeClass('btn-danger');
                    elem.addClass('btn-default');
                    elem.data('action', 'on_' + actWord);
                    elem.removeAttr('style');
                }
            });

            return false;
        });
    };
    /**
     * Изменение флага загрузки данных по должнику
     * @returns boolean
     */
    $.debtorsCtrl.changeLoadStatus = function () {
        $(document).on('click', '.loadFlag', function () {
            var elem = $(this);
            $.get(elem.attr('href')).done(function (data) {
                if (data == 1) {
                    if (elem.children().hasClass('glyphicon-ok')) {
                        elem.children().removeClass('glyphicon-ok');
                        elem.children().addClass('glyphicon-remove');
                    } else {
                        elem.children().removeClass('glyphicon-remove');
                        elem.children().addClass('glyphicon-ok');
                    }
                }
                $.app.ajaxResult(data);
            });
            return false;
        });
    }
    $.debtorsCtrl.uploadOrders = function (debtor_id) {
        $.app.blockScreen(true);
        $.post($.app.url + '/ajax/debtors/orders/upload', {debtor_id: debtor_id}).done(function (data) {
            var json = $.parseJSON(data);
            var $tableBody = $('.debtor-payments-table tbody');
            var tBodyStr = '';
            json.forEach(function (item) {
                tBodyStr += '<tr><td>' + item.number + '</td>'
                    + '<td>' + item.created_at + '</td>'
                    + '<td>' + item.doc + '</td>'
                    + '<td>' + item.outcome + '</td>'
                    + '<td>' + item.income + '</td>'
                    + '<td>' + item.purpose + '</td></tr>';
            });
            $tableBody.html(tBodyStr);
        }).always(function () {
            $.app.blockScreen(false);
        });
    };
    $.debtorsCtrl.updateLoan = function () {
        $(document).on('click', '.update-loan', function () {
            var elem = $(this);

            elem.prop('disabled', true);

            var arm_loan_id = elem.data('loanid');
            var debtor_id = elem.data('debtorid');
            var loan_id_1c = elem.data('loanid1c');

            $.post($.app.url + '/debtors/loans/summary/updateloan', {
                debtor_id: debtor_id,
                arm_loan_id: arm_loan_id,
                loan_id_1c: loan_id_1c
            }).done(function (data) {
                if (data == '1') {
                    elem.remove();
                } else {
                    elem.prop('disabled', false);
                }
            });
        });
    };
    $.debtorsCtrl.submitEventsFilter = function () {
        $('#debtorEventsClearFilterBtn').bind('click', function () {
            $.post($.app.url + '/ajax/debtors/totalEvents', {}).done(function (data) {
                $('#totalDebtorEvents').html(data);
            });
        });

        $(document).on('click', '#debtorEventsFilterBtn', function () {
            var id1c = $('#debtorEventsSearchModal input[name="search_field_users@id_1c"]').val();
            if (id1c.length > 0) {
                $.post($.app.url + '/ajax/debtors/totalEvents', {user_id_1c: id1c}).done(function (data) {
                    if (data != '0') {
                        $('#totalDebtorEvents').html(data);
                    }
                });
            }
        });
    };
    $.debtorsCtrl.debtorsTotalPlanned = function (userId) {
        $.get($.app.url + '/ajax/debtors/total-planned', {userId: userId}).done(function (data) {
            $('#totalNumberPlaned').html(data);
        });
    };
    $.debtorsCtrl.debtorsToExcel = function () {
        window.open($.app.url + '/debtors/export?' + $('#debtorsFilter form').serialize());
    };
    $.debtorsCtrl.debtorsEventsToExcel = function () {
        window.open($.app.url + '/debtors/export/events?' + $('#debtorEventsFilter form').serialize());
    };
    $.debtorsCtrl.forgottenToExcel = function () {
        window.open($.app.url + '/debtors/export/forgotten?' + $('#forgottenDebtorFilter form').serialize());
    };

    $.debtorsCtrl.emailMessagesList = function (user_id){
        $.app.blockScreen(true);
        $.get($.app.url + '/debtors/emails/list/' +user_id)
            .done((response) => {
            $.app.openModal('Email',response);
        }).always(()=>{
            $.app.blockScreen(false);
        });
    }

    $.app.openModal = function (title, msg) {
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

    $.debtorsCtrl.intiInputModal = function (element) {
        let idList = $(element).val();
        if (idList == 10 || idList == 20 || idList == 15) {
            $('#datePayment').show();
            $('#datePaymentLabel').text('Оплатите задолженность до :');
            $('#discountPayment').hide();
            $('#discountPaymentLabel').text('');
            $('#dateAnswer').hide();
            $('#dateAnswerLabel').text('');
        } else if (idList == 18) {
            $('#datePayment').show();
            $('#datePaymentLabel').text('Предложение доступно до :');
            $('#discountPayment').show();
            $('#discountPaymentLabel').text('внесите руб :');
            $('#dateAnswer').show();
            $('#dateAnswerLabel').text('ДАЙТЕ ОТВЕТ до :');
        } else {
            $('#datePayment').hide();
            $('#datePaymentLabel').text('');
            $('#discountPayment').hide();
            $('#discountPaymentLabel').text('');
            $('#dateAnswer').hide();
            $('#dateAnswerLabel').text('');
        }

    };
})(jQuery);
