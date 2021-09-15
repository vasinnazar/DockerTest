@extends('app')
@section('title') Список должников @stop
@section('content')
<div class='row'>
    <div class='col-xs-12'>
        <a class="btn btn-default" href="#" data-toggle="modal" data-target="#searchDebtorModal"><span class="glyphicon glyphicon-search"></span> Поиск</a>
        <button class="btn btn-default" id="clearDebtorsFilterBtn" disabled>Очистить фильтр</button>
        <button class="btn btn-default" id="repeatLastSearchBtn">Повторить последний запрос</button>
        <table id="debtorsTable" class="table table-borderless table-condensed table-striped">
            <thead>
                <tr>
                    <th>Должник</th>
                    <th>Контрагент</th>
                    <th>ОД</th>
                    <th>Проценты</th>
                    <th>Пр. проценты</th>
                    <th>Пеня</th>
                    <th>Госпошлина</th>
                    <th></th>
                </tr>
            </thead>
        </table>
    </div>
</div>
<div class='row'>
    <div class='col-xs-12'>
        <h3>Мероприятие</h3>
        <hr>
        {!! Form::open(['url'=>'debtors/event/update']) !!}
        <a></a>
        <div>
            <button class='btn btn-default'>Карточка должника</button>
            <div class="pull-right"><a href="{{url('/debtors/calendar')}}" class="btn btn-success">Календарь</a>
        </div>
        <div class='form-group form-inline'>
            <label>Тип мероприятия</label>
            {!! Form::select('event_type_id',$event_types,null,['class'=>'form-control']) !!}
        </div>
        <div class='form-group form-inline'>
            <label>Дата</label>
            <input type='date' name='date' class='form-control'/>
        </div>
        <div class='form-group form-inline'>
            <label>Причина просрочки</label>
            {!! Form::select('overdue_reason_id',$overdue_reasons,null,['class'=>'form-control']) !!}
        </div>
        <div class='form-group form-inline'>
            <label>Результат</label>
            {!! Form::select('event_result_id',$event_results,null,['class'=>'form-control']) !!}
        </div>
        <div class='form-group form-inline'>
            <label>Группа долга</label>
            {!! Form::select('debt_group_id',$debt_groups,null,['class'=>'form-control']) !!}
        </div>
        <div class='form-group'>
            <label>Отчет о мероприятии</label>
            {!! Form::textarea('report',null,['class'=>'form-control']) !!}
        </div>
        <h3>Планирование</h3>
        <hr>
        <div class='form-group form-inline'>
            <label>Тип мероприятия</label>
            {!! Form::select('plan_event_type_id',$event_types,null,['class'=>'form-control']) !!}
        </div>
        <div class='form-group form-inline'>
            <label>Дата</label>
            <input type='date' name='plan_date' class='form-control'/>
        </div>
        <button class='btn btn-primary' type='submit'>Сохранить</button>
        {!! Form::close() !!}
    </div>
</div>

@stop
@section('scripts')
<script src="{{asset('js/debtors/debtorsController.js')}}"></script>
<script>
$(document).ready(function () {
    $.debtorsCtrl.init();
});
</script>
@stop