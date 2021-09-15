(function ($) {
    $.rolesCtrl = {};
    $.rolesCtrl.init = function () {
        $('#permissionModal form').submit(function (e) {
            e.preventDefault();
            var sendData = $(this).serialize();
            var $form = $(this);
            $.app.blockScreen(true);
            $.post($.app.url + '/adminpanel/permissions/update', sendData).done(function (data) {
                $.app.blockScreen(false);
                if (data.result.toString() === '1') {
                    //список разрешений на странице редактирования роли
                    var $permList1 = $('#permissionsList');
                    //список разрешений на странице просмотра всех ролей
                    var $permList2 = $('#permissionsList2');
                    if ($permList1.length > 0) {
                        $('#permissionsList').append('<li><label><input type="checkbox" name="permission[]" value="' + data.permission.id + '"/>' + data.permission.name + '</label></li>');
                    }
                    if ($permList2.length > 0) {
                        var $existingItem = $('.permission-item[data-permission-id="' + data.permission.id + '"]');
                        if ($existingItem.length > 0) {
                            var $item2 = $existingItem;
                        } else {
                            var $item2 = $('#permissionsList2 .permission-item-template2').clone();
                        }
                        $item2.attr('data-permission-id', data.permission.id);
                        $item2.find('.edit-btn').attr('onclick', '$.rolesCtrl.editPermission(' + data.permission.id + ')');
                        $item2.find('.remove-btn').attr('href', $.app.url + '/adminpanel/permissions/destroy/' + data.permission.id);
                        $item2.find('.permission-description').text(data.permission.description);
                        $item2.find('.permission-name').text(data.permission.name);
                        $item2.removeClass('hidden permission-item-template2');
                        if ($existingItem.length == 0) {
                            $('#permissionsList2').append($item2);
                        }
                    }
                    $('#permissionModal').modal('hide');
                }
            });
        });
    };
    $.rolesCtrl.addPermission = function () {

    };
    $.rolesCtrl.savePermission = function () {

    };
    $.rolesCtrl.editPermission = function (id) {
        $.post($.app.url + '/adminpanel/permissions/ajax/get/' + id).done(function (data) {
            if (data.result.toString() === '1') {
                for (var item in data.permission) {
                    $('#permissionModal').find('[name="' + item + '"]').val(data.permission[item]);
                }
                $('#permissionModal').modal('show');
            } else {
                $.app.ajaxResult(data.result);
            }
        });
    };
})(jQuery);