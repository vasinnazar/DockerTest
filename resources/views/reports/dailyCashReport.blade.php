@extends('reports.reports')
@section('title')Ежедневный отчёт@stop
@section('subcontent')
<table class='table table-bordered table-condensed daily-report-calc-table'>
    <tr>
        <td rowspan="2">Денег в кассе<br> на начало дня</td>
        <td colspan="3">Отчет</td>
        <td rowspan="2">Денег в кассе<br> на конец дня</td>
    </tr>
    <tr>
        <td>Приход</td>
        <td>Расход</td>
        <td>Итого по отчету</td>
    </tr>
    <tr class='money-row'>
        <td id="cbStartBalance">{!!number_format($report->start_balance/100,2,'.','')!!}</td>
        <td id="dcrIncome"></td>
        <td id="dcrOutcome"></td>
        <td id="dcrTotal"></td>
        <td id="cbEndBalance">{!!number_format($report->end_balance/100,2,'.','')!!}</td>
    </tr>
</table>
<br>
<table class="table-bordered table" id="cashbookTable">
    <thead>
        <tr>
            <th>Контрагент</th>
            <th>Вид операции</th>
            <th>Вид док</th>
            <th>Номер документа</th>
            <th>Сумма</th>
            <th>Комментарий</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><input name="fio" class="form-control input-sm"/></td>
            <td>
                <select name="action" class="form-control input-sm">
                    <option value="0" style="background-color: #ccffcc">Приход</option>
                    <option value="1" style="background-color: #ffcccc">Расход</option>
                    <option value="2" style="background-color: #ffcccc">Зачисление на карту</option>
                    @if(\Carbon\Carbon::now()->lt(new \Carbon\Carbon('2017-02-21')))
                    <option value="3" style="background-color: #ccffcc">Перемещено из офиса</option>
                    <option value="4" style="background-color: #ffcccc">Перемещено в офис</option>
                    @endif
                    <option value="5" style="background-color: #ffcccc">Инкассация с точек в банк</option>
                </select>
            </td>
            <td>
                <!--<input name="doctype" class="form-control input-sm"/>-->
                <select name="doctype" class="form-control input-sm">
                    <option value="0">Основной договор</option>
                    <option value="1">Дополнительный договор</option>
                    <option value="2">Соглашение об урегулировании задолженности</option>
                    <option value="3">Соглашение о приостановке начисления процентов</option>
                    <option value="4">Судебное урегулирование задолженности</option>
                </select>
            </td>
            <td><input name="doc" class="form-control input-sm"/></td>
            <td><input name="money" class="form-control input-sm"/></td>
            <td><input name="comment" class="form-control input-sm"/></td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-default" name="add"><span class="glyphicon glyphicon-plus"></span></button>
                    <button class="btn btn-default" name="remove"><span class="glyphicon glyphicon-trash"></span></button>
                </div>
            </td>
        </tr>
    </tbody>
</table>
{!! Form::model($report,['url'=>'/reports/update','id'=>'reportForm']) !!}
{!!Form::hidden('id')!!}
{!!Form::hidden('data')!!}
{!!Form::hidden('type')!!}
{!!Form::hidden('start_balance')!!}
{!!Form::hidden('end_balance')!!}
@if(Auth::user()->isAdmin())
<div class="form-group col-xs-12">
    <label>Подразделение</label>
    <input type='hidden' name='subdivision_id' />
    <input type='text' name='subdivision_id_autocomplete' class='form-control' data-autocomplete='subdivisions' placeholder="@if(!is_null($report->subdivision_id)) {{$report->subdivision->name}} @endif" />
</div>
<div class="form-group col-xs-12">
    <label>Специалист</label>
    <input type='hidden' name='user_id' />
    <input type='text' name='user_id_autocomplete' class='form-control' data-autocomplete='users' placeholder="@if(!is_null($report->user_id)) {{$report->user->name}} @endif" />
</div>
<div class="form-group col-xs-12">
    <label>ID 1C</label>
    {!!Form::text('id_1c',null,['class'=>'form-control'])!!}
</div>
<div class="form-group col-xs-12">
    <label>Дата</label>
    {!!Form::text('created_at',null,['class'=>'form-control'])!!}
</div>
@endif
<div class="btn-group pull-right">
    <button type="submit" class="btn btn-primary" id="reportSubmitBtn">Сохранить</button>
</div>
{!! Form::close() !!}
@stop
@section('scripts')
<script src="{{URL::asset('js/libs/jqueryui/jquery-ui.min.js')}}"></script>
<script src="{{ URL::asset('js/reports/reportController.js?2') }}"></script>
<script>
(function () {
    $.reportCtrl.init();
})(jQuery);
</script>
@stop