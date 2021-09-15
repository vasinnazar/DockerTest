<?php

use Carbon\Carbon; ?>
<div class="loan-step">
    <div class="row">
        <div class="form-group col-xs-12 col-md-6">
            <label class="control-label" for="fio">ФИО<span class="required-mark">*</span></label>
            {!! Form::text('fio',null,array(
            'required'=>'required', 'placeholder'=>'Введите ФИО клиента',
            'pattern'=>'([-A-Za-zА-Яа-яЁё\-]+ ){1}[-A-Za-zА-Яа-яЁё\-]+.*', 'class'=>'form-control'
            ))!!}
            <div class="help-block with-errors"></div>
        </div>
        <div class="form-group col-xs-12 col-md-3">
            <label class="control-label" for="telephone">Телефон<span class="required-mark">*</span></label>
            @if($claimForm->postclient != 1)
            <!--<div class="input-group">-->
                {!! Form::text('telephone',null,array(
                'required'=>'required', 'placeholder'=>'7(XXX)XXX-XXXX', 'class'=>'form-control',
                'maxlength'=>'15'
                ))!!}
<!--                <span class="input-group-btn">
                    <button class="btn btn-default" onclick="$.claimCtrl.checkPhone(this); return false;"><span class='glyphicon glyphicon-refresh'></span></button>
                </span>
            </div>-->
            @else 
            {!! Form::text('telephone',null,array(
                'required'=>'required', 'placeholder'=>'7(XXX)XXX-XXXX', 'class'=>'form-control',
                'maxlength'=>'15'
                ))!!}            
            @endif
            <div class="help-block with-errors"></div>
        </div>
        <div class="form-group col-xs-12 col-md-3">
            <label class="control-label" for="snils">СНИЛС</label>
            {!! Form::text('snils',null,array('placeholder'=>'XXXXXXXXXXX', 'class'=>'form-control','maxlength'=>'11','data-snils'=>'asd'))!!}
            <div class="help-block with-errors"></div>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-xs-12 col-md-4">
            <label class="control-label"  for="birth_date">Дата рождения<span class="required-mark">*</span></label>
            {!! Form::text('birth_date',null,array(
            'required'=>'required', 'placeholder'=>'Введите дату рождения',
            'pattern'=>'(0[1-9]|1[0-9]|2[0-9]|3[01]).(0[1-9]|1[012]).[0-9]{4}', 'class'=>'form-control auto-year',
            'max'=>Carbon::now()->format('Y-m-d')
            ))!!}
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
            {!! Form::text('address_reg_date',null,array(
            'pattern'=>'(0[1-9]|1[0-9]|2[0-9]|3[01]).(0[1-9]|1[012]).[0-9]{4}', 'class'=>'form-control auto-year',
            'max'=>Carbon::now()->format('Y-m-d')
            ))!!}
            <div class="help-block with-errors"></div>
        </div>
    </div>
    <div class="row">

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
            @if(Auth::user()->isCC() || Auth::user()->isAdmin())
            <div class="input-group">
                {!! Form::text('number',null,array(
                'required'=>'required', 'placeholder'=>'XXXXXX',
                'pattern'=>'[0-9]{6}', 'class'=>'form-control',
                'data-minlength'=>'6', 'maxlength'=>'6'
                ))!!}
                <span class="input-group-btn">
                    <button onclick="$.claimCtrl.getCustomerData();
                            return false;" class="btn btn-default">Проверить паспорт</button>
                </span>
            </div>
            @else
            {!! Form::text('number',null,array(
            'required'=>'required', 'placeholder'=>'XXXXXX',
            'pattern'=>'[0-9]{6}', 'class'=>'form-control',
            'data-minlength'=>'6', 'maxlength'=>'6'
            ))!!}
            @endif
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
            {!! Form::text('issued_date',null,array(
            'required'=>'required', 'placeholder'=>'Дата выдачи',
            'pattern'=>'(0[1-9]|1[0-9]|2[0-9]|3[01]).(0[1-9]|1[012]).[0-9]{4}', 'class'=>'form-control auto-year',
            'max'=>Carbon::now()->format('Y-m-d')
            ))!!}
            <div class="help-block with-errors"></div>
        </div>
    </div>
</div>