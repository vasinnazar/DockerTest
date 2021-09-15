(function ($) {
    $.testerCtrl = {
        lastCsvFileData: null,
        csvFileTable: null,
        sentReqNum: 0,
        responsedReqNum: 0,
        additionalParams: ['start_time', 'end_time', 'actions', 'response'],
    };
    $.testerCtrl.init = function () {
        var exchangeArmDataGrid = new DataGridController($('#sendTestExchangeForm [name="data"]').val(), {
            table: $("#exchangeArmDataTable"),
            form: $('#sendTestExchangeForm'),
            dataField: $('#sendTestExchangeForm [name="data"]')
        });

        $('#sendTestExchangeForm').submit(function (e) {
            e.preventDefault();
            $.post($.app.url + '/ajax/adminpanel/tester/exchangearm', $(this).serialize()).done(function (data) {
                $('#exchangeArmResponse').val(data);
            });
        });
    };
    /**
     * инициализация страницы с нагрузочным тестом
     * @returns {undefined}
     */
    $.testerCtrl.loadTestInit = function () {
        $('#csvFileForm [name="csv_file"]').change(function () {
            $.testerCtrl.parseCsvFile(this);
        });
        $('#csvFileSendTo1cBtn').click(function () {
            $.testerCtrl.sendDataTableTo1c($.testerCtrl.lastCsvFileData);
        });
        $('#csvFileTableRefreshBtn').click(function () {
            $.testerCtrl.updateCsvDataTable();
        });
    };
    /**
     * отправить данные в 1с
     * @param {type} sendData
     * @returns {undefined}
     */
    $.testerCtrl.sendDataTableTo1c = function (sendData) {
        var length = $('[name="DataTables_Table_0_length"]').val();
        if (length === "-1") {
            length = sendData.length;
        }
        var formdata = $("#soapParamsForm").serializeArray();
        var postParams = {};
        $(formdata).each(function (index, obj) {
            postParams[obj.name] = obj.value;
        });
        postParams['module_1c'] = $('[name="function_1c"] option:selected').parents('optgroup:first').attr('label');
        var onSoapDone = function (data_id) {
            return function (data) {
                sendData[data_id].response = JSON.stringify(data);
                sendData[data_id].end_time = moment().format('DD.MM.YYYY hh:mm:ss');
                sendData[data_id].actions = (sendData[data_id].response !== '') ? '<span class="label label-success">Да</span>' : '<span class="label label-danger">Нет</span>';
                sendData[data_id].actions += ' <button type="button" class="btn btn-default" onclick="$.testerCtrl.viewResponse(' + data_id + ');"><span class="glyphicon glyphicon-eye-open"></span></button>';
                $.testerCtrl.responsedReqNum++;
                if ($.testerCtrl.sentReqNum === $.testerCtrl.responsedReqNum) {
                    $('#csvFileSendTo1cBtn').prop('disabled', false);
                    $.testerCtrl.updateCsvDataTable();
                }
            };
        };
        $.testerCtrl.sentReqNum = 0;
        $.testerCtrl.responsedReqNum = 0;
        $('#csvFileSendTo1cBtn').prop('disabled', true);
        for (var i = 0; i < length; i++) {
            postParams['params'] = sendData[i];
            $.testerCtrl.additionalParams.forEach(function (ap) {
                delete postParams['params'][ap];
            });
            $.testerCtrl.sentReqNum++;
            sendData[i].start_time = moment().format('DD.MM.YYYY hh:mm:ss');
            $.post($.app.url + '/ajax/adminpanel/tester/soaptest', postParams, onSoapDone(i));
        }
    };
    /**
     * Открыть ответ от 1с
     * @param {type} data_id
     * @returns {undefined}
     */
    $.testerCtrl.viewResponse = function (data_id) {
        var $modalBody = $('#viewLogModal .modal-body');
        $modalBody.empty();
        $modalBody.text($.testerCtrl.lastCsvFileData[data_id].response);
        $('#viewLogModal').modal('show');
    };
    /**
     * парсит цсв файл выбранный в файловом инпуте
     * добавляет ко всем строкам поле для ответа 1с
     * @param {type} fileInput
     * @returns {undefined}
     */
    $.testerCtrl.parseCsvFile = function (fileInput) {
        var file = fileInput.files[0];
        Papa.parse(file, {
            header: true,
            dynamicTyping: false,
            complete: function (results) {
                console.log(results);
                var data = [];
                var fields = results.meta.fields;
                var fieldsLength = Object.keys(fields).length;
                $.testerCtrl.additionalParams.forEach(function (i) {
                    fields.push(i);
                });
                for (var r in results.data) {
                    if (Object.keys(results.data[r]).length === fieldsLength) {
                        var item = {};
                        for (var f in results.data[r]) {
                            item[f] = results.data[r][f].toString();
                        }
                        $.testerCtrl.additionalParams.forEach(function (i) {
                            item[i] = '';
                        });
                        data.push(item);
                    }
                }
                $.testerCtrl.lastCsvFileData = data;
                $.testerCtrl.previewCsvFile(fields, data, $('#csvFileDataHolder').get(0));
                $('#csvFileSendTo1cBtn,#csvFileTableRefreshBtn,#csvExportBtn').prop('disabled', false);
            }
        });
    };
    /**
     * обновить таблицу 
     * @returns {undefined}
     */
    $.testerCtrl.updateCsvDataTable = function () {
        $.testerCtrl.csvFileTable.clear();
        $.testerCtrl.csvFileTable.rows.add($.testerCtrl.lastCsvFileData);
        $.testerCtrl.csvFileTable.draw();
    };
    /**
     * отобразить цсв файл в таблице
     * @param {type} fields
     * @param {type} data
     * @param {type} holder
     * @returns {undefined}
     */
    $.testerCtrl.previewCsvFile = function (fields, data, holder) {
        var html = '<table class="table table-condensed table-stripped">';
        html += '<thead><tr>';
        for (var f in fields) {
            html += '<th>' + fields[f] + '</th>';
        }
        html += '</tr></thead>';
        html += '<tbody>';
        html += '</tbody></table>';
        $(holder).html(html);
        var cols = [];
        for (var f in fields) {
            cols.push({
                data: fields[f]
            });
        }
        $(holder).find('table').DataTable({
            data: data,
            columns: cols,
            columnDefs: [
                {
                    "targets": [cols.length - 1],
                    "visible": false
                }
            ],
            lengthMenu: [[5, 25, 100, -1], [5, 25, 100, 'all']],
            language: dataTablesRuLang,
        });
        $.testerCtrl.csvFileTable = $(holder).find('table').dataTable().api();
    };
    /**
     * сохраняет сконвертированные в цсв данные в файл
     * @param {type} args
     * @returns {unresolved|undefined}
     */
    $.testerCtrl.exportToCsv = function (args) {
        var data, filename, link;
        data = $.testerCtrl.lastCsvFileData;
        if (data == null || !data.length) {
            console.log('data is null', data);
            return null;
        }
        var csv = $.testerCtrl.convertToCsv({data: data});
        if (csv == null) {
            console.log('csv is null', csv);
            return;
        }

        filename = 'export.csv';

        if (!csv.match(/^data:text\/csv/i)) {
            csv = 'data:text/csv;charset=utf-8,' + csv;
        }
        data = encodeURI(csv);

        link = document.createElement('a');
        link.setAttribute('href', data);
        link.setAttribute('download', filename);
        link.click();
    };
    /**
     * конвертирует объект в цсв
     * @param {type} args
     * @returns {unresolved|String}
     */
    $.testerCtrl.convertToCsv = function (args) {
        var result, ctr, keys, columnDelimiter, lineDelimiter, data;
        data = args.data || null;
        if (data == null || !data.length) {
            console.log('data is null', data);
            return null;
        }

        columnDelimiter = args.columnDelimiter || ',';
        lineDelimiter = args.lineDelimiter || '\n';

        keys = Object.keys(data[0]);

        result = '';
        result += keys.join(columnDelimiter);
        result += lineDelimiter;

        data.forEach(function (item) {
            ctr = 0;
            keys.forEach(function (key) {
                if (ctr > 0)
                    result += columnDelimiter;
                if (key != 'response' && key != 'actions') {
                    result += item[key];
                }
                ctr++;
            });
            result += lineDelimiter;
        });

        return result;
    };
})(jQuery);