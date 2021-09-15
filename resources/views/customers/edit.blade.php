@extends('app')
@section('title') Редактирование физ.лица @stop
@section('content')
<div class="alert alert-info">
    Здесь Вы можете отредактировать данные клиента.
</div>
{!! Form::model($customerForm,['action'=>'CustomersController@update','id'=>'customerEditForm']) !!}
{!! Form::hidden('customer_id') !!}
{!! Form::hidden('passport_id') !!}
{!! Form::hidden('user_id') !!}
<?php use Carbon\Carbon; ?>
<div class="row">
    <div class="form-group col-xs-12 col-md-7">
        <label class="control-label" for="fio">ФИО<span class="required-mark">*</span></label>
        {!! Form::text('fio',null,array(
        'required'=>'required', 'placeholder'=>'Введите ФИО клиента',
        'pattern'=>'([A-Za-zА-Яа-яЁё\-]+ ){2}[A-Za-zА-Яа-яЁё\-]+.*', 'class'=>'form-control'
        ))!!}
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group col-xs-12 col-md-4">
        <label class="control-label" for="telephone">Телефон<span class="required-mark">*</span></label>
        {!! Form::text('telephone',null,array(
        'required'=>'required', 'placeholder'=>'7XXXXXXXXXX',
        'pattern'=>'7[0-9\*]{10}', 'class'=>'form-control',
        'maxlength'=>'15'
        ))!!}
        <div class="help-block with-errors"></div>
    </div>
    @if(Auth::user()->isAdmin())
    <div class="form-group col-xs-12 col-md-1">
        <label class="control-label" for="id_1c">ID 1C</label>
        {!! Form::text('id_1c',null,['class'=>'form-control'])!!}
        <div class="help-block with-errors"></div>
    </div>
    @endif
</div>
<div class="row">
    <div class="form-group col-xs-12 col-md-4">
        <label class="control-label"  for="birth_date">Дата рождения<span class="required-mark">*</span></label>
        <input type="date" required="required" name="birth_date" value="{{$customerForm->birth_date}}" 
               pattern="(0[1-9]|1[0-9]|2[0-9]|3[01]).(0[1-9]|1[012]).[0-9]{4}" class="form-control" max="{{Carbon::now()->format('Y-m-d')}}" />
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group col-xs-12 col-md-4">
        <label class="control-label"  for="birth_city">Место рождения<span class="required-mark">*</span></label>
        {!! Form::text('birth_city',null,array(
        'required'=>'required', 'placeholder'=>'Введите место рождения', 
        'class'=>'form-control'
        ))!!}
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group col-xs-12 col-md-4">
        <label class="control-label"  for="address_reg_date">Дата регистрации<span class="required-mark">*</span></label>
        <input type="date" required="required" name="address_reg_date" value="{{$customerForm->address_reg_date}}" 
               pattern="(0[1-9]|1[0-9]|2[0-9]|3[01]).(0[1-9]|1[012]).[0-9]{4}" class="form-control" max="{{Carbon::now()->format('Y-m-d')}}" />
        <div class="help-block with-errors"></div>
    </div>
</div>
<div class="row">
    <div class="form-group col-xs-12 col-md-2">
        <label  class="control-label" for="seria">Серия<span class="required-mark">*</span></label>
        {!! Form::text('series',null,array(
        'required'=>'required', 'placeholder'=>'XXXX',
        'pattern'=>'[0-9]{4}', 'class'=>'form-control',
        'data-minlength'=>'4','maxlength'=>'4'
        ))!!}
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group col-xs-12 col-md-2">
        <label class="control-label"  for="Nomer">Номер<span class="required-mark">*</span></label>
        {!! Form::text('number',null,array(
        'required'=>'required', 'placeholder'=>'XXXXXX',
        'pattern'=>'[0-9]{6}', 'class'=>'form-control',
        'data-minlength'=>'6', 'maxlength'=>'6'
        ))!!}
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group col-xs-14 col-md-4">
        <label class="control-label"  for="issued">Паспорт выдан<span class="required-mark">*</span></label>
        {!! Form::text('issued',null,array(
        'required'=>'required', 'placeholder'=>'Кем выдан',
        'class'=>'form-control'
        ))!!}
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group col-xs-12 col-md-2">
        <label class="control-label"  for="subdivision_code">Код подразделения<span class="required-mark">*</span></label>
        {!! Form::text('subdivision_code',null,array(
        'required'=>'required', 'placeholder'=>'XXX-XXX',
        'pattern'=>'[0-9]{3}-[0-9]{3}', 'class'=>'form-control'
        ))!!}
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group col-xs-12 col-md-2">
        <label class="control-label"  for="issued_date">Дата выдачи<span class="required-mark">*</span></label>
        <input type="date" required="required" name="issued_date" value="{{$customerForm->issued_date}}" 
               pattern="(0[1-9]|1[0-9]|2[0-9]|3[01]).(0[1-9]|1[012]).[0-9]{4}" class="form-control" max="{{Carbon::now()->format('Y-m-d')}}" />
        <div class="help-block with-errors"></div>
    </div>
</div>
<div class="row">
    <h4 class="col-xs-12">Юридический адрес проживания</h4>
