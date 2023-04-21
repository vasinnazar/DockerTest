@extends('app')
@section('title') Судебные приказы @stop
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
        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#debtorCourtsFilterModal"><span class='glyphicon glyphicon-search'></span> Фильтр</button>
    </div>
</div>
<div class='row'>
    <div class="col-xs-12">
        <table id="debtorCourtsTable" class="pull-right">
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
                        <a href="/debtors/courts/getFile/xls/{{ $task->id }}">Скачать Excel</a>
                    </td>
                    <td>
                        <a href="/debtors/courts/getFile/zip/{{ $task->id }}">Скачать ZIP-архив</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="debtorCourtsFilterModal" tabindex="-1" role="dialog" aria-labelledby="debtorCourtsFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="debtorCourtsFilterModalLabel">Фильтр реестра приказов</h4>
            </div>
            <form action="/debtors/courts/start">
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <table class='table table-borderless' id='debtorCourtsFilter'>
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
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @foreach ($user->subordinatedUsers as $subordinated)
                                        <option value="{{ $subordinated->id }}">{{ $subordinated->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                {!!Form::button('Очистить фильтр',['class'=>'btn btn-default','type'=>'button', 'id'=>'debtorCourtsClearFilterBtn'])!!}
                <button type="submit" class="btn btn-primary">Создать реестр</button>
            </div>
            </form>
        </div>
        </div>
</div>
@endif
@stop
@section('scripts')
        <script src="{{asset('js/debtors/debtorsController.js?6')}}"></script>
@stop
