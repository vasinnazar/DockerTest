/**
 * 
 * @param {type} data данные в джейсоне для заполнения таблицы
 * @param {type} props объект со свойствами table - jquery объект таблицы, form - jquery объект формы в которой находится поле, в которое пишется джейсон данных собранных с таблицы
 * @returns {DataGridController}
 */
DataGridController = function (data, props) {
    this.initData = (typeof (data) === "undefined" || data == "") ? [] : $.parseJSON(data);
    this.props = ($.isPlainObject(props)) ? props : {};
    this.$table = ('table' in this.props) ? this.props.table : null;
    this.$form = ('form' in this.props) ? this.props.form : null;
    if (typeof (this.$form) !== "undefined") {
        this.$form.data("dataGrid", this);
    }
    this.$dataField = ('dataField' in this.props) ? this.props.dataField : ((this.$form !== null) ? this.$form.find('[name="data"]') : null);
    this.init();
};
DataGridController.prototype.init = function () {
    if (this.$table !== null) {
        if (typeof (this.initData) !== 'undefined' && this.initData != "") {
            this.fill(this.initData);
        } else {
            this.addEvents();
        }
    }
    if (this.$form !== null) {
        this.$form.submit(function (e) {
            if (typeof ($(this).data('dataGrid')) !== "undefined") {
                $(this).data('dataGrid').$dataField.val(JSON.stringify($(this).data('dataGrid').getGridJSON()));
            }
        });
    }
};
DataGridController.prototype.fill = function (data) {
    var $row,$insertedRow, r, f = '';
    if (data) {
        for (r in data) {
            $row = this.$table.find('tbody tr:first').clone();
            this.$table.find('tbody').append($row);
            $insertedRow = this.$table.find('tr:last');
            if ("onAddEvent" in this.props) {
                this.props.onAddEvent($insertedRow);
            }
            for (f in data[r]) {
                var $field = $insertedRow.find('[name="' + f + '"]');
                $field.val(data[r][f]);
                if ($field.attr('type') == 'date') {
                    $field.val(moment(data[r][f]).format('YYYY-MM-DD'));
                }
                $field.find('option[value=\'' + data[r][f] + '\']').attr('selected', 'selected');
//                var autocompleteField = this.$table.find('tr:last [name="' + f + '_autocomplete"]');
//                if (autocompleteField.length > 0) {
//                    (function (field) {
//                        $.get($.app.url + '/ajax/autocomplete/label', {table: field.attr('data-autocomplete'), id: data[r][f]}).done(function (data) {
//                            field.val(data);
//                        });
//                    })(autocompleteField);
//                }
            }
        }
        this.$table.find('tbody tr:first').remove();
    }
    this.addEvents();
};
DataGridController.prototype.addEvents = function () {
    var $row = this.$table.find('tbody tr:first').clone();
    var dataGridCtrl = this;
    this.$table.find('button[name="remove"]').click(function () {
        dataGridCtrl.deleteRow($(this));
    });
    this.$table.find('button[name="add"]').click(function () {
        dataGridCtrl.addRow($(this));
    });
    this.$table.find('input[name="money"],.my-money').myMoney();
};
DataGridController.prototype.deleteRow = function (btn) {
    if (btn.parents('tbody').find('tr').length > 1) {
        btn.parents('tr:first').remove();
    } else {
        btn.parents('tr:first').find('input:not([type="button"]),select').val('');
    }
    return false;
};
DataGridController.prototype.addRow = function (btn) {
    var dataGridCtrl = this;
    var dataGridProps = this.props;
    var copyEvents = ("copyEvents" in dataGridProps && dataGridProps.copyEvents == false) ? false : true;
    var $row = btn.parents('tr:first').clone(copyEvents);
    $row.find('input:not([type="button"]),select').not('[readonly],[disabled]').val('');
    $row.find('input[name="money"]').myMoney();
    btn.parents('tr:first').after($row);
    var $insertedRow = btn.parents('tr:first').next('tr');
    if (!copyEvents) {
        this.$table.find('button[name="remove"]').click(function () {
            dataGridCtrl.deleteRow($(this));
        });
        this.$table.find('button[name="add"]').click(function () {
            dataGridCtrl.addRow($(this));
        });
    }
    $insertedRow.find('.money').myMoney();
    if ("onAddEvent" in dataGridProps) {
        dataGridProps.onAddEvent($insertedRow);
    }
    $.app.setAutocomplete($insertedRow.find('[data-autocomplete]'));
    return $insertedRow;
};
DataGridController.prototype.getGridJSON = function () {
    var json = {}, i = 0;
    if (this.$table === null) {
        return [];
    }
    this.$table.find('tbody>tr').each(function () {
        json[i] = {};
        $(this).find('input:not([type="button"]),select').not('[disabled]').each(function () {
            json[i][$(this).attr('name')] = $(this).val().replace('/"/g', '\"').replace('/\\/g', '\\\\');
        });
        i++;
    });
    return json;
};
DataGridController.prototype.updateDataField = function () {
    this.$dataField.val(JSON.stringify(this.getGridJSON()));
};
