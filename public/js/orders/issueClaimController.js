(function ($) {
    $.issueClaimCtrl = {};
    $.issueClaimCtrl.init = function () {
        $.issueClaimCtrl.issueClaimsTableCtrl = new TableController('issueclaims', [
            {data: '0', name: 'ic_created_at'},
            {data: '1', name: 'ot_name'},
            {data: '2', name: 'ic_number'},
            {data: '3', name: 'ic_money'},
            {data: '4', name: 'u_name'},
            {data: '5', name: 'p_fio'},
            {data: '6', name: 'actions', searchable: false, orderable: false},
        ], {
            listURL: 'ajax/orders/issueclaims/list',
            clearFilterBtn: $('#clearIssueClaimsFilterBtn')
        });
        
        $('#editIssueClaimForm [name="money"]').myMoney();

        $('#editIssueClaimForm').submit(function (e) {
            $.app.blockScreen(true);
            e.preventDefault();
            $.post($(this).attr('action'), $(this).serialize()).done(function (data) {
                $.app.ajaxResult(data);
                $('#editIssueClaimModal').modal('hide');
                $.issueClaimCtrl.issueClaimsTableCtrl.clearFilter();
            }).always(function () {
                $.app.blockScreen(false);
            });
        });
    };
    $.issueClaimCtrl.openClaim = function (id) {
        $.app.blockScreen(true);
        $.get($.app.url + '/ajax/orders/issueclaims/view/' + id).done(function (data) {
            if (!('result' in data) || data.result == 0) {
                $.app.ajaxResult(0);
                return;
            }
            for (var k in data) {
                $('#viewIssueClaimModal [name="' + k + '"]').val(data[k]);
            }
            if ('agreed' in data) {
                var $label = $('#viewIssueClaimModalAgreedLabel');
                var $canPrint = $('#viewIssueClaimModalCanPrint');
                if (data.agreed == 'Да') {
                    $label.removeClass('label-danger').addClass('label-success').text(data.agreed);
                    $canPrint.show();
                } else {
                    $label.removeClass('label-success').addClass('label-danger').text(data.agreed);
                    $canPrint.hide();
                }
            }
            $('#viewIssueClaimModal').modal('show');
        }).always(function () {
            $.app.blockScreen(false);
        });
    };
    $.issueClaimCtrl.editClaim = function (id) {
        $.app.blockScreen(true);
        $.get($.app.url + '/ajax/orders/issueclaims/view/' + id + '?only_claim=1').done(function (data) {
            for (var k in data) {
                if(k=='money'){
                    $('#editIssueClaimModal [name="' + k + '"]').val(data[k]/100);
                } else {
                    $('#editIssueClaimModal [name="' + k + '"]').val(data[k]);
                }
                
                var idPos = k.indexOf('_id');
                if (idPos !== -1) {
                    var tableName = k.substring(0, idPos);
                    if (tableName in data) {
                        if (k == 'passport_id') {
                            $('#editIssueClaimModal [name="' + k + '_autocomplete"]').attr('placeholder',data[tableName]['fio']);
                        } else {
                            $('#editIssueClaimModal [name="' + k + '_autocomplete"]').attr('placeholder',data[tableName]['name']);
                        }
                    }
                }
            }
            $('#editIssueClaimModal').modal('show');
        }).always(function () {
            $.app.blockScreen(false);
        });
    };

})(jQuery);