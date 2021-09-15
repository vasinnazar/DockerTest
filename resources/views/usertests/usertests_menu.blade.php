@extends('app')
@section('title') Список тестов @stop
@section('content')
<div class='row'>
    <div class='col-xs-12 col-sm-3 col-lg-2 hidden-print'>
        <ul class='nav nav-pills nav-stacked'>
            @if(!is_null(Auth::user()) && Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_OPEN,\App\Utils\PermLib::SUBJ_USER_TESTS)))
            <li class='{{ (str_contains(Request::path(), 'usertests/index')) ? 'active' : '' }}'>
                <a href="{{ url('/usertests/index') }}">Тесты</a>
            </li>
            @endif
            @if (config('app.version_type') != 'debtors')
            <li class='{{ (str_contains(Request::path(), 'help/videos/')) ? 'active' : '' }}'>
                <a href="{{ url('/help/videos') }}">Видео инструкции</a>
            </li>
            @endif
        </ul>
    </div>
    <div class='col-xs-12 col-sm-9 col-lg-10'>
        @yield('subcontent')
    </div>
</div>
@stop