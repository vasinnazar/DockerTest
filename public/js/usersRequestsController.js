(function ($) {
    $.uReqsCtrl = {};
    $.uReqsCtrl.init = function () {
        $('#claimForRemoveModal').submit(function (e) {
//            e.preventDefault();
            $.app.blockScreen(true);
//            $.post(armffURL + 'ajax/removeRequests/claim',).done(function (data) {
//                if (data && 'res' in data) {
//                    $.app.ajaxResult(data['res']);
//                } else {
//                    $.app.ajaxResult(0);
//                }
//                $.app.blockScreen(false);
//            });
        });
    };
    $.uReqsCtrl.claimForRemove = function (id,doctype) {
        $('#claimForRemoveModal').modal();
        $('#claimForRemoveModal [name="id"]').val(id);
        $('#claimForRemoveModal [name="doctype"]').val(doctype);        
        $('#claimForRemoveModal [name="comment"]').val('');
    };
    $.uReqsCtrl.showInfo = function(id){
        $.get(armffURL+'ajax/usersreqs/removeRequests/info/'+id).done(function(data){
            $('#remreqInfoModal').modal();
            $('#remreqInfoHolder').html(data);
        });
    };
})(jQuery);