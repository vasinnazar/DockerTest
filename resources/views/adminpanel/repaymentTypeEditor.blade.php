@extends('adminpanel')
@section('title') Редактор вида гашения @stop

@section('subcontent')
{!! Form::model($repaymentType,['route'=>'adminpanel.repaymenttypes.update'])!!}
{!! Form::hidden('id') !!}
<div class="row">
    <div class="form-group col-xs-12 col-md-12">
        <label>Наименование</label>
        {!! Form::text('name',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-1">
        <label>Сумма ОД</label>
        {!! Form::text('od_money',null,['class'=>'form-control money-mask']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-1">
        <label>Сумма %</label>
        {!! Form::text('percents_money',null,['class'=>'form-control money-mask']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-1">
        <label>Сумма проср. %</label>
        {!! Form::text('exp_percents_money',null,['class'=>'form-control money-mask']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-1">
        <label>Просроченные %</label>
        {!! Form::text('exp_percent',null,['class'=>'form-control','data-mask'=>'#0.00','data-mask-reverse'=>'true']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-1">
        <label>Проценты</label>
        {!! Form::text('percent',null,['class'=>'form-control','data-mask'=>'#0.00','data-mask-reverse'=>'true']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-1">
        <label>Сумма пени</label>
        {!! Form::text('fine_money',null,['class'=>'form-control money-mask']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-1">
        <label>Пеня</label>
        {!! Form::text('fine_percent',null,['class'=>'form-control','data-mask'=>'#0.00','data-mask-reverse'=>'true']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-2">
        <label>% после просрочки</label>
        {!! Form::text('pc_after_exp',null,['class'=>'form-control','data-mask'=>'#0.00','data-mask-reverse'=>'true']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-3">
        <label>Срок заморозки в днях</label>
        <input type="number" value="{{$repaymentType->freeze_days}}" class="form-control" name="freeze_days"/>
    </div>
    <div class="form-group col-xs-12 col-md-2">
        <label>Текстовый идентификатор</label>
        {!!Form::text('text_id',null,['class'=>'form-control'])!!}
    </div>
    <div class="form-group col-xs-12 col-md-2">
        <label>Кол-во дней по-умолчанию</label>
        {!!Form::text('default_time',null,['class'=>'form-control'])!!}
    </div>
    <div class="form-group col-xs-12 col-md-6">
        {!!Form::checkbox('add_after_freeze',1,null,['class'=>'checkbox','id'=>'addAfterFreezeCb'])!!}
        <label for="addAfterFreezeCb">Начислять проценты за дни заморозки, по истечению срока договора</label>
    </div>
    <div class="form-group col-xs-12 col-md-6">
        {!!Form::checkbox('mandatory_percents',1,null,['class'=>'checkbox','id'=>'mandatoryPercentsCb'])!!}
        <label for="mandatoryPercentsCb">Обязательная выплата процентов для заключения договора</label>
    </div>
</div>
<div class='row'>
    <div class="form-group col-xs-12 col-md-6">
        <label>Форма договора (нал)</label>
        {!!Form::select('contract_form_id',$contractForms,null,['class'=>'form-control'])!!}
    </div>
    <div class="form-group col-xs-12 col-md-6">
        <label>Форма договора для постоянных клиентов (нал)</label>
        {!!Form::select('perm_contract_form_id',$contractForms,null,['class'=>'form-control'])!!}
    </div>
    <div class="form-group col-xs-12 col-md-6">
        <label>Форма договора (карта)</label>
        {!!Form::select('card_contract_form_id',$contractForms,null,['class'=>'form-control'])!!}
    </div>
    <div class="form-group col-xs-12 col-md-6">
        <label>Форма договора для постоянных клиентов (карта)</label>
        {!!Form::select('card_perm_contract_form_id',$contractForms,null,['class'=>'form-control'])!!}
    </div>
</div>
<div class='row'>
    <div class="form-group col-xs-12 col-md-12">
        <label>Порядок создания приходников</label>
        {!! Form::textarea('payments_order',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-12">
        <label>Условие</label>
        {!! Form::textarea('condition',null,['class'=>'form-control']) !!}
    </div>
</div>
{!! Form::button('Сохранить',['class'=>'btn btn-primary pull-right','type'=>'submit']) !!}
{!! Form::close() !!}
@stop
@section('scripts')
<script>
    $(function () {
        $('.money-mask').myMoney();
//        $('.money-mask').maskMoney({allowNegative: true, thousands:'', decimal:'.', allowZero:true}).maskMoney('mask');
    });
</script>
@stop