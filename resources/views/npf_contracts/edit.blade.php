@extends('app')
@section('title') Редактирование договора НПФ @stop
@section('content')
{!! Form::model($item,['action'=>'NpfController@updateItem','id'=>'npfEditForm']) !!}
{!! Form::hidden('id') !!}
{!! Form::hidden('user_id') !!}
{!! Form::hidden('subdivision_id') !!}
{!! Form::hidden('id_1c') !!}
<div class="row">
    @if(Auth::user()->isAdmin())
    <div class='form-group col-xs-12'>
        <p>ID1C: {{$item->id_1c}}</p>
        <p>Дата: {{$item->created_at}}</p>
        <p>Подразделение: {{$item->subdivision->name_id}} - {{$item->subdivision->name}}</p>
        <p>Ответственный: {{$item->user->name}}</p>
    </div>
    @endif
    <div class="form-group col-xs-12 col-md-3">
        {!! Form::hidden('passport_id') !!}
        {!! Form::hidden('passport_series') !!}
        {!! Form::hidden('passport_number') !!}
        <label>Контрагент: </label>
        <div class="input-group">
            <input type="text" name="contragent_fio" class="form-control" value="{{$item->contragent_fio}}" disabled>
            <span class="input-group-btn">
                <a class="btn btn-default" data-toggle="modal" data-target="#findCustomersModal">
                    <span class="glyphicon glyphicon-search"></span> Поиск контрагента
                </a>
            </span>
        </div>
    </div>
    <div class="form-group col-xs-12 col-md-4">
        <label>ФИО при рождении: </label>
        <input type="text" name="old_fio" class="form-control" value="{{$item->old_fio}}"/>
    </div>
    <div class="form-group col-xs-12 col-md-3">
        <label class="control-label" for="snils">СНИЛС<span class="required-mark">*</span></label>
        {!! Form::text('snils',null,['required'=>'required', 'class'=>'form-control','maxlength'=>'11'])!!}
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group col-xs-12 col-md-2">
        <label  class="control-label">Фонд</label>
        {!! Form::select('npf_fond_id',$npf_fonds,null,['class'=>'form-control'])!!}
    </div>
</div>
<button class="btn btn-primary pull-right" type="submit">Сохранить</button>
{!! Form::close() !!}
@include('elements.findCustomerModal')

@stop
@section('scripts')
@if(!Auth::user()->isAdmin())
<script type="text/javascript" src="{{ asset('js/form.js') }}"></script>
@endif
<script src="{{ URL::asset('js/customers/customerController.js') }}"></script>
<script>
(function () {
    $('#findCustomersModal form').submit(function () {
        var targetForm = '#npfEditForm';
        $.app.blockScreen(true);
        $.get($(this).attr('action'), $(this).serialize()).done(function (data) {
            $.app.blockScreen(false);
            var i, str, attrs;
            for (i in data) {
                str = data[i].fio + ' ' + data[i].series + ' ' + data[i].number;
                attrs = 'data-passport-id="' + data[i].passport_id + '" data-series="' + data[i].series + '" ' + 'data-number="' + data[i].number + '" ' + 'data-fio="' + data[i].fio + '" ' + 'data-snils="' + data[i].snils + '"';
                $('#findCustomersResult').empty();
                $('<a href="#" class="list-group-item" ' + attrs + '>' + str + '</a>').appendTo('#findCustomersResult').click(function () {
                    $(targetForm + ' [name="passport_id"]').val($(this).attr('data-passport-id'));
                    $(targetForm + ' [name="passport_series"]').val($(this).attr('data-series'));
                    $(targetForm + ' [name="passport_number"]').val($(this).attr('data-number'));
                    $(targetForm + ' [name="contragent_fio"]').val($(this).attr('data-fio'));
                    $(targetForm + ' [name="snils"]').val($(this).attr('data-snils'));
                    $('#findCustomersModal').attr('data-target-form', '');
                    $('#findCustomersModal').modal('hide');
                    return false;
                });
            }
        });
        return false;
    });
})(jQuery);
</script>
@stop