</div>
<div id="registrationResidence">
    <div class="row">
        <div class="form-group col-xs-12 col-md-2">
            <label>Индекс</label>
            {!! Form::text('zip',null,array(
            'placeholder'=>'XXXXXX',
            'pattern'=>'[0-9]{6}', 'class'=>'form-control input-sm'
            ))!!}
        </div>
        <div class="form-group col-xs-12 col-md-5">
            <label class="control-label">Область</label>
            {!! Form::text('address_region',null,array(
            'class'=>'form-control input-sm'
            ))!!}
        </div>
        <div class="form-group col-xs-12 col-md-5">
            <label class="control-label">Район</label>
            {!! Form::text('address_district',null,array(
            'class'=>'form-control input-sm',
            'title'=>'Не заполнять если город - районный центр'
            ))!!}
        </div>
    </div>
    <div class="row">
        <div class="form-group col-xs-12 col-md-3">
            <label class="control-label" >Город</label>
            {!! Form::text('address_city',null,array(
            'class'=>'form-control input-sm'
            ))!!}
        </div>
        <div class="form-group col-xs-12 col-md-3">
            <label class="control-label" >Нас. пункт</label>
            {!! Form::text('address_city1',null,array(
            'class'=>'form-control input-sm'
            ))!!}
        </div>
        <div class="form-group col-xs-12 col-md-3">
            <label class="control-label" >Улица</label>
            {!! Form::text('address_street',null,array(
            'class'=>'form-control input-sm'))!!}
        </div>
        <div class="form-group col-xs-12 col-md-1">
            <label class="control-label" for="address_house">Дом<span class="required-mark">*</span></label>
            {!! Form::text('address_house',null,array(
            'class'=>'form-control input-sm',
            'required'=>'required'
            ))!!}
        </div>
        <div class="form-group col-xs-12 col-md-1">
            <label class="control-label">Строение</label>
            {!! Form::text('address_building',null,array(
            'class'=>'form-control input-sm',
            ))!!}
        </div>
        <div class="form-group col-xs-12 col-md-1">
            <label class="control-label" >Квартира</label>
            {!! Form::text('address_apartment',null,[
            'class'=>'form-control input-sm'
            ])!!}
        </div>
    </div>
</div>
<div id="factResidence">
    <div class="row">
        <h4 class=" col-xs-12 col-md-3">Фактический адрес проживания</h4>
        <div class="form-group col-xs-3">
            <input type="checkbox" class="checkbox"  name="copyFactAddress" id="checkbox"  value="1"> 
            <label for="checkbox">Совпадает</label>
        </div>
    </div>        
    <div class="row">
        <div class="form-group col-xs-12 col-md-2">
            <label class="control-label">Индекс</label>
            {!! Form::text('fact_zip',null,array(
            'placeholder'=>'XXXXXX',
            'pattern'=>'[0-9]{6}', 'class'=>'form-control input-sm'
            ))!!}
        </div>
        <div class="form-group col-xs-12 col-md-5">
            <label class="control-label">Область</label>
            {!! Form::text('fact_address_region',null,array(
            'class'=>'form-control input-sm'
            ))!!}
        </div>
        <div class="form-group col-xs-12 col-md-5">
            <label class="control-label">Район</label>
            {!! Form::text('fact_address_district',null,array(
            'class'=>'form-control input-sm',
            'title'=>'Не заполнять если город - районный центр'
            ))!!}
        </div>
    </div>
    <div class="row">
        <div class="form-group col-xs-12 col-md-3">
            <label class="control-label" >Город</label>
            {!! Form::text('fact_address_city',null,array(
            'class'=>'form-control input-sm'
            ))!!}
        </div>
        <div class="form-group col-xs-12 col-md-3">
            <label class="control-label" >Нас. пункт</label>
            {!! Form::text('fact_address_city1',null,array(
            'class'=>'form-control input-sm'
            ))!!}
        </div>
        <div class="form-group col-xs-12 col-md-3">
            <label class="control-label" >Улица</label>
            {!! Form::text('fact_address_street',null,array(
            'class'=>'form-control input-sm'))!!}
        </div>
        <div class="form-group col-xs-12 col-md-1">
            <label class="control-label">Дом<span class="required-mark">*</span></label>
            {!! Form::text('fact_address_house',null,array(
            'class'=>'form-control input-sm',
            'required'=>'required'
            ))!!}
        </div>
        <div class="form-group col-xs-12 col-md-1">
            <label class="control-label">Строение</label>
            {!! Form::text('fact_address_building',null,array(
            'class'=>'form-control input-sm'
            ))!!}
        </div>
        <div class="form-group col-xs-12 col-md-1">
            <label class="control-label">Квартира</label>
            {!! Form::text('fact_address_apartment',null,array(
            'class'=>'form-control input-sm'
            ))!!}
        </div>
    </div>
</div>
<br>
<button class="btn btn-primary pull-right" type="submit">Сохранить</button>
{!! Form::close() !!}

@stop
@section('scripts')
<script type="text/javascript" src="{{ asset('js/form.js?2') }}"></script>
<script src="{{ URL::asset('js/customers/customerController.js') }}"></script>
<script>
(function () {
    $.custCtrl.init();
})(jQuery);
</script>
@stop