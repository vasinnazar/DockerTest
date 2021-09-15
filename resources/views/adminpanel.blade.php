@extends('app')
@section('title') Администрирование @stop
@section('css')@stop
@section('content')
<div class="row">
    <div class="col-xs-4 col-sm-3 col-md-2 sidebar">
        <ul class="nav nav-pills nav-stacked">
            <li{!! (Request::path()=='usersreqs/remove') ? ' class=active' : null !!}>
                <a href="{{ url('/usersreqs/remove') }}">Запросы на удаление</a>
            </li>
            <li{!! (Request::path()=='contracts/list') ? ' class=active' : null !!}>
                <a href="{{ url('/contracts/list') }}">Формы договоров</a>
            </li>
            @if(str_contains(Request::path(), 'contracts/create'))
            <li class='active sub-item'><a>Создание новой формы</a></li>
            @endif
            @if(str_contains(Request::path(), 'contracts/edit'))
            <li class='active sub-item'><a>Редактирование формы</a></li>
            @endif
            <li{!! (Request::path()=='loantypes/list') ? ' class=active' : null !!}>
                <a href="{{ url('/loantypes/list') }}">Виды займов</a>
            </li>
            @if(str_contains(Request::path(), 'loantypes/create'))
            <li class='active sub-item'><a>Создание вида займа</a></li>
            @endif
            @if(str_contains(Request::path(), 'loantypes/edit'))
            <li class='active sub-item'><a>Редактирование вида займа</a></li>
            @endif
            @if(str_contains(Request::path(), 'conditions/create'))
            <li class='active sub-item'><a>Создание условия</a></li>
            @endif
            @if(str_contains(Request::path(), 'conditions/edit'))
            <li class='active sub-item'><a>Редактирование условия</a></li>
            @endif
            <li{!! (Request::path()=='spylog/list') ? ' class=active' : null !!}>
                <a href="{{ url('/spylog/list') }}">Логи</a>
            </li>
            <li{!! (Request::path()=='adminpanel/users') ? ' class=active' : null !!}>
                <a href="{{ url('/adminpanel/users') }}">Специалисты</a>
            </li>
            <li{!! (Request::path()=='adminpanel/subdivisions') ? ' class=active' : null !!}>
                <a href="{{ url('/adminpanel/subdivisions') }}">Подразделения</a>
            </li>
            @if(str_contains(Request::path(), 'subdivisions/create'))
            <li class='active sub-item'><a>Создание подразделения</a></li>
            @endif
            @if(str_contains(Request::path(), 'subdivisions/edit'))
            <li class='active sub-item'><a>Редактирование подразделения</a></li>
            @endif
            @if(str_contains(Request::path(), 'adminpanel/cashbook'))
            <li class='active sub-item'><a>Кассовая книга</a></li>
            @endif
            @if(str_contains(Request::path(), 'adminpanel/orders'))
            <li class='active sub-item'><a>Ордеры</a></li>
            @endif
            <li{!! (Request::path()=='adminpanel/repaymenttypes') ? ' class=active' : null !!}>
                <a href="{{ url('/adminpanel/repaymenttypes') }}">Гашение</a>
            </li>
            @if(str_contains(Request::path(), 'repaymenttypes/create'))
            <li class='active sub-item'><a>Создание вида гашения</a></li>
            @endif
            @if(str_contains(Request::path(), 'repaymenttypes/edit'))
            <li class='active sub-item'><a>Редактирование вида гашения</a></li>
            @endif
            <li{!! (Request::path()=='adminpanel/terminals') ? ' class=active' : null !!}>
                <a href="{{ url('/adminpanel/terminals') }}">Терминалы</a>
            </li>
            @if(str_contains(Request::path(), 'adminpanel/terminals/create'))
            <li class='active sub-item'><a>Добавление нового терминала</a></li>
            @endif
            @if(str_contains(Request::path(), 'adminpanel/terminals/edit'))
            <li class='active sub-item'><a>Редактирование терминала</a></li>
            @endif
            <li{!! (Request::path()=='adminpanel/npffonds') ? ' class=active' : null !!}>
                <a href="{{ url('/adminpanel/npffonds') }}">НПФ</a>
            </li>
            @if(str_contains(Request::path(), 'adminpanel/npffonds/create'))
            <li class='active sub-item'><a>Создание фонда</a></li>
            @endif
            @if(str_contains(Request::path(), 'adminpanel/npffonds/edit'))
            <li class='active sub-item'><a>Редактирование фонда</a></li>
            @endif
            <li{!! (Request::path()=='messages') ? ' class=active' : null !!}>
                <a href="{{ url('messages') }}">Сообщения</a>
            </li>
            @if(str_contains(Request::path(), 'messages/create'))
            <li class='active sub-item'><a>Новое сообщение</a></li>
            @endif
            @if(str_contains(Request::path(), 'messages/edit'))
            <li class='active sub-item'><a>Редактирование сообщения</a></li>
            @endif
            <li{!! (Request::path()=='adminpanel/problemsolver') ? ' class=active' : null !!}>
                <a href="{{ url('/adminpanel/problemsolver') }}">Исправление проблем</a>
            </li>
            <li{!! (Request::path()=='repayments') ? ' class=active' : null !!}>
                <a href="{{ url('repayments') }}">Договоры</a>
            </li>
            <li{!! (Request::path()=='dataloader') ? ' class=active' : null !!}>
                <a href="{{ url('adminpanel/dataloader') }}">Загрузка данных</a>
            </li>
            <li{!! (Request::path()=='massivechange') ? ' class=active' : null !!}>
                <a href="{{ url('adminpanel/massivechange') }}">Массовые обработки</a>
            </li>
            <li{!! (Request::path()=='adminpanel/tester') ? ' class=active' : null !!}>
                <a href="{{ url('/adminpanel/tester') }}">Тестирование</a>
            </li>
            <li class='{!! (Request::path()=='adminpanel/tester/loadtest') ? 'active' : null !!} sub-item'><a href="{{url('adminpanel/tester/loadtest')}}">Тестирование нагрузки</a></li>
            <li{!! (Request::path()=='adminpanel/config') ? ' class=active' : null !!}>
                <a href="{{ url('/adminpanel/config') }}">Настройки</a>
            </li>
            <li{!! (Request::path()=='adminpanel/roles/index') ? ' class=active' : null !!}>
                <a href="{{ url('/adminpanel/roles/index') }}">Роли</a>
            </li>
            <li{!! (Request::path()=='usertests/editor/index') ? ' class=active' : null !!}>
                <a href="{{ url('/usertests/editor/index') }}">Редактор тестов</a>
            </li>
        </ul>
    </div>
    <div class="col-xs-8 col-sm-9 col-md-10 main">
        @yield('subcontent')
    </div>
</div>
@stop