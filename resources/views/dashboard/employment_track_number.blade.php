@extends('app')
@section('title') Ввод трек-номера @stop
@section('content')
<div class='container'>
    <div class='row'>
        <div class='col-xs-12 col-lg-6 col-lg-offset-3'>
            {!! Form::open(['url'=>url('employment/tracknumber/update')])!!}
            <label>Трек-номер почты России</label>
            {!! Form::text('employment_docs_track_number',null,['class'=>'form-control','maxlength'=>14]) !!}
            <br>
            <button class='btn btn-primary pull-right'>Сохранить</button>
            {!! Form::close() !!}
        </div>
    </div>
</div>
@stop