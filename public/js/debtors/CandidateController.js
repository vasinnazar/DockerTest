(function ($) {
//alert ('Привет');
	$.candidateCtrl = {};

    $.candidateCtrl.candidateToExcel = function () {
        window.open($.app.url + '/candidate/excel'.serialize());
    };

})(jQuery);