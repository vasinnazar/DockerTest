(function ($) {
    $.terminalsCtrl = {};
    $.terminalsCtrl.init = function () {
        
    };
    $.terminalsCtrl.openAddCashModal = function (id) {
        $('#addCashModal').modal();
        $('#addCashModal [name="id"]').val(id);
        $('#addCashModal [name="dispenser_count"]').val('');
        $('#addCashModal [name="dispenser_cash"]').val('');
    };
    $.terminalsCtrl.openIncassModal = function (id) {
        $('#incassModal').modal();
        $('#incassModal [name="id"]').val(id);
        $('#incassModal [name="bill_cash"]').val('');
    };
    $.terminalsCtrl.openAddCommandModal = function (id) {
        $('#addCommandModal').modal();
        $('#addCommandModal [name="point_id"]').val(id);
    };
    $.terminalsCtrl.changeLockStatus = function (id) {
        $.post(armffURL + 'terminals/changelockstatus', {id: id, is_locked: (($('#is_locked' + id).prop('checked')) ? 0 : 1)}).done(function (data) {
            if (parseInt(data) !== 1) {
                $('#is_locked' + id).prop('checked', false);
            }
            $.app.ajaxResult(data);
        });
    };
    $.terminalsCtrl.refreshStatus = function (id) {
        $.get(armffURL + 'ajax/terminals/refreshstatus', {id: id}).done(function (data) {
            if(data){
                $.app.ajaxResult(1);
                for(var d in data){
                    $('#terminalStatusHolder'+id+' .status_'+d).removeClass('label-warning');
                    if(data[d]){
                        $('#terminalStatusHolder'+id+' .status_'+d).removeClass('label-danger');
                        $('#terminalStatusHolder'+id+' .status_'+d).addClass('label-success');
                    } else {
                        $('#terminalStatusHolder'+id+' .status_'+d).removeClass('label-success');
                        $('#terminalStatusHolder'+id+' .status_'+d).addClass('label-danger');
                    }
                }
            } else {
                $.app.ajaxResult(0);
            }
        });
    };
})(jQuery);