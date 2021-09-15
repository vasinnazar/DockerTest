@extends('reports.reports')
@section('title') Сверка РНКО @stop
@section('css')
@parent
<link href="{{asset('/js/libs/viewer/viewer.min.css')}}" media="all" rel="stylesheet" type="text/css" />
<style>
    .viewer-holder{
        min-height: 300px;
    }
    .viewer-play{
        display: none;
    }
    #images li{
        list-style: none;
        /*display: inline-block;*/
        cursor: pointer;
    }
    #images li img{
        max-height: 200px;
        max-width: 200px;
    }
</style>
@stop
@section('subcontent')
<a href="{{ asset('/files/rnko.pdf') }}" target="_blank">Открыть руководство по РНКО</a><br><br>
<!--<h1 style='color:red'>ВСЕ КАРТЫ НЕОБХОДИМО РЕДАКТИРОВАТЬ</h1>-->
{!!Form::open(['url'=>'reports/rnko/number','class'=>'form-inline','id'=>'rnkoSearchForm','method'=>'GET'])!!}
{!!Form::label('Номер карты')!!}
{!!Form::text('card_number',null,['class'=>'form-control'])!!}
<button type='submit' class='btn btn-default'>Найти</button>
<div class="btn-group pull-right">
    <a href='{{url('reports/rnko/skip')}}' class='btn btn-danger'>Пропустить следующую карту (если не открывается)</a>
    <a href='{{url('reports/rnko/check/get')}}' class='btn btn-success'>Получить следующую карту</a>
</div>
{!!Form::close()!!}
<br>
@if(isset($rnko) && !is_null($rnko))
<div class="row">
    <div class="col-xs-12">
        <div class="alert alert-warning">
            Перед сверкой убедитесь, что фотография соответствует данным из РНКО!!! Не забудьте проверить совпадения по номеру телефона.
        </div>
    </div>
</div>
<div class='row'>
    <div class='col-xs-12 col-md-8'>
        @if(count($photos)>0)
        <div class='viewer-holder'>
            <ul id="images">
                @foreach($photos as $p)
                <li><img src="{{$p}}" alt="Picture" width="0"></li>
                @endforeach
            </ul>
        </div>
        @else
        Фотографии не найдены! Попробуйте найти папку с фотографиями, скопировав ссылку ниже в адресную строку проводника. ТОЛЬКО ДЛЯ ОФИСА!<br>
        Ссылка: \\192.168.1.123\images\{{$rnko->passport_series}}{{$rnko->passport_number}}<br>
        <img width="400px" src="{{asset('images/help/rnko_help.png')}}"/>
        
        @endif
        @if($photos_warning != '')
        <div class='alert alert-warning'>{{$photos_warning}}</div>
        @endif
    </div>
    <div class='col-xs-12 col-md-4'>
        {!! Form::model($rnko,['url'=>'reports/rnko/update','class'=>'form','id'=>'rnkoForm']) !!}
        <div class='form-group'>
            {!! Form::hidden('id') !!}
            Номер карты: {{$rnko->card_number}}
        </div>
        <div class='form-group'>
            ФИО: {{$rnko->fio}}
        </div>
        <div class='form-group'>
            Телефон: {{$telephone}}
        </div>
        <div class='form-group'>
            Паспорт: {{$rnko->passport_series}} {{$rnko->passport_number}} <br>
        </div>
        <div class='form-group'>
            <label>Комментарий</label>
            {!! Form::text('comment',null,['class'=>'form-control']) !!}
        </div>
        @if(Auth::user()->subdivision_id==113)
        <div class='form-group'>
            <label>Статус проверки</label>
            {!! Form::select('check_status',\App\Rnko::getStatusList(),null,['class'=>'form-control']) !!}
        </div>
        <div class='form-group'>
            <label>Статус</label>
            {!! Form::select('status',\App\Rnko::getStatusList(),null,['class'=>'form-control','disabled'=>'disabled']) !!}
        </div>
        @else
        <div class='form-group'>
            <label>Статус</label>
            {!! Form::select('status',\App\Rnko::getStatusList(),null,['class'=>'form-control']) !!}
        </div>
        @endif
        <button class='btn btn-primary btn-lg' type='submit'>Сохранить</button>
        <input type='hidden' value="1" name='by_number'/>
        {!! Form::close()!!}
    </div>
</div>
<div class='row'>
    <div class='col-xs-12'>
        <br>
        <iframe src="https://perevod-korona.com/bank2" width="100%" style="min-height:520px"></iframe>
    </div>
</div>
@else
<h1 class='alert alert-warning'>Введите номер карты.</h1>
@endif
@stop
@section('scripts')
<script src="{{asset('js/libs/viewer/viewer.min.js')}}" type="text/javascript"></script>
<script>
$(document).ready(function () {
    $('#images').viewer({inline: true, fullscreen: false, navbar: true, minHeight: 290, zoomRatio: 0.5, title: false});
    $('#openKoronaBtn').click(function () {
        var url = 'https://perevod-korona.com/bank2';
        $('#cardFrameModal').modal();
        $('#cardFrameModal .modal-body').html('<iframe src="' + url + '" width="100%" style="min-height:520px"></iframe>');
    });
    $('#rnkoForm').submit(function () {
        $.app.blockScreen(true);
    });
});
</script>
@stop