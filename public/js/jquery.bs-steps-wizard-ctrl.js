(function ($) {
    $.fn.bsStepsWizardCtrl = function () {
        var $prevBtn = this.find('.nav-wizard-back'),
                $nextBtn = this.find('.nav-wizard-forward'),
                $submitBtn = this.find('[type="submit"]'),
                $form = this.parents('form:first');

        $prevBtn.click(function () {
            if ($form.find('.has-error,.spell-error').length == 0) {
                $('.nav-wizard>li.active').prev().children('a:first').tab('show');
            } else {
                alert('Исправьте ошибки в текущей вкладке!');
            }
        });
        $nextBtn.click(function () {
            if ($form.find('.has-error,.spell-error').length == 0) {
                $('.nav-wizard>li.active').next().children('a:first').tab('show');
            } else {
                alert('Исправьте ошибки в текущей вкладке!');
            }
        });
        $('.nav-wizard a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if ($(e.target).parent().next().length === 0) {
                $nextBtn.prop('disabled', true);
//                $submitBtn.prop('disabled', false);
                //если подключен модуль валидации формы
                try {
                    if ($form.length > 0) {
                        if (!$form.validator('validate')) {
//                            $submitBtn.prop('disabled', true);
                        } else {
//                            $submitBtn.prop('disabled', false);
                        }
                    }
                } catch (e) {

                }

            } else if ($(e.target).parent().prev().length === 0) {
                $prevBtn.prop('disabled', true);
                $nextBtn.prop('disabled', false);
//                $submitBtn.prop('disabled', true);
            } else {
                $prevBtn.prop('disabled', false);
                $nextBtn.prop('disabled', false);
//                $submitBtn.prop('disabled', true);
            }
//            e.target // activated tab
//            e.relatedTarget // previous tab
        });
        return this;
    };
}(jQuery));