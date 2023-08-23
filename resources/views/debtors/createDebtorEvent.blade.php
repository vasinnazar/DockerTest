<div class="col-xs-12 col-sm-6 col-lg-8">
    <form action="/debtors/addevent" id="event-form" enctype="multipart/form-data" method="POST">
        {{ csrf_field() }}
        <input type="hidden" name="debtor_id" value="{{ $debtor->id }}">
        <input type="hidden" name="user_id" value="{{ $user->id }}">
        <div class="row">
            <div class="col-xs-12 col-lg-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Редактирование мероприятия
                    </div>
                    <div class="panel-body">
                        <div class='form-horizontal'>
                            <div class='form-group'>
                                <label class='col-xs-12 col-sm-4 text-right'>Дата мероприятия:</label>
                                <div class='col-xs-12 col-sm-8 form-inline'>
                                    <input id="datetimepickerCreate"
                                           type="text"
                                           name="created_at"
                                           value="{{date('d.m.Y H:i', time())}}"
                                           class="form-control"
                                           readonly>
                                </div>
                            </div>
                            <div class='form-group'>
                                <label class='col-xs-12 col-sm-4 text-right'>Тип мероприятия:</label>
                                <div class='col-xs-12 col-sm-8'>
                                    <select id="eventTypeId" name="event_type_id" class="form-control">
                                        <option value=""></option>
                                        @foreach ($debtdata['event_types'] as $k => $type)
                                            <option value="{{$k}}">{{$type}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class='form-group'>
                                <label class='col-xs-12 col-sm-4 text-right'>Группа долга:</label>
                                <div class='col-xs-12 col-sm-8'>
                                    @php
                                        $sel_disabled = '';
                                        $bool_sel_disabled = false;
                                        if ($debtor->base == 'Архив убытки' || $debtor->base == 'Архив компании') {
                                            $sel_disabled = ' disabled';
                                            $bool_sel_disabled = true;
                                        }
                                    @endphp
                                    <select name="debt_group_id" class="form-control"{{$sel_disabled}}>
                                        @if ($debtor->debt_group_id == null && $bool_sel_disabled)
                                            <option value="" selected disabled></option>
                                        @else
                                            <option value=""></option>
                                            @foreach ($debtdata['debt_groups'] as $k => $type)
                                                @php($selected = '')
                                                @if($bool_sel_disabled && $k == $debtor->debt_group_id)
                                                    @php($selected = ' selected')
                                                @endif
                                                @continue ($user->hasRole('debtors_personal') && !in_array($k,
                                                        $availableData['debtGroupPersonal']))
                                                @continue ($user->hasRole('debtors_remote') && !in_array($k,
                                                        $availableData['debtGroupRemote']))
                                                <option value="{{ $k }}"{{$selected}}>{{$type}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class='form-group'>
                                <label class='col-xs-12 col-sm-4 text-right'>Результат:</label>
                                <div class='col-xs-12 col-sm-8'>
                                    <select name="event_result_id" class="form-control">
                                        <option value=""></option>
                                        @foreach ($debtdata['event_results'] as $k => $type)
                                            @continue ($user->hasRole('debtors_remote') &&
                                                !in_array($k, $availableData['eventResultRemote']))
                                            <option value="{{$k}}">{{$type}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class='form-group'>
                                <label class='col-xs-12 col-sm-4 text-right'>Статус соединения:</label>
                                <div class='col-xs-12 col-sm-8'>
                                    <select id="connectionStatusId" name="connection_status_id" class="form-control">
                                        <option value=""></option>
                                        @foreach ($debtdata['connection_status'] as $k => $type)
                                            <option value="{{$k}}">{{$type}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @if ($user->hasRole('debtors_chief') || $user->hasRole('can_edit_all_debtors'))
                                <div class='form-group' id='chief_event_field'>
                                    <label class='col-xs-12 col-sm-4 text-right'>От имени:</label>
                                    <div class='col-xs-12 col-sm-8 form-inline'>
                                        <input
                                                disabled
                                                id="usersLogin"
                                                name='users@login'
                                               type='text'
                                               class='form-control autocomplete'
                                               data-hidden-value-field='search_field_users@id'
                                               style='width: 100%;'/>
                                        <input id="chief_from_user_id"
                                               name='search_field_users@id'
                                               type='hidden'/>
                                    </div>
                                </div>
                            @endif
                            <div class='form-group'>
                                <label class='col-xs-12 col-sm-4 text-right'>Отчет о мероприятии:</label>
                                <div class='col-xs-12 col-sm-8'>
                                    <textarea style="min-height: 150px; max-width: 100%;"
                                              name="report"
                                              class="form-control"></textarea>
                                </div>
                            </div>
                            <div class='form-group'>
                                <div class='col-xs-12'>
                                    <label class="btn btn-default btn-file pull-right">
                                        Прикрепить фото <input name="messenger_photo"
                                                               type="file"
                                                               onchange="$('#upload-file-info').text(this.files[0].name)"
                                                               style="display: none;">
                                    </label>
                                </div>
                            </div>
                            <div class='form-group'>
                                <div class='col-xs-12'>
                                    <span class='label label-info pull-right' id="upload-file-info"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-lg-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Планирование
                    </div>
                    <div class="panel-body form-horizontal">
                        <div class="form-group">
                            <label class='col-xs-12 col-sm-4 text-right'>Тип мероприятия:</label>
                            <div class='col-xs-12 col-sm-8'>
                                <select name="event_type_id_plan" class="form-control">
                                    <option value=""></option>
                                    @foreach ($debtdata['event_types'] as $k => $type)
                                        <option value="{{$k}}">{{$type}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label class='col-xs-12 col-sm-4 text-right'>Дата мероприятия</label>
                            <div class='col-xs-12 col-sm-8 form-inline'>
                                <input id="datetimepickerPlan" type="text" name="date" class="form-control">
                            </div>
                        </div>
                        <div class='form-group'>
                            <label class='col-xs-12 col-sm-4 text-right'>Сумма договоренности</label>
                            <div class='col-xs-12 col-sm-8 form-inline'>
                                <input type="text" name="promise_pay_amount" class="form-control" disabled>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Договоренность о закрытии кредитного договора
                    </div>
                    <div class="panel-body form-horizontal">
                        <div class='form-group'>
                            <label class='col-xs-12 col-sm-4 text-right'>Дата договоренности</label>
                            <div class='col-xs-12 col-sm-8 form-inline'>
                                <input id="datetimepickerProlongationBlock" type="text"
                                       name="dateProlongationBlock" class="form-control">
                            </div>
                        </div>
                        <div class="text-center">
                            <br>
                            <button id="submit_event" type="submit" class="btn btn-primary btn-lg">
                                <span class="glyphicon glyphicon-floppy-disk"></span> Сохранить
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>