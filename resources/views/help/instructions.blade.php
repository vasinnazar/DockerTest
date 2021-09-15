@extends('help.helpmenu')
@section('title') Инструкции @stop
@section('subcontent')
<h2>Инструкции</h2>
<hr>
<div class='row'>
    <div class='col-xs-12 col-lg-6'>
        <div class='list-group'>
            <a class='list-group-item' href="{{ asset('/files/rnko.pdf') }}" target="_blank">Открыть руководство по РНКО</a>
            <a class='list-group-item' href="{{ asset('/files/webcam.pdf') }}" target="_blank">Открыть рук-во по фото с веб камеры</a>
            <a class='list-group-item' href="{{ url('/help/page/issue') }}">Инструкция по выдаче в подотчет</a>
            <a class='list-group-item' href="{{ url('/help/addresses') }}">Инструкция по заполнению адресов в заявке</a>
            <a class='list-group-item' href="{{ asset('/files/hlr.pdf') }}" target="_blank">Инструкция по проверке телефона для новых клиентов</a>
            <a class='list-group-item' href="{{ asset('/files/dopcommission.pdf') }}" target="_blank">Открыть рук-во по заявлению о продлении срока действия договора</a>
            <a class='list-group-item' href="{{ asset('/files/sfp_operations.pdf') }}" target="_blank">Открыть рук-во по операциям с картами СФП</a>
            <a class='list-group-item' href="{{ asset('/files/advance_reports.pdf') }}" target="_blank">Создание авансовых отчетов</a>
            <a class='list-group-item' href="{{ asset('/files/userphotos.pdf') }}" target="_blank">Загрузка фото специалистов</a>
        </div>
    </div>
</div>
@stop