@extends('adminpanel')
@section('title') Редактор условия @stop

@section('subcontent')
{!! Form::model($condition,['route'=>'conditions.update'])!!}
{!! Form::hidden('id') !!}
<div class="row">
    <div class="form-group col-xs-12">
        <label>Наименование</label>
        {!! Form::text('name',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12">
        <label>Условие</label>
        {!! Form::text('condition',null,['class'=>'form-control']) !!}
    </div>
</div>
{!! Form::button('Сохранить',['class'=>'btn btn-primary pull-right','type'=>'submit']) !!}
{!! Form::close() !!}
@stop