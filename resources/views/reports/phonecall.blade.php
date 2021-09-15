@extends('reports.reports')
@section('title') Обзвон @stop
@section('subcontent')
@if(is_null($phonecall->id))
{!! Form::model($phonecall,['url'=>'reports/phonecall/save']) !!}
{!! Form::hidden('customer_id_1c') !!}
{!! Form::hidden('id') !!}
<div class='form-group col-xs-12 centered'>
    <div class="radio">
        <label>
            {!! Form::radio('phone_call_type',1,['id'=>'phoneCallTypeRadio0']) !!}
            День рождения
        </label>
    </div>
    <div class="radio">
        <label>
            {!! Form::radio('phone_call_type',2,['id'=>'phoneCallTypeRadio1']) !!}
            Закрытые договора
        </label>
    </div>
    <div class="radio">
        <label>
            {!! Form::radio('phone_call_type',3,['id'=>'phoneCallTypeRadio2']) !!}
            Пенсионеры
        </label>
    </div>
</div>
<div class='col-xs-12 centered'>
    <button type='submit' class='btn btn-primary'>Показать</button>
</div>
@else
{!! Form::model($phonecall,['url'=>'reports/phonecall/save']) !!}
{!! Form::hidden('customer_id_1c') !!}
{!! Form::hidden('id') !!}
<div class='form-group col-xs-12 centered'>
    <div class="radio">
        <label>
            {!! Form::radio('phone_call_type',1,['id'=>'phoneCallTypeRadio0']) !!}
            День рождения
        </label>
    </div>
    <div class="radio">
        <label>
            {!! Form::radio('phone_call_type',2,['id'=>'phoneCallTypeRadio1']) !!}
            Закрытые договора
        </label>
    </div>
    <div class="radio">
        <label>
            {!! Form::radio('phone_call_type',3,['id'=>'phoneCallTypeRadio2']) !!}
            Пенсионеры
        </label>
    </div>
</div>
<div class='form-group col-xs-12 col-md-9'>
    <label>ФИО</label>
    {!! Form::text('fio',null,['class'=>'form-control','disabled'=>'disabled']) !!}
</div>
<div class='form-group col-xs-12 col-md-3'>
    <label>Телефон</label>
    {!! Form::text('telephone',null,['class'=>'form-control','disabled'=>'disabled']) !!}
</div>
<div class='form-group col-xs-12 col-md-6'>
    <label>Дата последнего обзвона</label>
    {!! Form::text('last_date_call',null,['class'=>'form-control','disabled'=>'disabled']) !!}
</div>
<div class='form-group col-xs-12 col-md-6'>
    <label>Дата рождения</label>
    {!! Form::text('birth_date',null,['class'=>'form-control','disabled'=>'disabled']) !!}
</div>
<div class='form-group col-xs-12'>
    <label>Результат</label>
    {!! Form::select('call_result',[
    '1'=>'Недозвон',
    '2'=>'Дозвон',
    '3'=>'Оформить заявку',
    '4'=>'Никогда не звонить'
    ],null,['class'=>'form-control']) !!}
</div>
<div class='form-group col-xs-12'>
    <label>Комментарий</label>
    {!! Form::textarea('comment',null,['class'=>'form-control']) !!}
</div>
<div class='col-xs-12 centered'>
    <button type='submit' class='btn btn-primary'>Сохранить/Следующий</button>
</div>
@endif
{!! Form::close() !!}
@stop
@section('scripts')
@stop