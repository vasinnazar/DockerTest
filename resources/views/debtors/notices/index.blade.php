@extends('app')
@section('title') Реестр писем @stop
@section('content')
@if ($taskInProgress)
<div class='row'>
    <div class="col-xs-12">
        Задача выполняется.
    </div>
</div>
@else
<div class='row'>
    <div class="col-xs-12">
        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#debtorNoticesFilterModal"><span class='glyphicon glyphicon-search'></span> Фильтр</button>
    </div>
</div>
<div class="row">
    <div class="col-xs-12">
        <table class="table table-condensed table-striped table-bordered">
            <thead>
                <tr>
                    <th>Дата задачи</th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tasks as $task)
                <tr>
                    <td>{{ date('d.m.Y H:i', strtotime($task->created_at)) }}</td>
                    <td>
                        <a href="/debtors/notices/getFile/xls/{{ $task->id }}">Скачать Excel</a>
                    </td>
                    <td>
                        <a href="/debtors/notices/getFile/zip/{{ $task->id }}">Скачать ZIP-архив</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="debtorNoticesFilterModal" tabindex="-1" role="dialog" aria-labelledby="debtorNoticesFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="debtorNoticesFilterModalLabel">Фильтр реестра требований</h4>
            </div>
            <form action="/debtors/notices/start">
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <table class='table table-borderless' id='debtorNoticesFilter'>
                            <tr>
                                <td>Дата закрепления, с</td>
                                <td></td>
                                <td><input name="fixation_date_from" type='text' class='form-control'/></td>
                            </tr>
                                <tr>
                                <td>Дата закрепления, до</td>
                            <td></td>
                                <td><input name="fixation_date_to" type='text' class='form-control'/></td>
                            </tr>
                            <tr>
                                <td>Дней просрочки, от</td>
                                <td>
                                </td>
                                <td>
                                    <input name="qty_delays_from" type="number" class="form-control">
                                </td>
                            </tr>
                            <tr>
                                <td>Дней просрочки, до</td>
                                <td>
                                </td>
                                <td>
                                    <input name="qty_delays_to" type="number" class="form-control">
                                </td>
                            </tr>
                            <tr>
                                <td>Адрес: </td>
                                <td></td>
                                <td>
                                    <select name="address_type">
                                        <option value="0">Регистрации</option>
                                        <option value="1">Фактический</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Группа долга: </td>
                                <td></td>
                                <td>
                                    <select name="debt_group_ids[]" multiple="multiple">
                                        <option value="4">Бесконтактный</option>
                                        <option value="5">Сложный</option>
                                        <option value="6">Безнадежный</option>
                                        @if($user->isDebtorsRemote())
                                            <option value="8">Розыск</option>
                                        @endif
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>База: </td>
                                <td></td>
                                <td>
                                    <select name="debt_base_ids[]" multiple="multiple">
                                        @if($user->isDebtorsRemote())
                                            <option value="7">Б-1</option>
                                        @endif
                                        <option value="9">Б-3</option>
                                        <option value="19">Б-риски</option>
                                        <option value="29">Б-график</option>
                                        <option value="30">КБ-график</option>
                                        <option value="18">Б-МС</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Ответственный: </td>
                                <td></td>
                                <td>
                                    <select name="responsible_users_ids[]" multiple="multiple" size="8">
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @foreach ($user->subordinatedUsers as $subordinated)
                                            <option value="{{ $subordinated->id }}">{{ $subordinated->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            @if($user->isDebtorsRemote())
                                <tr>
                                    <td>Сумма задолженности от :</td>
                                    <td></td>
                                    <td><input name="amount_owed" type='number' class='form-control'/></td>
                                </tr>
                                <tr>
                                    <td>Дней просрочки, от</td>
                                    <td></td>
                                    <td><input name="overdue_from" type='number' class='form-control'/></td>
                                </tr>
                                <tr>
                                    <td>Дней просрочки, до</td>
                                    <td></td>
                                    <td><input name="overdue_till" type='number' class='form-control'/></td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                @if($user->isDebtorsRemote())
                    <div class="pull-left" style="text-align: left;">
                        <span><input type="checkbox" name="loan_sbp" value="1">&nbsp;СБП</span>
                    </div>
                @endif
                {!!Form::button('Очистить фильтр',['class'=>'btn btn-default','type'=>'button', 'id'=>'debtorNoticesClearFilterBtn'])!!}
                <button type="submit" class="btn btn-primary">Создать реестр</button>
            </div>
            </form>
        </div>
        </div>
</div>
@endif
@stop
@section('scripts')
        <script src="{{asset('js/debtors/debtorsController.js?2')}}"></script>
@stop
