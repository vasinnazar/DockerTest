@extends('adminpanel')
@section('title')Специалисты@stop
@section('css')
<link rel="stylesheet" href="{{ asset('js/libs/jqueryui/jquery-ui.min.css') }}">
@stop
@section('subcontent')
<div class="row">
    <div class="col-sm-3" style="overflow: hidden">
        <div id="spylogFilter" class="form-inline">
            <div class="input-group">
                <input class="form-control input-sm" name="name" placeholder="Имя пользователя"/>
                <span class="input-group-btn">
                    <button onclick="$.adminCtrl.filterLogs();
                            return false;" class="btn btn-primary btn-sm">
                        <span class="glyphicon glyphicon-search"></span>
                    </button>
                </span>
            </div>
            <button class="btn btn-default btn-sm" onclick="$.adminCtrl.addUser()"><span class="glyphicon glyphicon-plus"></span></button>
        </div>
        <table class="table table-condensed table-borderless compact" id="spylogUsersTable">
            <thead>
                <tr>
                    <th>Пользователь</th>
                    <th></th>
                </tr>
            </thead>
        </table>
    </div>
    <div class="col-sm-9" id="adminpanelUserDetails" style="display: none;">
        <div class="row">
            <div class="col-sm-12 col-lg-12">
                <h3>Данные пользователя</h3>
                <hr>
                {!! Form::open(['url'=>'ajax/adminpanel/users/update', 'class'=>'form-horizontal', 'id'=>'userDataForm']) !!}
                <div class="form-group">
                    <label class="control-label col-sm-2">ID</label>
                    <div class="col-sm-10">
                        <input type="text" readonly name="user_id" class="form-control"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2">ФИО</label>
                    <div class="col-sm-10">
                        {!! Form::text('name',null,['class'=>'form-control input-sm']) !!}
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2">Логин</label>
                    <div class="col-sm-10">
                        {!! Form::text('login',null,['class'=>'form-control input-sm']) !!}
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2">Должность</label>
                    <div class="col-sm-10">
                        {!! Form::text('position',null,['class'=>'form-control input-sm']) !!}
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2">Телефон</label>
                    <div class="col-sm-10">
                        {!! Form::text('phone',null,['class'=>'form-control input-sm']) !!}
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2">Внутренний номер</label>
                    <div class="col-sm-10">
                        {!! Form::text('infinity_extension',null,['class'=>'form-control input-sm']) !!}
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2">Документ</label>
                    <div class="col-sm-10">
                        {!! Form::text('doc',null,['class'=>'form-control input-sm']) !!}
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2">Дата рождения</label>
                    <div class="col-sm-10">
                        <input type="date" class="form-control input-sm" name="birth_date"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2">Лимит SMS</label>
                    <div class="col-sm-10">
                        {!! Form::text('sms_limit',null,['class'=>'form-control input-sm']) !!}
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2">ID в 1С</label>
                    <div class="col-sm-10">
                        {!! Form::text('id_1c',null,['class'=>'form-control input-sm']) !!}
                        <br>
                        <div class=" alert alert-warning">
                            При смене фамилии ID_1C тоже меняется!
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2">Группа по пользователю</label>
                    <div class="col-sm-10">
                        <input type="hidden" name="user_group_id" value="" />
                        <input type='text' name='user_group_id_autocomplete' class='form-control input-sm' data-autocomplete='users' value="" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2">Регион</label>
                    <div class="col-sm-10">
                        <input type="hidden" name="region_id"/>
                        <input type='text' name='region_id_autocomplete' class='form-control input-sm' data-autocomplete='users' />
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-12">

                    </div>
                </div>
                <div class="pull-right">
                    <div class="passports-btns btn-group btn-group-sm">

                    </div>
                    <button type='button' class='btn btn-default btn-sm' onclick="$.adminCtrl.setEmploymentFields();
                            return false;">Разрешить зайти без док-ов на трудоустройство</button>
                    <a href="#" class="btn btn-default btn-sm" onclick="$.adminCtrl.createCustomer(this);
                            return false;" id="createCustomerBtn">Создать физ.лицо</a>
                    <button class="btn btn-primary btn-sm">Сохранить</button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-lg-12">
                <h3>Ограничение доступа</h3>
                <hr>
                {!! Form::open(['url'=>'ajax/adminpanel/users/bantime', 'class'=>'form-inline', 'id'=>'bantimeForm']) !!}
                <input name="begin_time" type="hidden" />
                <input name="end_time" type="hidden" />
                <input type="hidden" name="user_id"/>
                <p class="slider-range-label">Разрешить доступ с <b>0</b> часов до <b>24</b> часов</p>
                <div class="slider-range"></div>
                <br>
                <label>Заблокировать после:</label>
                <input name="ban_at" class="form-control" type="date"/>
                <br><br>
                <div class="form-group">
                    <input type="checkbox" name="banned" id="userBanCheckbox" class="checkbox"/>
                    <label for="userBanCheckbox">Заблокировать</label>
                </div>
