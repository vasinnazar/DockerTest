@extends('app')
@section('title') Календарный план @stop
@section('content')
<h2>Календарный план</h2>
{!! Form::open(['url'=>url('debtors/reports/plancalend'),'method'=>'get','class'=>'form-inline']) !!}
<label>Дата начала</label>
<input name='start_date' type="date" class='form-control' />
<label>Дата завершения</label>
<input name='end_date' type="date" class='form-control' />
<label>В Excel <input name="to_excel" type="checkbox"/></label>
<button type="submit" class='btn btn-primary'>Сформировать</button>
{!! Form::close() !!}
@stop