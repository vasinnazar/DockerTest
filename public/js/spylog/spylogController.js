(function ($) {
    $.spylogCtrl = {};
    $.spylogCtrl.init = function () {
        $('[name="date_from"],[name="date_to"]').datepicker({
            language: 'ru',
            autoclose: true
        });
        $.spylogCtrl.table = $('#spylogTable').DataTable({
            order: [[0, "desc"]],
            searching: false,
            sDom: 'Rfrtlip',
            lengthMenu: [[100, 50, 25], [100, 50, 25]],
            language: dataTablesRuLang,
            processing: true,
            serverSide: true,
            ajax: {
                url: armffURL + '/ajax/spylog/list',
                data: function (d) {
                    d.name = $('#spylogFilter input[name="name"]').val();
                    d.date_from = $('#spylogFilter input[name="date_from"]').val();
                    d.date_to = $('#spylogFilter input[name="date_to"]').val();
                    d.date_to = $('#spylogFilter input[name="date_to"]').val();
                    d.table = $('#spylogFilter select[name="table"]').val();
                    d.doc_id = $('#spylogFilter input[name="doc_id"]').val();
                    d.action = $('#spylogFilter select[name="action"]').val();
                }
            },
            columns: [
                {data: '0', name: 'log_created_at'},
                {data: '1', name: 'username'},
                {data: '2', name: 'log_table'},
                {data: '3', name: 'log_doc_id'},
                {data: '4', name: 'log_action'},
                {data: '5', name: 'view', orderable: false, searchable: false},
            ]
        });
    };
    $.spylogCtrl.filterLogs = function () {
        $.spylogCtrl.table.draw();
    };
    $.spylogCtrl.repeatLog = function (log_id) {
        $.post(armffURL + '/ajax/spylog/repeat', {id: log_id}).done(function (data) {
            $.app.ajaxResult(data);
        });
    };
    $.spylogCtrl.viewLog = function ($log_id, isChange, logAction) {
        $('#viewLogModal .modal-body').empty();
        $('#viewLogModal').modal();
        $.post(armffURL + '/ajax/spylog/view/' + $log_id).done(function (data) {
            if (data) {
                try {
                    var html, table, col, d, json = $.parseJSON(data);
                    html = '<div class="row">';
                    if (isChange) {
                        for (table in json) {
                            html += '<div class="col-xs-12">\n\
                                <p>Таблица: ' + table + '; Действие: ' + json[table]['action'] + '; </p>';
                            html += '<div class="row">';
                            if (json[table]['action'] == '1') {
                                html += '<div class="col-xs-12">';
                                html += '<table class="table table-condensed table-striped">\n\
                                            <thead><tr><th>Поле:</th><th>До:</th><th>После:</th></tr></thead><tbody>';
                                for (col in json[table]['data']['after']) {
                                    d = json[table]['data'];
                                    html += '<tr class="'
                                            + ((d['before'][col] != d['after'][col]) ? 'warning' : '')
                                            + '"><td>' + col + '</td><td>'
                                            + d['before'][col] + '</td><td>'
                                            + d['after'][col] + '</td></tr>';
                                }
                                html += '</tbody></table></div>';
                                html += '</div>';
                            } else {
                                html += '<div class="col-xs-12">';
                                html += '<table class="table table-condensed table-striped">\n\
                                            <thead><tr><th>Поле:</th><th>Значение:</th></tr></thead><tbody>';
                                for (col in json[table]['data']) {
                                    html += '<tr><td>' + col + '</td><td>' + json[table]['data'][col] + '</td></tr>';
                                }
                                html += '</tbody></table></div>';
                                html += '</div>';
                            }
                        }
                    } else {
                        //если лог запроса в 1с или лог ошибки или лог создания
//                        if (logAction == 17 || logAction == 15 || logAction == 3 || logAction == 2 || logAction == 18) {
                        if ($.inArray(logAction, [17, 15, 3, 2, 18, 19, 20, 21, 22, 23, 24, 25, 26])) {
                            html += '<div class="col-xs-12">';
                            html += $.spylogCtrl.addLogRow(json);
                            html += '</div>';
                        } else {
                            html += '<div class="col-xs-12">';
                            html += JSON.stringify(json);
                            html += '</div>';
                        }
                    }
                    html += '</div>';
                    $('#viewLogModal .modal-body').html(html);
                } catch (e) {
                    $('#viewLogModal .modal-body').html(data);
                }
            }
        });
    };
    //добавляет строку в лог
    $.spylogCtrl.addLogRow = function (json) {
        var html = '<table class="table table-bordered table-condensed">';
        for (var e1 in json) {
            html += '<tr>';
            html += '<td width="100px;">' + e1 + '</td>';
            html += '<td>';
            if (typeof (json[e1]) === "object") {
                html += $.spylogCtrl.addLogRow(json[e1]);
            } else {
                html += json[e1];
                if (e1 == 'loan_id') {
                    html += ' <a href="' + armffURL + 'loans/summary/' + json[e1] + '" target="_blank">Открыть</a>';
                }
            }
            html += '</td>';
            html += '</tr>';
        }
        html += '</table>';
        return html;
    };
})(jQuery);