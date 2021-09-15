@extends('reports.reports')
@section('title')Ежедневные отчёты@stop
@section('subcontent')
<div>
    @if(is_null($report))
    <a href="{{url('reports/dailycashreport')}}" class="btn btn-default">Создать</a>
    @else
    <a href="{{url('reports/dailycashreport',[$report->id])}}" class="btn btn-default">Редактировать</a>
    <button class="btn btn-default" onclick="$.reportsListCtrl.matchWithCashbook({{$report->id}}); return false;">Сверить</button>
    <button class="btn btn-default"><span class="glyphicon glyphicon-print"></span> Распечатать за сегодня</button>
    @endif
    @if(Auth::user()->isAdmin())
    <div id="reportsFilter" class="form-inline">
        <label>Название подразделения</label>
        <!--<input class="form-control input-sm" name="subdiv_name" placeholder="примерное название подразделения"/>-->
        <input type='hidden' name='subdivision_id' />
        <input type='text' name='subdivision_id_autocomplete' class='form-control input-sm' data-autocomplete='subdivisions' placeholder="подразделение" />
        <input class="form-control input-sm" name="created_at" type="date"/>
        <input class="form-control input-sm" name="subdivision_code" type="text" placeholder="код подразделения"/>
        <button class="btn btn-primary btn-sm" id="reportsFilterBtn">
            <span class="glyphicon glyphicon-search"></span>
        </button>
    </div>
    @endif
</div>
@if(Auth::user()->subdivision_id != 107)
<!--<h2><span class="label label-warning">Внимание! Ежедневный отчёт временно недоступен для Вашего подразделения. Заполняйте отчёт в 1С</span></h2>-->
@endif
<br>
<table class="table-borderless table table-condensed compact" id="reportsTable">
    <thead>
        <tr>
            <th>Совпадает</th>
            <th>Дата</th>
            <th>Баланс на начало периода</th>
            <th>Баланс на конец периода</th>
            <th>Ответственный</th>
            <th></th>
        </tr>
    </thead>
</table>
@stop
@section('scripts')
<script src="{{URL::asset('js/libs/jqueryui/jquery-ui.min.js')}}"></script>
<script src="{{ URL::asset('js/reports/reportsListController.js') }}"></script>
<script>
                (function () {
                $.reportsListCtrl.init();
                        })(jQuery);
</script>
@stop