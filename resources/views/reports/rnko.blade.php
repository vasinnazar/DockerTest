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
<!--<h1>Осталось: {{$rnko_left}}</h1>-->
<a href="{{ asset('/files/rnko.pdf') }}" target="_blank">Открыть руководство по РНКО</a><br><br>
@if(isset($rnko) && !is_null($rnko))
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
            Паспорт: {{$rnko->passport_series}} {{$rnko->passport_number}}
        </div>
        <div class='form-group'>
            <label>Комментарий</label>
            {!! Form::text('comment',null,['class'=>'form-control']) !!}
        </div>
        <div class='form-group'>
            <label>Статус</label>
            {!! Form::select('status',\App\Rnko::getStatusList(),null,['class'=>'form-control']) !!}
        </div>
        <button class='btn btn-primary btn-lg' type='submit'>Сохранить/Следующий</button>
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
<h1 class='alert alert-warning'>Для продолжения попробуйте обновить страницу позже.</h1>
@if(isset($details) && !is_null($details))
@if(in_array(Auth::user()->id,[1,5,189]))
<div>
    <h4>Отчет по дням</h4>
    <form method='GET' action='{{url('reports/rnko/bydates')}}' class='form-inline'>
        <label>Дата начала</label>
        <input name='date_start' type='date' class='form-control'/>
        <select name="dept" class="form-control">
            <option value="ovk">ОВК</option>
            @if(Auth::user()->id != 189)
            <option value="sales">Продажи</option>
            <option value="cc">КЦ</option>
            <option value="seb">Проверка</option>
            <option value="it">__</option>
            @endif
        </select>
        <button type='submit' class='btn btn-default'>Открыть</button>
        <br>
        <br>
    </form>
</div>
@endif
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Статистика</h3>
    </div>
    <div class="panel-body">
        <p><span>Обработано всего:</span><span class='badge'>{{$details['total']}} из {{$details['count']}}</span></p>
        <p><span>С фотками, на очереди:</span><span class='badge'>{{$details['uploaded']}}</span></p>
        <br>
        <p><span>Сверено:</span><span class='badge'>{{$details['checked']}}</span></p>
        <p><span>Отредактировано:</span><span class='badge'>{{$details['edited']}}</span></p>
        <p><span>Нет фотографий:</span><span class='badge'>{{$details['nophoto']}}</span></p>
        @if(in_array(Auth::user()->id,[1,5]))
        <div>
            <p>По подразделениям: ОВК - <span class='badge'>{{$details['depstat']['ovk']}}</span>; Продажи - <span class='badge'>{{$details['depstat']['sales']}}</span></p>
        </div>
        @endif
        @if(isset($details['stat']) && !is_null($details['stat']))
        <div>
            <p>По проверяющим:</p>
            <table class='table table-bordered table-condensed table-striped'>
                <thead>
                    <tr>
                        <th>ФИО</th>
                        <th>Кол-во проверенных всего</th>
                        <th>Кол-во проверенных за сегодня</th>
                        <th>Кол-во проверенных за вчера</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($details['stat'] as $s)
                    <tr>
                        <td>{{$s['fio']}}</td>
                        <td>{{$s['all']}}</td>
                        <td>{{$s['today']}}</td>
                        <td>{{$s['yesterday']}}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        <!--<p><span>Письмо не найдено:</span><span class='badge'>{{$details['autonophoto']}}</span></p>-->

    </div>
</div>
@endif
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