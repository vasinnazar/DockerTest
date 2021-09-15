(function ($) {
    $.fn.yaSpeller = function (options) {
        var opts = $.extend({
            checkURL: 'http://speller.yandex.net/services/spellservice.json/checkText?text='
        }, options);
        this.each(function () {
            $(this).focus(function () {
                $(this).popover('destroy');
            });
            $(this).blur(function () {
                var $elem = $(this);
                $.ajax({
                    url: opts.checkURL + $elem.val(),
                    dataType: 'jsonp',
                    success: function (data) {
                        var validator = {}, s, s1, w, w1, txt, html = '<div class="btn-group-vertical">';
                        if (!data) {
                            return;
                        }
                        if (data.length > 0 && data[0].hasOwnProperty('s') && data[0]['s'].length>0) {
                            $elem.data('words', []);
                            for (w in data) {
                                for (s in data[w]['s']) {
                                    txt = $elem.val().replace(new RegExp(data[w]['word'], 'g'), data[w]['s'][s]);
                                    for (w1 in data) {
                                        if (w1 != w) {
                                            for (s1 in data[w1]['s']) {
                                                txt = txt.replace(new RegExp(data[w1]['word'], 'g'), data[w1]['s'][s1]);
                                            }
                                        }
                                    }
                                    if (html.replace(new RegExp(txt, 'g'), '') == html) {
                                        html += '<button class="btn btn-default">' + txt + '</button>';
                                        $elem.data('words').push(txt);
                                    }
                                }
                            }
                            html += '<button class="btn btn-default">' + $elem.val() + '</button></div>';
                            $elem.popover({content: html, html: true, placement: 'top', title: 'Ошибка!'});
                            $elem.popover('show');
                            $elem.on('hidden.bs.popover', function () {
                                $(this).popover('destroy');
                            });
//                            $elem.parent().addClass('has-error spell-error');
                            $('#' + $elem.attr('aria-describedby') + ' button').click(function () {
                                var v = $(this).text();
                                if ($(this).data('word')) {
                                    v = $elem.val();
                                    v = v.replace(new RegExp($(this).data('word'), 'g'), $(this).text());
                                    $(this).prop('disabled', true);
                                }
                                $elem.popover('destroy');
                                $elem.parent().removeClass('has-error spell-error');

                                $elem.val(v);
                                return false;
                            });
                        } else {
                            return;
                        }
                    },
                    error: function () {
                        alert("Error");
                    }
                });
            });
        });
        return this;
    };
}(jQuery));