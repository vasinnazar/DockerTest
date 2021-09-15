@extends('app')
@section('title') Акт выполненных работ @stop
@section('content')
<h2>Акт выполненных работ</h2>
{!! Form::open(['url'=>url('debtors/reports/jobsdoneact'),'method'=>'get','class'=>'form-inline']) !!}
<label>Специалист</label>
<input name='user_id' type='hidden' />
<input name='user_id_autocomplete' data-autocomplete='users' class='form-control'/>
<label>Дата начала</label>
<input name='start_date' type="date" class='form-control' />
<label>Дата завершения</label>
<input name='end_date' type="date" class='form-control' />
<button type="submit" class='btn btn-primary'>Сформировать</button>
{!! Form::close() !!}
@stop