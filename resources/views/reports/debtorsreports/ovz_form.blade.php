@extends('app')
@section('title') ОВЗ @stop
@section('content')
<h2>ОВЗ</h2>
{!! Form::open(['url'=>url('debtors/reports/ovz'),'method'=>'get','class'=>'form-inline']) !!}
<label>Дата начала</label>
<input name='start_date' type="date" class='form-control' />
<label>Дата завершения</label>
<input name='end_date' type="date" class='form-control' />
<button type="submit" class='btn btn-primary'>Сформировать</button>
{!! Form::close() !!}
@stop