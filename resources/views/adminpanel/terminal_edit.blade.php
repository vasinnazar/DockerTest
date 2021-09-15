@extends('adminpanel')
@section('title') Редактирование терминала @stop
@section('subcontent')

{!! Form::model($item,['route'=>'adminpanel.terminals.update','id'=>'terminalEditForm']) !!}
{!! Form::hidden('id') !!}
<div class="row">
    <div class="form-group col-xs-12 col-md-12">
        <label>Описание</label>
        {!! Form::text('description',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-12">
        <label>Адрес</label>
        {!! Form::text('address',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-12">
        <label>Идентификатор</label>
        {!! Form::text('id',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-12">
        <label>Hardware ID</label>
        {!! Form::text('HardwareID',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-12">
        <label>Пароль</label>
        {!! Form::text('password',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-12">
        <label>Заблокирован</label>
        {!! Form::text('is_locked',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-12">
        <label>Пользователь</label>
        {!! Form::select('user_id',$users,null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-12">
        <label>Кол-во купюр в приёмнике</label>
        {!! Form::text('bill_count',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-12">
        <label>Сумма в приёмнике(в копейках)</label>
        {!! Form::text('bill_cash',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-12">
        <label>Кол-во купюр в диспенсере</label>
        {!! Form::text('dispenser_count',null,['class'=>'form-control']) !!}
    </div>
</div>
{!! Form::button('Сохранить',['class'=>'btn btn-primary pull-right','type'=>'submit']) !!}
{!! Form::close() !!}
@stop
@section('scripts')
@stop