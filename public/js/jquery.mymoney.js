(function ($) {
    $.fn.myMoney = function (options) {
        var opts = $.extend({
            allowNegative: false,
            enabledKeys: [8, 46, 13, 35, 36, 37, 39, 9]
        }, options);
        this.each(function () {
            if ($(this).val() == '') {
                $(this).val('0.00');
            } else {
                addDot(this);
            }
            if ($(this).hasClass('my-money')) {
                return;
            }
            $(this).addClass('my-money');
            $(this).focus(function (e) {
                setSelection(this, 0, $(this).val().indexOf('.'));
            });
            $(this).blur(function (e) {
                if ($(this).val().indexOf('.') < 0) {
                    addDot(this, true);
                }
            });
            $(this).keydown(function (e) {
                var v = $(this).val(), dotPos = v.indexOf('.');
                if (e.which === 40) {
                    if (getCaretPosition(this) > dotPos) {
                        setSelection(this, 0, dotPos);
                    } else {
                        setSelection(this, dotPos + 1, dotPos + 3);
                    }
                    e.preventDefault();
                } else if ((e.which >= 48 && e.which <= 57) || (e.which >= 96 && e.which <= 105) || opts.enabledKeys.indexOf(e.which) != -1) {

                } else if (e.which == 110 || e.which == 188) {
                    if (dotPos > 0) {
                        setSelection(this, dotPos + 1, dotPos + 3);
                        e.preventDefault();
                    }
                } else {
                    e.preventDefault();
                }
            });
            $(this).keyup(function (e) {
                var v = $(this).val(), dotPos = v.indexOf('.'), commaPos = v.indexOf(',');
                if (dotPos > 0) {
                    if (dotPos < v.length - 3) {
                        $(this).val(v.substr(0, dotPos + 3));
                    }
                } else if (dotPos < 0) {
                    if (commaPos > -1) {
                        $(this).val(v.replace(',', '.'));
                    }
                }
            });
        });
        function addDot(elem, endZeros) {
            var v = $(elem).val();
            if (endZeros) {
                if ($(elem).val().indexOf('.') >= 0) {
                    v = v.replace('.', '');
                }
                $(elem).val(v + '.00');
            } else {
                if (v == '0') {
                    $(elem).val('0.00');
                } else {
                    if ($(elem).val().indexOf('.') >= 0) {
                        v = v.replace('.', '')
                    }
                    $(elem).val(v.substr(0, v.length - 2) + '.' + v.substr(v.length - 2, 2));
                }
            }
        }
        function setSelection(input, startPos, endPos) {
            input.focus();
            if (typeof input.selectionStart != "undefined") {
                input.selectionStart = startPos;
                input.selectionEnd = endPos;
            } else if (document.selection && document.selection.createRange) {
                // IE branch
                input.select();
                var range = document.selection.createRange();
                range.collapse(true);
                range.moveEnd("character", endPos);
                range.moveStart("character", startPos);
                range.select();
            }
        }
        function getCaretPosition(ctrl) {
            var CaretPos = 0;	// IE Support
            if (document.selection) {
                ctrl.focus();
                var Sel = document.selection.createRange();
                Sel.moveStart('character', -ctrl.value.length);
                CaretPos = Sel.text.length;
            }
            // Firefox support
            else if (ctrl.selectionStart || ctrl.selectionStart == '0')
                CaretPos = ctrl.selectionStart;
            return (CaretPos);
        }
        return this;
    };
}(jQuery));