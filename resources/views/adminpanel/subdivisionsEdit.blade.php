@extends('adminpanel')
@section('title') Подразделение @stop
@section('subcontent')
{!! Form::model($subdivision,['action'=>'SubdivisionController@updateSubdivision','id'=>'subdivisionForm']) !!}
{!! Form::hidden('id') !!}
<div class="form-group">
    <label class="control-label">Код подразделения</label>
    {!! Form::text('name_id',null,['class'=>'form-control']) !!}
</div>
<div class="form-group">
    <label class="control-label">Адрес</label>
    {!! Form::text('address',null,['class'=>'form-control']) !!}
</div>
<div class="form-group">
    <label class="control-label">Город</label>
    {!! Form::select('city_id', \App\City::lists('name','id')->all(),null,['class'=>'form-control']) !!}
</div>
<div class="form-group">
    <label class="control-label">Город (вписать здесь если нет в списке)</label>
    {!! Form::text('city',null,['class'=>'form-control']) !!}
</div>
<div class="form-group">
    <label class="control-label">Название</label>
    {!! Form::text('name',null,['class'=>'form-control']) !!}
</div>
<div class="form-group">
    <label class="control-label">Мировой судья</label>
    {!! Form::text('peacejudge',null,['class'=>'form-control']) !!}
</div>
<div class="form-group">
    <label class="control-label">Районный суд</label>
    {!! Form::text('districtcourt',null,['class'=>'form-control']) !!}
</div>
<div class="form-group">
    <label class="control-label">Руководитель</label>
    {!! Form::text('director',null,['class'=>'form-control']) !!}
</div>
<div class="form-group">
    <label class="control-label">Терминал (0|1)</label>
    {!! Form::text('is_terminal',null,['class'=>'form-control']) !!}
</div>
<div class="form-group">
    <label class="control-label">Разрешить использовать новые карты (0-нет|1-да)</label>
    {!! Form::text('allow_use_new_cards',null,['class'=>'form-control']) !!}
</div>
<button class="btn btn-primary pull-right" type="submit">Сохранить</button>
{!! Form::close() !!}
<br>
<hr>
<ul class='list-group'>
Специалисты на подразделении:
@foreach($subdivision->users as $user)
<li class='list-group-item'>
    {{$user->name}}
</li>
@endforeach
</ul>
@stop