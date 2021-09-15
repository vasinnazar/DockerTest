@extends('help.helpmenu')
@section('title')Сертификаты@stop
@section('subcontent')
<style>
    .help-steps li{
        margin: 10px 0;
    }
    .help-steps img{
        max-height: 300px;
        max-width: 300px;
    }
</style>
<h1>Сертификаты</h1>
Для того чтобы выдавать займы на карты необходимо установить сертификаты. <br>Скачайте сертификаты по ссылке:
<h2><a class="btn btn-success btn-lg" href="{{asset('files/Setup.exe')}}" download>Скачать сертификаты</a></h2>
<br>
<br>
<hr>
<ol class="help-steps">
    <li><img src="{{asset('images/help/save1.png')}}" alt="Этап 1"/></li>
    <li><img src="{{asset('images/help/save2.png')}}" alt="Этап 2"/></li>
    <li><img src="{{asset('images/help/save3.png')}}" alt="Этап 3"/></li>
    <li><img src="{{asset('images/help/save4.png')}}" alt="Этап 4"/></li>
    <li><img src="{{asset('images/help/save5.png')}}" alt="Этап 5"/></li>
    <li><img src="{{asset('images/help/save6.png')}}" alt="Этап 6"/></li>
</ol>
@stop