@extends('app')
@section('title') Помощь @stop
@section('content')
<div class="row">
    <div class="col-sm-3 col-md-2 sidebar">
        <ul class="nav nav-pills nav-stacked">
            <li{!! (Request::path()=='help/cert') ? ' class=active' : null !!}>
                <a href="{{ url('/help/cert') }}">Сертификаты</a>
            </li>
            <li{!! (Request::path()=='help/docs') ? ' class=active' : null !!}>
                <a href="{{ url('/help/docs') }}">Документы</a>
            </li>
            <li>
                <a target="_blank" href="https://docs.google.com/spreadsheets/d/10gsqbH-LqRf9nsp8jp5uMxhu-zAPizW8zXR_OT7xk4U/edit">Скидка по карте</a>
            </li>
            <li>
                <a target="_blank" href="https://docs.google.com/spreadsheets/d/1lhUejAiHKOCjD6RST4Dy0QdWsXuBJZ5WXTIO7JFewxA/edit">Список ТП (телефоны)</a>
            </li>        
            <li>
                <a href="{{ asset('/files/teamviewer.exe') }}">Скачать TeamViewer</a>
            </li>
            <li>
                <a href="{{ asset('/files/fin_install.exe') }}" target="_blank">Скачать Установщик</a>
            </li>
            <li>
                <a href="{{ asset('/files/fin_yandex.exe') }}" target="_blank">Скачать Установщик (Яндекс)</a>
            </li>
            <li>
                <a href="{{ asset('/files/fin_yandex_vpn.exe') }}" target="_blank">Скачать Установщик (Яндекс VPN)</a>
            </li>
            <li>
                <a href="{{ asset('/files/finterra_install_vpn.exe') }}" target="_blank">Скачать Установщик с VPN</a>
            </li>
            <li>
                <a href="{{ asset('/files/arm_questions.pdf') }}" target="_blank">Вопросы по аттестации в ПО АРМ</a>
            </li>
            <li class='{{ (str_contains(Request::path(), 'help/videos/')) ? 'active' : '' }}'>
                <a href="{{ url('/help/videos') }}">Видео инструкции</a>
            </li>
            <li{!! (Request::path()=='help/instructions') ? ' class=active' : null !!}>
                <a href="{{ url('/help/instructions') }}">Инструкции</a>
            </li>
        </ul>
    </div>
    <div class="col-sm-9 col-md-10 main">
        @yield('subcontent')
    </div>
</div>
@stop