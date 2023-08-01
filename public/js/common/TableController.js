/**
 * 
 * @param {string} name
 * @param {array} columns [{data:0,name:'name'}]
 * @param {object} props {filterHolder:jquery.element,clearFilterBtn:jquery.element,table:jquery.element,listURL:'ajax/.../list',order:[[column index, ("asc","desc")],...]}
 * @returns {TableController}
 */
TableController = function (name, columns, props) {
    this.name = name;
    this.props = ($.isPlainObject(props)) ? props : {};
    this.$filterHolder = ('filterHolder' in this.props) ? this.props.filterHolder : $('#' + name + 'Filter');
    this.$clearFilterBtn = ('clearFilterBtn' in this.props) ? this.props.clearFilterBtn : $('#clearFilterBtn');
    this.$filterBtn = ('filterBtn' in this.props) ? this.props.filterBtn : $('#' + name + 'FilterBtn');
    this.$lastSearchBtn = ('repeatLastSearchBtn' in this.props) ? this.props.repeatLastSearchBtn : $('#repeatLastSearchBtn');
    this.$table = ('table' in this.props) ? this.props.table : $('#' + name + 'Table');
    this.columns = columns;
    this.lastSearchBtnDefLabel = "Повторить последний запрос";
    this.init();
};
TableController.prototype.init = function () {
    var ctrl = this;
    console.log(this.name)
    var dtData = {
        order: ('order' in this.props) ? this.props.order : [[0, "desc"]],
        searching: false,
        sDom: 'Rfrtlip',
        lengthMenu: [[25, 50, 100], [25, 50, 100]],
        language: dataTablesRuLang,
        processing: true,
        serverSide: true,
        ajax: {
            url: armffURL + (('listURL' in this.props) ? this.props.listURL : ('ajax/' + this.name + '/list')),
            data: function (d) {
                ctrl.$filterHolder.find('input,select').each(function () {
                    if ($(this).attr('type') == 'checkbox') {
                        d[$(this).attr('name')] = ($(this).prop('checked')) ? 1 : 0;
                    } else {
                        d[$(this).attr('name')] = $(this).val();
                    }
                });
            }
        },
        columns: this.columns
    };
    this.$clearFilterBtn.click(function () {
        ctrl.clearFilter();
        return false;
    });
    this.$filterBtn.click(function () {
        ctrl.filterTable();
    });
    this.$lastSearchBtn.click(function () {
        ctrl.repeatLastSearch();
    });
    if('dtData' in this.props){
        dtData = Object.assign(dtData,this.props.dtData);
    }
    this.table = this.$table.DataTable(dtData);
};
TableController.prototype.filterTable = function () {
    var lastSearchBtnLabel = '';
    this.$filterHolder.find('textarea,input').each(function () {
        Cookies.set('search_' + $(this).attr('name'), $(this).val());
        
        if($(this).attr('type')!='hidden' && $(this).attr('type')!='checkbox'){
            lastSearchBtnLabel += $(this).val()+' ';
        }
    });
    this.$lastSearchBtn.html(this.lastSearchBtnDefLabel+': <b style="color:red">'+lastSearchBtnLabel+'</b>');
    this.$filterHolder.find('input[name="without1c"]').val("0");
    this.table.draw();
    this.$clearFilterBtn.prop('disabled', false);
};
TableController.prototype.clearFilter = function () {
    this.$filterHolder.find('input,textarea,select').not('.unchangeable').val('');
    this.$clearFilterBtn.prop('disabled', true);
    this.$filterHolder.find('input[name="without1c"]').val("0");
    this.table.draw();
};
TableController.prototype.repeatLastSearch = function () {
    this.$filterHolder.find('textarea,input').each(function () {
        $(this).val(Cookies.get('search_' + $(this).attr('name')));
    });
    this.$clearFilterBtn.prop('disabled', false);
    this.$filterHolder.find('input[name="without1c"]').val("1");
    this.$filterHolder.find('input[name="anotherSubdivision"]').prop('checked', true);
    this.table.draw();
};
