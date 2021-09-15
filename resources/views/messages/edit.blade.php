@extends('adminpanel')
@section('title') Редактирование сообщения @stop
@section('subcontent')
<div class="row">
    <div class="form-group col-xs-12 col-md-8 col-md-offset-2">
        {!! Form::model($item,['action'=>'MessagesController@updateItem','id'=>'messageEditForm']) !!}
        {!! Form::hidden('id') !!}
        {!! Form::hidden('user_id') !!}
        <div class="form-group">
            <label>Сообщение: </label>
            {!! Form::textarea('text',null,['class'=>'form-control']) !!}
            <label>Подпись: </label>
            {!! Form::text('caption',null,['class'=>'form-control']) !!}
            <label>Цвет: </label>
            {!! Form::select('type',[
            'danger'=>'Красный',
            'success'=>'Зеленый',
            'warning'=>'Желтый',
            'primary'=>'Синий',
            'info'=>'Голубой',
            ],null,['class'=>'form-control']) !!}
        </div>
        <button class="btn btn-primary centered" type="submit">Сохранить</button>
        {!! Form::close() !!}
    </div>
</div>
@stop