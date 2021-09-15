@extends('adminpanel')
@section('title') Загрузка почты @stop
@section('subcontent')
{!! Form::open(['url'=>'adminpanel/mailloader/load']) !!}
<label>Дата</label>
<input name='date' class='form-control'/>
<label>ФИО</label>
<input name='fio' class='form-control'/>
<label>Серия</label>
<input name='passport_series' class='form-control'/>
<label>Номер</label>
<input name='passport_number' class='form-control'/>
<label>Количество писем</label>
{!! Form::text('num',null,['class'=>'form-control']) !!}
<label>Начать с первого письма</label>
<input name='from_start' type="checkbox"/>
<br>
<button type='submit' class='btn btn-default'>Загрузить письма</button>
{!! Form::close() !!}
<a href='{{url('adminpanel/mailloader/xml')}}' class='btn btn-default'>Получить ХМЛ</a>
<a href='{{url('adminpanel/mailloader/loadxml')}}' class='btn btn-default'>Загрузить ХМЛ</a>
@stop