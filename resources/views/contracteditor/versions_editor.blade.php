@extends('adminpanel')
@section('title') Версии документа: {{$contract->name}} @stop
@section('subcontent')
{!! Form::open(['url'=>'contracts/versions/add','method'=>'post']) !!}
{!! Form::hidden('contract_form_id',$contract->id) !!}
{!! Form::button('<span class="glyphicon glyphicon-plus"></span> Создать',['class'=>'btn btn-default','type'=>'submit'])!!}
{!! Form::close() !!}
<br>
<h1>Версии документа: {{$contract->name}}</h1>
@foreach($versions as $v)
{!! Form::open(['url'=>'contracts/versions/update','method'=>'post','class'=>'form-inline']) !!}
{!! Form::hidden('id',$v->id) !!}
<div class='form-group form-inline'>
    <label>Дата</label>
    {!! Form::text('date',$v->date->format('Y-m-d H:i:s'),['class'=>'form-control']) !!}
</div>
<div class='form-group form-inline'>
    <label>Версия договора</label>
    {!! Form::select('new_contract_form_id',$contractslist,$v->new_contract_form_id,['class'=>'form-control']) !!}
</div>
<div class='form-group'>
    <button class='btn btn-success' type='submit'>Обновить</button>
</div>
<div class='form-group'>
    <a class='btn btn-danger' href='{{url('contracts/versions/remove?id='.$v->id)}}'>Удалить</a>
</div>
{!! Form::close() !!}
<hr>
@endforeach
@stop