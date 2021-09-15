
!function(a){"function"==typeof define&&define.amd?define(["jquery"],a):a("object"==typeof exports?require("jquery"):jQuery)}(function(a){var b,c=navigator.userAgent,d=/iphone/i.test(c),e=/chrome/i.test(c),f=/android/i.test(c);a.mask={definitions:{9:"[0-9]",a:"[A-Za-z]","*":"[A-Za-z0-9]"},autoclear:!0,dataName:"rawMaskFn",placeholder:"_"},a.fn.extend({caret:function(a,b){var c;if(0!==this.length&&!this.is(":hidden"))return"number"==typeof a?(b="number"==typeof b?b:a,this.each(function(){this.setSelectionRange?this.setSelectionRange(a,b):this.createTextRange&&(c=this.createTextRange(),c.collapse(!0),c.moveEnd("character",b),c.moveStart("character",a),c.select())})):(this[0].setSelectionRange?(a=this[0].selectionStart,b=this[0].selectionEnd):document.selection&&document.selection.createRange&&(c=document.selection.createRange(),a=0-c.duplicate().moveStart("character",-1e5),b=a+c.text.length),{begin:a,end:b})},unmask:function(){return this.trigger("unmask")},mask:function(c,g){var h,i,j,k,l,m,n,o;if(!c&&this.length>0){h=a(this[0]);var p=h.data(a.mask.dataName);return p?p():void 0}return g=a.extend({autoclear:a.mask.autoclear,placeholder:a.mask.placeholder,completed:null},g),i=a.mask.definitions,j=[],k=n=c.length,l=null,a.each(c.split(""),function(a,b){"?"==b?(n--,k=a):i[b]?(j.push(new RegExp(i[b])),null===l&&(l=j.length-1),k>a&&(m=j.length-1)):j.push(null)}),this.trigger("unmask").each(function(){function h(){if(g.completed){for(var a=l;m>=a;a++)if(j[a]&&C[a]===p(a))return;g.completed.call(B)}}function p(a){return g.placeholder.charAt(a<g.placeholder.length?a:0)}function q(a){for(;++a<n&&!j[a];);return a}function r(a){for(;--a>=0&&!j[a];);return a}function s(a,b){var c,d;if(!(0>a)){for(c=a,d=q(b);n>c;c++)if(j[c]){if(!(n>d&&j[c].test(C[d])))break;C[c]=C[d],C[d]=p(d),d=q(d)}z(),B.caret(Math.max(l,a))}}function t(a){var b,c,d,e;for(b=a,c=p(a);n>b;b++)if(j[b]){if(d=q(b),e=C[b],C[b]=c,!(n>d&&j[d].test(e)))break;c=e}}function u(){var a=B.val(),b=B.caret();if(a.length<o.length){for(A(!0);b.begin>0&&!j[b.begin-1];)b.begin--;if(0===b.begin)for(;b.begin<l&&!j[b.begin];)b.begin++;B.caret(b.begin,b.begin)}else{for(A(!0);b.begin<n&&!j[b.begin];)b.begin++;B.caret(b.begin,b.begin)}h()}function v(){A(),B.val()!=E&&B.change()}function w(a){if(!B.prop("readonly")){var b,c,e,f=a.which||a.keyCode;o=B.val(),8===f||46===f||d&&127===f?(b=B.caret(),c=b.begin,e=b.end,e-c===0&&(c=46!==f?r(c):e=q(c-1),e=46===f?q(e):e),y(c,e),s(c,e-1),a.preventDefault()):13===f?v.call(this,a):27===f&&(B.val(E),B.caret(0,A()),a.preventDefault())}}function x(b){if(!B.prop("readonly")){var c,d,e,g=b.which||b.keyCode,i=B.caret();if(!(b.ctrlKey||b.altKey||b.metaKey||32>g)&&g&&13!==g){if(i.end-i.begin!==0&&(y(i.begin,i.end),s(i.begin,i.end-1)),c=q(i.begin-1),n>c&&(d=String.fromCharCode(g),j[c].test(d))){if(t(c),C[c]=d,z(),e=q(c),f){var k=function(){a.proxy(a.fn.caret,B,e)()};setTimeout(k,0)}else B.caret(e);i.begin<=m&&h()}b.preventDefault()}}}function y(a,b){var c;for(c=a;b>c&&n>c;c++)j[c]&&(C[c]=p(c))}function z(){B.val(C.join(""))}function A(a){var b,c,d,e=B.val(),f=-1;for(b=0,d=0;n>b;b++)if(j[b]){for(C[b]=p(b);d++<e.length;)if(c=e.charAt(d-1),j[b].test(c)){C[b]=c,f=b;break}if(d>e.length){y(b+1,n);break}}else C[b]===e.charAt(d)&&d++,k>b&&(f=b);return a?z():k>f+1?g.autoclear||C.join("")===D?(B.val()&&B.val(""),y(0,n)):z():(z(),B.val(B.val().substring(0,f+1))),k?b:l}var B=a(this),C=a.map(c.split(""),function(a,b){return"?"!=a?i[a]?p(b):a:void 0}),D=C.join(""),E=B.val();B.data(a.mask.dataName,function(){return a.map(C,function(a,b){return j[b]&&a!=p(b)?a:null}).join("")}),B.one("unmask",function(){B.off(".mask").removeData(a.mask.dataName)}).on("focus.mask",function(){if(!B.prop("readonly")){clearTimeout(b);var a;E=B.val(),a=A(),b=setTimeout(function(){z(),a==c.replace("?","").length?B.caret(0,a):B.caret(a)},10)}}).on("blur.mask",v).on("keydown.mask",w).on("keypress.mask",x).on("input.mask paste.mask",function(){B.prop("readonly")||setTimeout(function(){var a=A(!0);B.caret(a),h()},0)}),e&&f&&B.off("input.mask").on("input.mask",u),A()})}})});
$(document).ready(function () {

    // Устанавливаем обработчик потери фокуса для всех полей ввода текста
    $('input#seria, input#fio, input#birth_date, input#issued, input#subdivision_code, input#address_reg_date, input#issued_date, input#telephone,input#nomer, input#birth_city, input#email, textarea#message').unbind().blur(function () {

        // Для удобства записываем обращения к атрибуту и значению каждого поля в переменные 
        var id = $(this).attr('id');
        var val = $(this).val();

        // После того, как поле потеряло фокус, перебираем значения id, совпадающее с id данного поля
        switch (id)
        {
          
            case 'seria':
                var rv_name = /^[0-9]+$/; // используем регулярное выражение

                if (val.length == 4 && val != '' && rv_name.test(val))
                {
                    $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                    $(this).addClass('not_error').removeClass('error').css('border-color', '');

                }

                else
                {
                    $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                    $(this).removeClass('not_error').addClass('error').css('border-color', 'red');
                    $(this).after('<span></span>');
                    $(this).next('').addClass('jQtooltip mini').attr('title', 'Поле обязательно').html('<div class="error" style="width: 184px; height: 52px; display: block;">Поле обязательно и должно состоять из 4 цифр</div>');

                }
                break;
                case 'nomer':
                var rv_name = /^[0-9]+$/; // используем регулярное выражение

                if (val.length == 6 && val != '' && rv_name.test(val))
                {   
                    $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                    $(this).addClass('not_error').removeClass('error').css('border-color', '');
               
                }

                else
                {
                      $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                   $(this).removeClass('not_error').addClass('error').css('border-color', 'red');
                   $(this).after('<span></span>');
                   $(this).next('').addClass('jQtooltip mini').attr('title', 'Поле обязательно').html('<div class="error" style="width: 184px; height: 52px; display: block;">Поле обязательно и должно состоять из 6 цифр</div>');
                  
                }
                break;
                    case 'fio':
                var rv_name = /^[аА-яЯ-]/; // используем регулярное выражение

                if ( val != '' && rv_name.test(val))
                {   
                    $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                    $(this).addClass('not_error').removeClass('error').css('border-color', '');
               
                }

                else
                {
                      $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                   $(this).removeClass('not_error').addClass('error').css('border-color', 'red');
                   $(this).after('<span></span>');
                   $(this).next('').addClass('jQtooltip mini').attr('title', 'Поле обязательно').html('<div class="error" style="width: 184px; height: 52px; display: block;">Поле обязательно и должно состоять только из букв </div>');
                  
                }
                break;
                case 'telephone':
                var rv_name = /^[0-9]+$/; // используем регулярное выражение

                if ( val.length == 15 && val != '')
                {   
                    $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                    $(this).addClass('not_error').removeClass('error').css('border-color', '');
               
                }

                else
                {
                      $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                   $(this).removeClass('not_error').addClass('error').css('border-color', 'red');
                   $(this).after('<span></span>');
                   $(this).next('').addClass('jQtooltip mini').attr('title', 'Поле обязательно').html('<div class="error" style="width: 184px; height: 52px; display: block;">Поле обязательно</div>');
                  
                }
                break;
                case 'birth_date':
                var rv_name = /^[0-9]+$/; // используем регулярное выражение
                if (val != '' )
                {   
                      
                    $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                    $(this).addClass('not_error').removeClass('error').css('border-color', '');
                }
                else
                {
                  
                      $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                   $(this).removeClass('not_error').addClass('error').css('border-color', 'red');
                   $(this).after('<span></span>');
                   $(this).next('').addClass('jQtooltip mini').attr('title', 'Поле обязательно').html('<div class="error" style="width: 184px; height: 52px; display: block;">Некорректная дата или поле не заполнено</div>');
                }
                break;
                 case 'birth_city':
                var rv_name =  /^[аА-яЯ-]/; // используем регулярное выражение
                if (val != '' && rv_name.test(val))
                {   
                    $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                    $(this).addClass('not_error').removeClass('error').css('border-color', '');
                }
                else
                {
                      $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                   $(this).removeClass('not_error').addClass('error').css('border-color', 'red');
                   $(this).after('<span></span>');
                   $(this).next('').addClass('jQtooltip mini').attr('title', 'Поле обязательно').html('<div class="error" style="width: 184px; height: 52px; display: block;">Поле обязательно и должно содержать русские буквы</div>');
                }
                break;
                 case 'issued':
                var rv_name =  /^[аА-яЯ-]/; // используем регулярное выражение
                if (val != '' && rv_name.test(val))
                {   
                    $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                    $(this).addClass('not_error').removeClass('error').css('border-color', '');
                }
                else
                {
                      $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                   $(this).removeClass('not_error').addClass('error').css('border-color', 'red');
                   $(this).after('<span></span>');
                   $(this).next('').addClass('jQtooltip mini').attr('title', 'Поле обязательно').html('<div class="error" style="width: 184px; height: 52px; display: block;">Поле обязательно и должно содержать русские буквы</div>');
                }
                break;
                 case 'issued_date':
                var rv_name = /^[0-9]+$/; // используем регулярное выражение
                if (val != '' )
                {   
                      
                    $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                    $(this).addClass('not_error').removeClass('error').css('border-color', '');
                }
                else
                {
                  
                      $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                   $(this).removeClass('not_error').addClass('error').css('border-color', 'red');
                   $(this).after('<span></span>');
                   $(this).next('').addClass('jQtooltip mini').attr('title', 'Поле обязательно').html('<div class="error" style="width: 184px; height: 52px; display: block;">Некорректная дата или поле не заполнено</div>');
                }
                break;
                  case 'subdivision_code':
                var rv_name = /^[0-9]+$/; // используем регулярное выражение
                if (val != '' )
                {   
                      
                    $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                    $(this).addClass('not_error').removeClass('error').css('border-color', '');
                }
                else
                {
                  
                      $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                   $(this).removeClass('not_error').addClass('error').css('border-color', 'red');
                   $(this).after('<span></span>');
                   $(this).next('').addClass('jQtooltip mini').attr('title', 'Поле обязательно').html('<div class="error" style="width: 184px; height: 52px; display: block;">Некорректная дата или поле не заполнено</div>');
                }
                break;
                  case 'address_reg_date':
                var rv_name = /^[0-9]+$/; // используем регулярное выражение
                if (val != '' )
                {   
                      
                    $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                    $(this).addClass('not_error').removeClass('error').css('border-color', '');
                }
                else
                {
                  
                      $("span").remove()
                    $().next('jQtooltip mini').removeClass();
                   $(this).removeClass('not_error').addClass('error').css('border-color', 'red');
                   $(this).after('<span></span>');
                   $(this).next('').addClass('jQtooltip mini').attr('title', 'Поле обязательно').html('<div class="error" style="width: 184px; height: 52px; display: block;">Некорректная дата или поле не заполнено</div>');
                }
                break;
                // Проверка email address_reg_date 
            case 'email':
                var rv_email = /^([a-zA-Z0-9_.-])+@([a-zA-Z0-9_.-])+\.([a-zA-Z])+([a-zA-Z])+/;
                if (val != '' && rv_email.test(val) )
                {
                    $(this).addClass('not_error');
                    $(this).next('.error-box').text('Принято')
                            .css('color', 'green')
                            .animate({'paddingLeft': '10px'}, 400)
                            .animate({'paddingLeft': '5px'}, 400);
                }
                else
                {
                    $(this).removeClass('not_error').addClass('error');
                    $(this).next('.error-box').html('&bull; поле "Email" обязательно для заполнения &bull;<br> поле должно содержать правильный email-адрес<br> (например: example123@mail.ru)')
                            .css('color', 'red')
                            .animate({'paddingLeft': '10px'}, 400)
                            .animate({'paddingLeft': '5px'}, 400);
                }
                break;


                // Проверка поля "Сообщение"
            case 'message':
                if (val != '' && val.length < 5000)
                {
                    $(this).addClass('not_error');
                    $(this).next('.error-box').text('Принято')
                            .css('color', 'green')
                            .animate({'paddingLeft': '10px'}, 400)
                            .animate({'paddingLeft': '5px'}, 400);
                }
                else
                {
                    $(this).removeClass('not_error').addClass('error');
                    $(this).next('.error-box').html('&bull; поле "Текст письма" обязательно для заполнения')
                            .css('color', 'red')
                            .animate({'paddingLeft': '10px'}, 400)
                            .animate({'paddingLeft': '5px'}, 400);
                }
                break;


        } // end switch(...)

    }); // end blur()


    // Теперь отправим наше письмо с помощью AJAX
    $('form#feedback-form').submit(function (e) {

        // Запрещаем стандартное поведение для кнопки submit
        e.preventDefault();

        // После того, как мы нажали кнопку "Отправить", делаем проверку,
        // если кол-во полей с классов .not_error равно 3(так как у нас всего 3 поля), то есть все поля заполнены верно,
        // выполняем наш Ajax сценарий и отправляем письмо адресату

        if ($('.not_error').length == 3)
        {

            // Eще одним моментов является то, что в качестве указания данных для передачи обработчику send.php, мы обращаемся $(this) к нашей форме,
            // и вызываем метод .serialize().
            // Это очень удобно, т.к. он сразу возвращает сгенерированную строку с именами и значениями выбранных элементов формы.

            $.ajax({
                url: 'send.php',
                type: 'post',
                data: $(this).serialize(),
                beforeSend: function (xhr, textStatus) {
                    $('form#feedback-form :input').attr('disabled', 'disabled');
                },
                success: function (response) {
                    $('form#feedback-form :input').removeAttr('disabled');
                    $('form#feedback-form :text, textarea').val('').removeClass().next('.error-box').text('');
                    alert(response);
                }
            }); // end ajax({...})
        }
        else
        {
            return false;
        }
        // Иначе, если количество полей с данным классом не равно значению 3 мы возвращаем false,
        // останавливая отправку сообщения в невалидной форме


    }); // end submit()

}); // end script


(function ($) {
    $(function () {

        $('span.jQtooltip').each(function () {
            var el = $(this);
            var title = el.attr('title');
            if (title && title != '') {
                el.attr('title', '').append('<div>' + title + '</div>');
                var width = el.find('div').width();
                var height = el.find('div').height();
                el.hover(
                        function () {
                            el.find('div')
                                    .clearQueue()
                                    .delay(200)
                                    .animate({width: width + 20, height: height + 20}, 200).show(200)
                                    .animate({width: width, height: height}, 200);
                        },
                        function () {
                            el.find('div')
                                    .animate({width: width + 20, height: height + 20}, 150)
                                    .animate({width: 'hide', height: 'hide'}, 150);
                        }
                ).mouseleave(function () {
                    if (el.children().is(':hidden'))
                        el.find('div').clearQueue();
                });
            }
        })

    })
})(jQuery)