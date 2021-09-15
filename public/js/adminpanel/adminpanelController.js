(function ($) {
    $.adminCtrl = {};
    $.adminCtrl.init = function () {

    };
    $.adminCtrl.usersList = function () {
        $.adminCtrl.table = $('#spylogUsersTable').DataTable({
            order: [[0, "desc"]],
            searching: false,
            sDom: 'Rfrtlip',
            lengthMenu: [[25, 50, 100], [25, 50, 100]],
            language: dataTablesRuLang,
            processing: true,
            serverSide: true,
            ajax: {
                url: armffURL + '/ajax/adminpanel/userslist',
                data: function (d) {
                    d.name = $('#spylogFilter input[name="name"]').val();
                }
            },
            columns: [
                {data: '0', name: 'name'},
            ]
        });
        $('#userLogForm').find('[name="from"],[name="to"]').change(function () {
            $.adminCtrl.getUserLog();
        });
        $("#bantimeForm .slider-range").slider({
            range: true,
            min: 0,
            max: 24,
            values: [0, 24],
            slide: function (event, ui) {
                $('#bantimeForm [name="begin_time"]').val(
                        ((parseInt(ui.values[0]) < 10) ? ('0' + ui.values[0]) : ui.values[0]) + ':00:00');
                $('#bantimeForm [name="end_time"]').val(
                        ((parseInt(ui.values[1]) < 10) ? ('0' + ui.values[1]) : ui.values[1]) + ':00:00');
                $('#bantimeForm .slider-range-label').html('Разрешить доступ с: <b>'
                        + ui.values[0] + '</b> часов до <b>' + ui.values[1] + '</b> часов');
            }
        });
        $("#userDataForm,#bantimeForm,#changePasswordForm").submit(function (e) {
            e.preventDefault();
            $.app.blockScreen(true);
            $.post($(this).attr('action'), $(this).serialize()).done(function (data) {
                $.app.blockScreen(false);
                $.app.ajaxResult(data);
            });
        });
        $("#changeSubdivisionForm").submit(function (e) {
            var $form = $(this);
            e.preventDefault();
            $.app.blockScreen(true);
            $.post($(this).attr('action'), $(this).serialize()).done(function (data) {
                if (data && $form.find('[name="user_id"]').val() == $('#curUserDropdown').data('id')) {
                    var name = $form.find('[name="subdivision_id"] option:selected').text();
                    $('#curUserSubdivisionDropdown').attr('title', name);
                    $('#curUserSubdivisionName').text((name.length > 28) ? (name.substr(0, 28) + '...') : name);
                }
                $.app.blockScreen(false);
                $.app.ajaxResult(data);
            });
        });
        $('#refreshUserLastLogin').click(function (e) {
            e.preventDefault();
            $.app.blockScreen(true);
            $.post(armffURL + 'ajax/adminpanel/refreshlastlogin/' + $(this).parents('form').find('[name="user_id"]').val()).done(function (data) {
                $.app.blockScreen(false);
                $.app.ajaxResult(data);
            });
            return false;
        });
        $('#userRolesForm').submit(function (e) {
            e.preventDefault();
            $.app.blockScreen(true);
            $.post($.app.url + '/adminpanel/roles/grant', $(this).serialize() + '&user_id=' + $('#adminpanelUserDetails input[name="user_id"]').val()).done(function (data) {
                $.app.blockScreen(false);
                $.app.ajaxResult(data);
            });
        });
    };
    $.adminCtrl.filterLogs = function () {
        $.adminCtrl.table.draw();
    };
    /**
     * Запрашивает и отображает данные о пользователе
     * @param {type} user_id идентификатор пользователя
     * @param {type} elem нажатая строка с пользователем в таблице пользователей
     * @returns {undefined}
     */
    $.adminCtrl.viewUser = function (user_id, elem) {
        //делаем активной строчку с пользователем в таблице пользователей
        $(elem).parents('table:first').find('tr').removeClass('active');
        $(elem).parents('tr:first').addClass('active');
        //проставляем идентификатор пользователя во всех формах страницы пользователя
        $('#adminpanelUserDetails input[name="user_id"]').val(user_id);
        $('#createSSLBtn').attr('href', $.app.url + '/adminpanel/users/ssl/download?user_id=' + user_id);
        $.app.blockScreen(true);
        $.get(armffURL + 'ajax/adminpanel/users/' + user_id).done(function (data) {
            if (data) {
                //обновляем поля форм
                $('#adminpanelUserDetails').show();
                for (var d in data.user) {
                    $('#adminpanelUserDetails [name="' + d + '"]').val(data.user[d]);
                    if ((d === 'user_group_id' || d === 'region_id')) {
                        if ((data.user[d] === null || data.user[d] === '0' || data.user[d] === 0 || data.user[d] === '')) {
                            (function (inp) {
                                $('#adminpanelUserDetails [name="' + inp + '_autocomplete"]').val('');
                            })(d);
                            continue;
                        }
                        (function (inp) {
                            $.get(armffURL + 'ajax/adminpanel/users/' + data.user[d]).done(function (dataSub) {
                                if (dataSub) {
                                    $('#adminpanelUserDetails [name="' + inp + '_autocomplete"]').val(dataSub.user["name"]);
                                }
                            });
                        })(d);
                    }
                }
                $('#adminpanelUserDetails .passports-btns').empty();
                if (parseInt(data.user['customer_id']) > 0) {
                    $('#createCustomerBtn').hide();
                    for (var p in data.passports) {
                        $('#adminpanelUserDetails .passports-btns').append('<a class="btn btn-default btn-sm" href="/customers/edit/' + data.user["customer_id"] + '/' + data.passports[p]["id"] + '">Редакт. физ.лицо</a>');
                    }
                } else {
                    $('#createCustomerBtn').show();
                }

                if (data.hasDebtorRole) {
                    $('.debtors-modal-link').show();
                }

                $('#debtorUsersData').html('');
                $('#formDebtorUserSlaves input[name="master_user_id"]').val(user_id);
                var jsonDebtorRoleUsers = $.parseJSON(data.debtorRoleUsers);
                var jsonDebtorUserSlaves = $.parseJSON(data.debtorUserSlaves);
                $.each(jsonDebtorRoleUsers, function (k, u) {
                    var checked = '';
                    $.each(jsonDebtorUserSlaves, function (key, ui) {
                        if (u.id == ui.user_id) {
                            checked = ' checked';
                        }
                    });
                    var string = '<tr><td>' + u.id + '</td><td>' + u.login + '</td><td><input type="checkbox" name="masterTo[]" value="' + u.id + '"' + checked + '></td></tr>';
                    $('#debtorUsersData').append(string);
                });

                $('#bantimeForm [name="ban_at"]').val(data.user.ban_at);
                $('#bantimeForm [name="banned"]').prop('checked', parseInt(data.user.banned));
//                $('#bantimeForm [name="ban_http"]').prop('checked', parseInt(data.user.ban_http));
                $('#bantimeForm [name="begin_time"]').val(data.user.begin_time);
                $('#bantimeForm [name="end_time"]').val(data.user.end_time);
                $("#bantimeForm .slider-range-label").html('Разрешить доступ с <b>'
                        + data.user.begin_time.substr(0, 2) + '</b> часов по <b>'
                        + data.user.end_time.substr(0, 2) + '</b> часов');
                $("#bantimeForm .slider-range").slider('values', [data.user.begin_time.substr(0, 2), data.user.end_time.substr(0, 2)]);

                $('#changePasswordForm input[name="password"]').val('');

                $('#userLogForm').find('[name="from"],[name="to"]').val('');

                $('#userRolesList input[type="checkbox"]').prop('checked', false);
                for (var r in data.roles) {
                    $('#userRolesList input[type="checkbox"][value="' + data.roles[r].id + '"]').prop('checked', true);
                }
				$('#userPermissionsList input[type="checkbox"]').prop('checked', false);
                for (var pm in data.permissions) {
                    $('#userPermissionsList input[type="checkbox"][value="' + data.permissions[pm].permission_id + '"]').prop('checked', true);
                    $('#permissionUntil' + data.permissions[pm].permission_id)
                        .val(
                            moment(data.permissions[pm].valid_until)
                                .format('YYYY-MM-DDTHH:mm')
                        );
                }
                $.adminCtrl.viewUserLog(data.logs);
            }
            $.app.blockScreen(false);
        });
    };
    /**
     * Запрашивает данные для таблицы входов\выходов
     * @returns {undefined}
     */
    $.adminCtrl.getUserLog = function () {
        $.get(armffURL + 'ajax/adminpanel/users/userlog', $('#userLogForm').serialize()).done(function (data) {
            if (data) {
                $.adminCtrl.viewUserLog(data);
            }
        });
    };
    /**
     * Обновляет данные в таблице входов\выходов
     * @param {type} data 
     * @returns {undefined}
     */
    $.adminCtrl.viewUserLog = function (data) {
        $('#spylogUserLog').empty();
        var log, i, t, days = {}, date, ymd, m, d, hrs, min, sec, a, maxlogin,
                loginsTable = '<table class="table table-condensed \n\
                        table-borderless compact"><thead><tr><th>Дата</th><th>Вход</th><th>Выход</th></tr></thead><tbody>';
        for (a in data) {
            for (i in data[a]) {
                log = data[a][i];
                date = new Date(log.created_at);
                maxlogin = new Date(log.maxlogin);
                m = date.getMonth();
                m = (m + 1 < 10) ? '0' + (m + 1) : (m + 1);
                d = date.getDate();
                d = (d < 10) ? '0' + d : d;
                ymd = d.toString() + '.' + m.toString() + '.' + date.getFullYear();
                if (!days.hasOwnProperty(ymd)) {
                    days[ymd] = {login: '', logout: ''};
                }
                hrs = date.getHours();
                hrs = (hrs < 10) ? '0' + hrs : hrs;
                min = date.getMinutes();
                min = (min < 10) ? '0' + min : min;
                sec = date.getSeconds();
                sec = (sec < 10) ? '0' + sec : sec;
                days[ymd][a] = hrs + ':' + min + ':' + sec;
            }
        }
        for (i in days) {
            loginsTable += '<tr><td>' + i + '</td>';
            loginsTable += '<td>' + days[i].login + '</td>';
            loginsTable += '<td>' + days[i].logout + '</td>';
            loginsTable += '</tr>';
        }
        loginsTable += '</tbody></table>';
        $('#spylogUserLog').append(loginsTable);
    };
    $.adminCtrl.addUser = function () {
        if (confirm('Создать нового пользователя?')) {
            $.post(armffURL + 'ajax/adminpanel/users/add').done(function (data) {
                if (data) {
                    $('#spylogFilter [name="name"]').val(data.name);
                    $.adminCtrl.filterLogs();
                }
            });
        }
        return false;
    };
    $.adminCtrl.createCustomer = function () {
        if (confirm('Создать физ.лицо для пользователя?')) {
            $.post(armffURL + 'ajax/adminpanel/users/' + $('#adminpanelUserDetails [name="user_id"]').val() + '/createcustomer').done(function (data) {
                if (data && data != 0) {
                    $('#createCustomerBtn').hide();
                    $('#adminpanelUserDetails .passports-btns').empty().append('<a class="btn btn-default btn-sm" href="/customers/edit/' + data.customer_id + '/' + data.passport_id + '">Редакт. физ.лицо</a>');
                }
            });
        }
        return false;
    };
    $.adminCtrl.addRole = function () {
        var $item = $('#userRolesList .role-item-template').clone(true);
        $item.removeClass('role-item-template hidden');
        $('#userRolesList').append($item);
    };
    $.adminCtrl.saveRole = function (btn) {
        var sendData = {
            role_id: $(btn).parents('.role-item:first').find('[name="name"]').val(),
            user_id: $('#adminpanelUserDetails input[name="user_id"]').val()
        };
        $.post($.app.url + '/adminpanel/roles/grant', sendData).done(function (data) {
            $.app.ajaxResult(data);
            $.adminCtrl.addRole();
        });
    };
    $.adminCtrl.saveDebtorSlaves = function () {
        $.post(armffURL + 'ajax/adminpanel/users/saveDebtorUserSlaves', $('#formDebtorUserSlaves').serialize()).done(function (data) {
            $('#closeDebtorUserSlaves').click();
            $.app.ajaxResult(data);
        });
    };
    $.adminCtrl.toggleAllSlaves = function (cb) {
        $('#masterUsers [name="masterTo[]"]').prop('checked', $(cb).prop('checked'));
    };
    $.adminCtrl.setEmploymentFields = function () {
        $.post($.app.url + '/ajax/adminpanel/users/employment/' + $('#adminpanelUserDetails input[name="user_id"]').val()).done(function (data) {
            $.app.ajaxResult(data);
        });
    };
})(jQuery);