<!--                <div class="form-group">
                    <input type="checkbox" name="ban_http" id="userBanHttpCheckbox" class="checkbox"/>
                    <label for="userBanHttpCheckbox">Заблокировать доступ по HTTP</label>
                </div>-->
                <br>
                <div class="pull-right">
                    <a href="#" class="btn btn-default btn-sm" id="createSSLBtn">Получить SSL сертификат</a>
                    <button class="btn btn-default btn-sm" id="refreshUserLastLogin">Обновить время последнего входа</button>
                    <button class="btn btn-primary btn-sm" type="submit">Сохранить</button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
        <div class='row'>
            <div class='col-xs-12'>
                <h3>Роли</h3>
                <hr>
                <form id='userRolesForm'>
                    <div class='form-group'>
                        <ul id='userRolesList' class='list-group'>
                            @foreach($roles as $role)
                            <li class='list-group-item'>
                                <label>
                                    <input type='checkbox' name='role[]' value="{{$role->id}}" data-type="{{$role->name}}"/> {{$role->description}} ({{$role->name}})
                                    @if ($role->name == 'debtors')
                                    &nbsp;&nbsp;<span class="debtors-modal-link" style="display: none;"><button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#masterUsers">указать подчиненных</button></span>
                                    @endif
                                </label>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class='form-group'>
                        <button type='submit' class='btn btn-primary'>Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
		<div class='row'>
            <div class='col-xs-12'>
                <h3>Доступ к персональным данным</h3>
                <hr>
                <form id='userPermissionsForm'>
                    <div class='form-group'>
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th scope="col">Вид ПД</th>
                                <th scope="col">Действует до</th>
                            </tr>
                            </thead>
                            <tbody id="userPermissionsList">
                                @foreach($permissions as $permission)
                                    <tr>
                                        <td class="text-left">
                                            <input type='checkbox' name='permission[]'
                                                   value="{{$permission->id}}"
                                                   data-type="{{$permission->name}}"
                                            />
                                            {{$permission->description}} ({{$permission->name}})
                                        </td>
                                        <td>
                                            <input
                                                class="form-control input-sm" name="{{'date' . $permission->id}}"
                                                type="datetime-local" id="{{'permissionUntil' . $permission->id}}"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class='form-group'>
                        <button type='submit' class='btn btn-primary'>Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-lg-12">
                <h3>Сменить пароль</h3>
                <hr>
                {!! Form::open(['url'=>'ajax/adminpanel/users/changepass', 'class'=>'form-inline', 'id'=>'changePasswordForm']) !!}
                <input type="hidden" name="user_id"/>
                <input class="form-control input-sm" name="old_password" placeholder="Старый пароль"/>
                <input class="form-control input-sm" name="password" placeholder="Новый пароль"/>
                <button class="btn btn-primary btn-sm">Сменить</button>
                {!! Form::close() !!}
                <br>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-lg-12">
                <h3>Время входа\выхода</h3>
                <hr>
                {!! Form::open(['url'=>'ajax/adminpanel/users/userlog', 'class'=>'form-inline', 'id'=>'userLogForm']) !!}
                <input type="hidden" name="user_id"/>
                <div class="input-group input-group-sm">
                    <span class="input-group-addon">от</span>
                    <input class="form-control input-sm" name="from" placeholder="Дата от" type="date"/>
                </div>
                <div class="input-group input-group-sm">
                    <span class="input-group-addon">до</span>
                    <input class="form-control input-sm" name="to" placeholder="Дата по" type="date"/>
                </div>
                {!! Form::close() !!}
                <div id="spylogUserLog"></div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-lg-12">
                <h3>Сменить подразделение</h3>
                <hr>
                {!! Form::open(['url'=>'ajax/adminpanel/users/changesubdivision', 'class'=>'form-inline', 'id'=>'changeSubdivisionForm']) !!}
                <input type="hidden" name="user_id"/>
                <div>
                    Текущее подразделение:
                    <input disabled name="subdivision_name" style="width:50%;" class="form-control"/>
                    <br>
                    <br>
                </div>
                <input type='hidden' name='subdivision_id' />
                <input style="width: 50%" type='text' name='subdivision_id_autocomplete' class='form-control' data-autocomplete='subdivisions' />
                <button class="btn btn-primary btn-sm">Сменить</button>
                <span class="subdivision-change-status">
                    <span class="label label-success hidden">Сохранено</span>
                    <span class="label label-danger hidden">Ошибка</span>
                </span>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>
@include('elements.debtorsMasterUsers')

@stop
@section('scripts')
<script src="{{URL::asset('js/libs/jqueryui/jquery-ui.min.js')}}"></script>
<script src="{{ URL::asset('js/adminpanel/adminpanelController.js') }}"></script>
<script>
                        (function () {
                            var subdivslist = [];
                            $('input[name="subdivision_id"] option').each(function () {
                                subdivslist.push({id: $(this).attr('value'), name: $(this).text()});
                            });
                            $.adminCtrl.usersList();

                            $(document).on('change', 'input[data-type="debtors"]', function () {
                                if ($(this).is(':checked')) {
                                    $('.debtors-modal-link').show();
                                } else {
                                    $('.debtors-modal-link').hide();
                                }
                            });

                            $(document).on('click', '#saveDebtorUserSlaves', function () {
                                $.adminCtrl.saveDebtorSlaves();
                            });
                        })(jQuery);
</script>
@stop