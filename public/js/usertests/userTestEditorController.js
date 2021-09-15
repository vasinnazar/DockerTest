(function ($) {
    $.utEditorCtrl = {
        questionTemplate: $('#userTestQuestionTemplate'),
        answerTemplate: $('#userTestQuestionTemplate .answer:first'),
        questionsHolder: $('#userTestQuestionsHolder'),
        questionsField: $('#usertestEditorForm').find('[name="questions"]:first')
    };
    $.utEditorCtrl.init = function () {
        $('#usertestEditorForm').submit(function (e) {
            $.utEditorCtrl.questionsField.val($.utEditorCtrl.makeTestJson());
        });
        $.utEditorCtrl.loadJson($.utEditorCtrl.questionsField.val());
    };
    /**
     * Добавить вопрос
     * @returns {undefined}
     */
    $.utEditorCtrl.addQuestion = function (data) {
        var $question = $.utEditorCtrl.questionTemplate.clone();
        $question.attr('id', '').removeClass('hidden');
        $.utEditorCtrl.questionsHolder.append($question);
        $question.find('[name="toggle"]').click(function () {
            $.utEditorCtrl.toggleQuestionBody(this);
        });
        $question.find('[name="remove"]').click(function () {
            $.utEditorCtrl.removeQuestion(this);
        });
        $question.find('[name="text"]').change(function () {
            $question.find('.question-label').text('Вопрос: ' + $(this).val());
        });
        for (var d in data) {
            $question.find('[name="' + d + '"]').val(data[d]);
            if (d == 'answers') {
                var $answersHolder = $question.find('.answers-list');
                $answersHolder.empty();
                data[d].forEach(function (item) {
                    $.utEditorCtrl.addAnswer($answersHolder, item);
                });
            }
        }
//        $.utEditorCtrl.testFill($question);
    };
    /**
     * Тестовое заполнение вопроса
     * @param {type} $question
     * @returns {undefined}
     */
    $.utEditorCtrl.testFill = function ($question) {
        $question.find('.panel-footer .btn').click();
        $question.find('.panel-footer .btn').click();
        $question.find('textarea,input[type="text"]').val('АЗАЗАЗАЗАЗАЗА');
        $question.find('.answer [name="is_right"]:first').prop('checked', true);
    };
    /**
     * Удалить вопрос
     * @param {type} btn кнопка
     * @returns {undefined}
     */
    $.utEditorCtrl.removeQuestion = function (btn) {
        $(btn).parents('.question:first').remove();
    };
    /**
     * Свернуть\развернуть вопрос по нажатию на кнопку
     * @param {type} btn кнопка
     * @returns {undefined}
     */
    $.utEditorCtrl.toggleQuestionBody = function (btn) {
        var $body = $(btn).parents('.question:first').find('.panel-body');
        var $glyph = $(btn).children('.glyphicon');
        if ($glyph.hasClass('glyphicon-chevron-up')) {
            $glyph.removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
            $body.slideUp();
        } else {
            $glyph.removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
            $body.slideDown();
        }
    };
    /**
     * Добавляет вариант ответа к вопросу
     * @param {type} btn кнопка на которую нажали
     * @returns {undefined}
     */
    $.utEditorCtrl.addAnswer = function ($answersHolder, data) {
        $answersHolder.append($.utEditorCtrl.answerTemplate.clone());
        var $answer = $answersHolder.find('.answer:last');
        $answer.find('[name="remove_answer"]').click(function(){
            $(this).parents('.answer:first').remove();
        });
        for (var d in data) {
            if (d == 'is_right') {
                console.log(d,data[d],$answer.find('[name="' + d + '"]'),(data[d] == 1));
                $answer.find('[name="' + d + '"]').prop('checked', (data[d] == 1) ? true : false);
            } else {
                $answer.find('[name="' + d + '"]').val(data[d]);
            }
        }
    };
    $.utEditorCtrl.addAnswerBtnClick = function (btn) {
        var $answersHolder = $(btn).parents('.answers-holder:first').find('.answers-list:first');
        $.utEditorCtrl.addAnswer($answersHolder);
    };
    /**
     * Возвращает JSON с вопросами и ответами
     * @returns {String}
     */
    $.utEditorCtrl.makeTestJson = function () {
        var json = {};
        var qid = 0;
        $.utEditorCtrl.questionsHolder.find('.question').each(function () {
            json[qid] = {
                id: $(this).children('[name="id"]').val(),
                text: $(this).find('[name="text"]:first').val(),
                answers: {}
            };
            var aid = 0;
            $(this).find('.answer').each(function () {
                json[qid]['answers'][aid] = {
                    text: $(this).find('[name="text"]').val(),
                    is_right: $(this).find('[name="is_right"]').prop('checked'),
                    id: $(this).find('[name="id"]:first').val()
                };
                aid++;
            });
            qid++;
        });
        return JSON.stringify(json);
    };
    $.utEditorCtrl.loadJson = function (json) {
        var data = $.parseJSON(json);
        data.forEach(function (q) {
            $.utEditorCtrl.addQuestion(q);
        });
    };
})(jQuery);