@extends('reports.reports')
@section('title') Авансовые отчеты @stop
@section('subcontent')
<p id='advancereportsFilter' class='form-inline'>
    <input name='created_at' class='form-control' type='date'/>
    {!! Form::hidden('user_id') !!}
    <input type='text' name='user_id_autocomplete' class='form-control' data-autocomplete='users' placeholder="Специалист" />
    {!! Form::hidden('subdivision_id') !!}
    <input type='text' name='subdivision_id_autocomplete' class='form-control' data-autocomplete='subdivisions' placeholder="Подразделение" />
    <button type='button' class="btn btn-default" id="advancereportsClearFilterBtn" data-dismiss="modal">
        Очистить
    </button>
    <button type='button' class="btn btn-primary" id="advancereportsFilterBtn" data-dismiss="modal">
        <span class="glyphicon glyphicon-search"></span> Поиск
    </button>
</p>
<div class='btn-toolbar'>
    <a class='btn btn-default' href="{{url('/reports/advancereports/create')}}">
        <span class='glyphicon glyphicon-plus'></span> Создать
    </a>
    @if(Auth::user()->isAdmin())
    <a class='btn btn-default' href="{{url('reports/nomenclature/upload')}}">
        <span class='glyphicon glyphicon-refresh'></span> Загрузить список номенклатуры из 1С
    </a>
    {!! Form::open(['url'=>url('reports/advancereports/upload'),'class'=>'form-inline']) !!}
    <div class="input-group">
        <input type="text" class="form-control" name='advance_id_1c' placeholder="ID 1C">
        <span class="input-group-btn">
            <button class="btn btn-default" type="submit">Загрузить</button>
        </span>
    </div>
    {!! Form::close() !!}
    @endif
</div>
<table class='table table-borderless' id='advancereportsTable'>
    <thead>
        <tr>
            <th>Дата</th>
            <th>Специалист</th>
            <th>Подразделение</th>
            <th></th>
        </tr>
    </thead>
</table>
@stop
@section('scripts')
<script src="{{asset('js/common/TableController.js')}}"></script>
<script src="{{asset('js/reports/advanceReportController.js')}}"></script>
<script>
(function () {
    $.advRepCtrl.initList();
})(jQuery);
</script>
@stop
