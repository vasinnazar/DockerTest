@extends('app')
@section('title') Реестр писем @stop
@section('css')
<style>
    .debtors-frame{
        height: 250px;
        overflow-y:scroll;
    }
    .debtors-table-frame{
        border: 1px solid #ccc;
        padding: 10px;
    }
</style>
@stop
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
<div class='row'>
    <div class="col-xs-12">
        <table id="debtorNoticesTable" class="pull-right">
            <tr>
                <td style="padding-left: 20px; padding-right: 17px;">
                    <!--a href="/debtors/notices/start" class="btn btn-primary" target="_blank">Создать Excel</a>
                    <input type="button" id="startNoticesProcess" class="btn btn-primary" value="Создать реестр" /-->
                </td>
            </tr>
        </table>
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
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Ответственный: </td>
                                <td></td>
                                <td>
                                    <select name="responsible_users_ids[]" multiple="multiple" size="8">
                                        @foreach ($debtorUsers as $k => $u)
                                        <option value="{{ $k }}">{{ $u['name'] }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
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
        <script src="{{asset('js/debtors/debtorsController.js?5')}}"></script>
@stop
