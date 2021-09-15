@extends('app')
@section('title') Отчеты @stop
@section('content')
<div class="row">
    <div class="col-sm-3 col-md-2 sidebar">
        <ul class="nav nav-pills nav-stacked">
            @if(Auth::user()->hasRole(\App\Role::SPEC) || Auth::user()->hasRole(\App\Role::ADMIN) || Auth::user()->hasRole(\App\Role::RUC))
            <li class="{{ (str_contains(Request::path(), 'cashbook')) ? 'active' : '' }}">
                <a href="{{ url('/reports/cashbook') }}">Кассовая книга</a>
            </li>
            <li class="{{ (str_contains(Request::path(), 'dailycashreport')) ? 'active' : '' }}">
                <a href="{{ url('/reports/dailycashreportslist') }}">Ежедневные отчёты</a>
            </li>
            <li class="{{ (str_contains(Request::path(), 'docsregister')) ? 'active' : '' }}">
                <a href="{{ url('/reports/docsregister') }}">Реестр документов</a>
            </li>
            <li class="{{ (str_contains(Request::path(), 'plan')) ? 'active' : '' }}">
                <a href="{{ url('/reports/plan') }}">Выполнение плана</a>
            </li>
            <li class="{{ (str_contains(Request::path(), 'saldo')) ? 'active' : '' }}">
                <a href="{{ url('/reports/saldo') }}">Оборотно-сальдовая ведомость</a>
            </li>
            @endif
            <li class="{{ (str_contains(Request::path(), 'paysheet')) ? 'active' : '' }}">
                <a href="{{ url('/reports/paysheet') }}">Расчётный лист</a>
            </li>
            @if(Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_OPEN,\App\Utils\PermLib::SUBJ_NO_SUBDIVS_REPORT)))
            <li class="{{ (str_contains(Request::path(), 'absentsubdivisions')) ? 'active' : '' }}">
                <a href="{{ url('/reports/absentsubdivisions') }}">Отчет по отсутствующим подразделениям</a>
            </li>
            @endif
            @if(Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_OPEN,\App\Utils\PermLib::SUBJ_ADVANCE_REPORTS)))
            <li class="{{ (str_contains(Request::path(), 'advancereports')) ? 'active' : '' }}">
                <a href="{{ url('/reports/advancereports/index') }}">Авансовые отчеты</a>
            </li>
            @endif
            @if(Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_OPEN,\App\Utils\PermLib::SUBJ_USERPHOTOS)))
            <li class="{{ (str_contains(Request::path(), 'userphotos')) ? 'active' : '' }}">
                <a href="{{ url('/reports/userphotos/index') }}">Фото специалистов</a>
            </li>
            @endif
        </ul>
    </div>
    <div class="col-sm-9 col-md-10 main">
        @yield('subcontent')
    </div>
</div>
@stop