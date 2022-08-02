<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="_token" content="<?php echo csrf_token(); ?>">
        <meta name="app_url" content="{{url('/')}}">
        <meta name="subdivision_id" content="<?php if (!is_null(Auth::user())) echo Auth::user()->subdivision_id ?>">
        <meta name="admin" content="<?php echo (!is_null(Auth::user())) ? Auth::user()->isAdmin() : false ?>">
        <link rel="icon" type="image/png" href="{{asset('images/favicon.png')}}">
        <title>
            @yield('title')
        </title>

        <!--ШРИФТЫ-->

        <style>
            @font-face {
                font-family: 'Ubuntu Condensed';
                font-style: normal;
                font-weight: 400;
                src: local('Ubuntu Condensed'), local('UbuntuCondensed-Regular'), url({{asset('fonts/UbuntuCondensed-Regular.ttf')}}) format('truetype');
            }
        </style>
        <!--<link href='//fonts.googleapis.com/css?family=Roboto:400,300' rel='stylesheet' type='text/css'>-->
        <!--<link href='https://fonts.googleapis.com/css?family=Ubuntu+Condensed&subset=latin,cyrillic,cyrillic-ext' rel='stylesheet' type='text/css'>-->
        <!--<link href="http://fonts.googleapis.com/css?family=Scada:400,400italic,700,700italic&amp;subset=latin,cyrillic" rel="stylesheet" type="text/css">-->
        <!--КОНЕЦ: ШРИФТЫ-->

        <!--СТИЛИ ДЛЯ БИБЛИОТЕК-->
        <!--<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>-->
        <link href="{{asset('css/lib-styles/jquery-ui.css')}}" rel="stylesheet" type="text/css"/>
        <!--<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">-->
        <link rel="stylesheet" href="{{asset('css/lib-styles/jquery-ui-2.css')}}" type="text/css"/>
        <!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.5/css/bootstrap.min.css">-->
        <link rel="stylesheet" href="{{asset('css/lib-styles/bootstrap.min.css')}}" type="text/css">
        <!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.5/css/bootstrap-theme.min.css">-->
        <link rel="stylesheet" href="{{asset('css/lib-styles/bootstrap-theme.min.css')}}">
        <!--<link rel="stylesheet" href="{{ asset('js/libs/bootstrap-modal/css/bootstrap-modal.css') }}">-->
        <!--<link rel="stylesheet" href="{{ asset('js/libs/bootstrap-modal/css/bootstrap-modal-bs3patch.css') }}">-->
        <link rel="stylesheet" href="{{ asset('css/jquery.kladr.min.css') }}">
        <!--<link href="https://dadata.ru/static/css/lib/suggestions-15.5.css" type="text/css" rel="stylesheet" />-->
        <link href="{{asset('css/lib-styles/suggestions-15.5.css')}}" type="text/css" rel="stylesheet" />
        <link rel="stylesheet" href="{{ asset('css/MooDialog.css') }}">
        <link rel="stylesheet" href="{{ asset('/js/libs/bs-datepicker/css/bootstrap-datepicker3.standalone.min.css') }}">
        <!--<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.8/css/jquery.dataTables.css">-->
        <!--<link rel="stylesheet" href="//cdn.datatables.net/1.10.7/css/jquery.dataTables.min.css">-->
        <link rel="stylesheet" href="{{ asset('css/datatables.bootstrap.css') }}">
        <!--КОНЕЦ: СТИЛИ ДЛЯ БИБЛИОТЕК-->

        <!--ОБЩИЕ СТИЛИ-->
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        <link rel="stylesheet" href="{{ asset('css/form.css') }}">
        <link rel="stylesheet" href="{{ asset('/css/style.css') }}">
        <link rel="stylesheet" href="{{ asset('/css/ajax-loader.css') }}">
        <link rel="stylesheet" href="{{ asset('/css/infinity.css') }}">
        <!--КОНЕЦ: ОБЩИЕ СТИЛИ-->

        <!--индивидуальные стили для страниц-->
        @yield('css')

        <!--БИБЛИОТЕКИ-->
        <!--<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>-->
        <script src="{{asset('js/libs/cdn/jquery.min.js')}}"></script>
        <!--<script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.1/js/bootstrap.min.js"></script>-->
        <script type="text/javascript" src="{{asset('js/libs/cdn/bootstrap.min.js')}}"></script>
        <!--<script src="{{ asset('js/libs/bootstrap-modal/js/bootstrap-modal.js') }}"></script>-->
        <!--<script src="{{ asset('js/libs/bootstrap-modal/js/bootstrap-modalmanager.js') }}"></script>-->
        <script type="text/javascript" src="{{asset('js/libs/cdn/jquery-ui.js')}}"></script>
        <script type="text/javascript" src="{{  URL::asset('js/libs/jquery.mask.min.js') }}"></script>
        <script type="text/javascript" src="{{  URL::asset('js/libs/jquery.maskedinput.min.js') }}"></script>
        <script type="text/javascript" src="{{  URL::asset('js/libs/bs-datepicker/js/bootstrap-datepicker.min.js') }}"></script>      
        <script type="text/javascript" src="{{  URL::asset('js/libs/bs-datepicker/locales/bootstrap-datepicker.ru.min.js') }}"></script>
        <!--кладр-->
        <!--<script type="text/javascript" src="{{ asset('js/jquery.kladr.min.js') }}"></script>-->
        <!--<script type="text/javascript" src="{{ asset('js/controller.js') }}"></script>-->
        <script type="text/javascript" src="{{ asset('js/libs/kladr/core.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/libs/kladr/kladr.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/libs/kladr/kladr_zip.js') }}"></script>
        <!--<script type="text/javascript" src="{{ asset('js/form.js') }}"></script>-->        
        <!--валидация-->
        <!--<script type="text/javascript" src="{{ asset('js/libs/jqBootstrapValidation.js') }}"></script>-->
        <script type="text/javascript" src="{{ asset('js/libs/bs-validator.min.js') }}"></script>
        <!--<script type="text/javascript" src="{{ asset('js/libs/jquery-validation-1.14.0/jquery.validate.min.js') }}"></script>-->
        <!--<script type="text/javascript" src="{{ asset('js/libs/jquery-validation-1.14.0/additional-methods.min.js') }}"></script>-->
        <!--<script type="text/javascript" src="{{ asset('js/libs/jquery-validation-1.14.0/localization/messages_ru.min.js') }}"></script>-->
        <!--tinymce-->
        <script type="text/javascript" src="{{ asset('js/libs/tinymce/tinymce.min.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/libs/tinymce/jquery.tinymce.min.js') }}"></script>
        <!--<script src="//cdn.datatables.net/1.10.7/js/jquery.dataTables.min.js"></script>-->
        <!--<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.8/js/jquery.dataTables.js"></script>-->
        <script type="text/javascript" charset="utf8" src="{{asset('js/libs/cdn/jquery.dataTables.js')}}"></script>
        <script type="text/javascript" src="{{ asset('js/datatables.bootstrap.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/jquery.speller.js') }}"></script>
        <!--<script type="text/javascript" src="{{ asset('js/libs/fingerprint2.min.js') }}"></script>-->
        <!--<script type="text/javascript" src="{{ asset('js/libs/jQuery.maskMoney.min.js') }}"></script>-->
        <script type="text/javascript" src="{{ asset('js/libs/moment-with-locales.min.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/jquery.mymoney.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/libs/js.cookie.js') }}"></script>
        <!--КОНЕЦ: БИБЛИОТЕКИ-->
        <script type="module" src="{{asset('js/infinityController.js')}}"></script>

        <!--ОБЩИЕ СКРИПТЫ-->
        <script>
$.ajaxSetup({
    headers: {
        'X-CSRF-Token': $('meta[name="_token"]').attr('content')
    }
});
var dataTablesRuLang = {
    "processing": "Подождите...",
    "search": "Поиск:",
    "lengthMenu": "Показать _MENU_ записей",
    "info": "Записи с _START_ до _END_ из _TOTAL_ записей",
    "infoEmpty": "Записи с 0 до 0 из 0 записей",
    "infoFiltered": "(отфильтровано из _MAX_ записей)",
    "infoPostFix": "",
    "loadingRecords": "Загрузка записей...",
    "zeroRecords": "Записи отсутствуют.",
    "emptyTable": "В таблице отсутствуют данные",
    "paginate": {
        "first": "Первая",
        "previous": "Предыдущая",
        "next": "Следующая",
        "last": "Последняя"
    },
    "aria": {
        "sortAscending": ": активировать для сортировки столбца по возрастанию",
        "sortDescending": ": активировать для сортировки столбца по убыванию"
    }
},
//armffURL = '/';
        armffURL = $('meta[name="app_url"]').attr("content") + '/';
        </script>
        <script src="{{  URL::asset('js/app.js?2') }}"></script>
        <script src="{{ URL::asset('js/common/TableController.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/common/DataGridController.js') }}"></script>
        <script>
$(document).ready(function () {
    $.app.init();
});
        </script>
        <!-- RedHelper -->
<!--            <script id="rhlpscrtg" type="text/javascript" charset="utf-8" async="async" 
                src="https://web.redhelper.ru/service/main.js?c=zaymer">
        </script> -->
        <!--/Redhelper -->
        <!--КОНЕЦ: ОБЩИЕ СКРИПТЫ-->

        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
                <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
                <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->

    </head>
    <body>
        <nav class="navbar navbar-default">
            <div class="container-fluid">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                        <span class="sr-only">Toggle Navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <!--<a class="navbar-brand" href="{{ url('home2') }}">Финтерра</a>-->
                    <a class="navbar-brand">Финтерра</a>
                </div>
                @if (!Auth::guest())
                <div class="navbar-collapse collapse " id="bs-example-navbar-collapse-1">
                    <ul class="nav navbar-nav">
                        @if(Auth::user()->hasRole(\App\Role::SPEC))
                        <li><a href="{{ url('/') }}">Рабочий стол</a></li>
                        @endif
                        @if(Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_CREATE,\App\Utils\PermLib::SUBJ_CLAIMS)))
                        <li><a href="#" data-toggle="modal" data-target="#searchClientModal">Оформить заявку</a></li>
                        @endif
                        @if(Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_OPEN,\App\Utils\PermLib::SUBJ_LOANS)))
                        <li><a href="{{ url('/loans') }}">Погасить займ</a></li>
                        @endif
                        @if(Auth::user()->hasRole(\App\Role::SPEC))
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                                Отчёты
                                <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="{{ url('/reports/dailycashreportslist') }}">Ежедневные отчёты</a></li>
                                <li><a href="{{ url('/reports/cashbook') }}">Кассовая книга</a></li>
                                <li><a href="{{ url('/reports/paysheet') }}">Расчетный лист</a></li>
                                <li><a href="{{ url('/reports/docsregister') }}">Реестр документов</a></li>
                                <li><a href="{{ url('/reports/plan') }}">Выполнение плана</a></li>
                                <li><a href="{{ url('/reports/saldo') }}">Оборотно-сальдовая ведомость</a></li>
                                <li><a href="{{url('/reports/rnko')}}" target="_blank">Сверка РНКО</a></li>
                                @if(Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_OPEN,\App\Utils\PermLib::SUBJ_QUIZ_DEPT_REPORT)))
                                <li><a href="{{url('/quizdept/report')}}" target="_blank">Отчет о взаимодействии</a></li>
                                @endif
                                @if(Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_OPEN,\App\Utils\PermLib::SUBJ_NO_SUBDIVS_REPORT)))
                                <li class="{{ (str_contains(Request::path(), 'absentsubdivisions')) ? 'active' : '' }}">
                                    <a href="{{ url('/reports/absentsubdivisions') }}">Отчет по отсутств. подр.</a>
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
                        </li>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                                Заявления
                                <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="{{ url('blanks/customers') }}">для клиентов</a></li>
                                <li><a href="{{ url('blanks/users') }}">для сотрудников</a></li>
                                <li><a href="{{ url('matclaims') }}">на материалы</a></li>
                                <li><a href="{{ url('employment/docs') }}">Документы на трудоустройство</a></li>
                            </ul>
                        </li>
                        @endif
						@if(!is_null(Auth::user()) && !Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_SELECT,\App\Utils\PermLib::SUBJ_CANDIDATE_LIST)))
                        <li><a href="{{ url('/orders') }}">Кассовые операции</a></li>
						@endif
                        @if(Auth::user()->hasRole(\App\Role::SPEC))
                        <li><a href="{{ url('/customers') }}">Физ. лица</a></li>
                        @endif
                        <!--<li><a href="{{ url('/cardchanges') }}">Замена карт</a></li>-->
                        @if(Auth::user()->hasRole(\App\Role::SPEC))
                        <li><a href="{{ url('/npf') }}">НПФ</a></li>
                        @endif
                        <li><a href="{{ url('/help') }}">Помощь</a></li>
                        @yield('menu')
                        @if (!is_null(Auth::user()) && Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_OPEN,\App\Utils\PermLib::SUBJ_REMREQ)) && Auth::user()->id==5)
                        <li><a href="{{ url('/usersreqs/remove') }}">Запросы на удаление</a></li>
                        @endif
                        @if (!is_null(Auth::user()) && Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_OPEN,\App\Utils\PermLib::SUBJ_ADMINPANEL)))
                        <li><a href="{{ url('/adminpanel') }}">Админ. панель</a></li>
                        @endif
                        @if(Auth::user()->hasRole(\App\Role::SPEC))
                        <li><a href="{{ url('/reports/phonecall') }}">Обзвон</a></li>
                        @endif
                        @if(!is_null(Auth::user()) && Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_OPEN,\App\Utils\PermLib::SUBJ_RNKO)))
                        <li class="{{ (str_contains(Request::path(), 'rnko')) ? 'active' : '' }}">
                            <a href="{{ url('/reports/rnko/number') }}">Сверка РНКО по номеру карты</a>
                        </li>
                        @endif
                        @if(!is_null(Auth::user()) && Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_OPEN,\App\Utils\PermLib::SUBJ_DEBTORS)))
                        <li class="{{ (str_contains(Request::path(), 'debtors')) ? 'active' : '' }}">
                            <a href="{{ url('/debtors/index') }}">Должники</a>
                        </li>
                        @endif
                        @if(!is_null(Auth::user()) && Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_OPEN,\App\Utils\PermLib::SUBJ_DEBTOR_TRANSFER)))
                        <li class="{{ (str_contains(Request::path(), 'debtortransfer')) ? 'active' : '' }}">
                            <a href="{{ url('/debtortransfer/index') }}">Передача должников</a>
                        </li>
                        @endif
						@if(!is_null(Auth::user()) && Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_SELECT,\App\Utils\PermLib::SUBJ_CANDIDATE_LIST)))
                        <li class="{{ (str_contains(Request::path(), 'candidate')) ? 'active' : '' }}">
                            <a href="{{ url('/candidate/index') }}">Кандидаты</a>
                        </li>
                        @endif
						@if(!is_null(Auth::user()) && Auth::user()->hasRole('GraphSales'))
                        <li class="{{ (str_contains(Request::path(), 'graph')) ? 'active' : '' }}">
                            <a href="{{ url('/graph/index') }}">График продаж</a>
                        </li>
                        @endif
                        @if(!is_null(Auth::user()) && Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_OPEN,\App\Utils\PermLib::SUBJ_USER_TESTS)))
                        <li class="{{ (str_contains(Request::path(), 'usertests')) ? 'active' : '' }}">
                            <a href="{{ url('/usertests/home') }}">Обучение</a>
                        </li>
                        @endif
                        @if(Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_OPEN,\App\Utils\PermLib::SUBJ_NO_SUBDIVS_REPORT)))
                        <li class="{{ (str_contains(Request::path(), 'absentsubdivisions')) ? 'active' : '' }}">
                            <a href="{{ url('/reports/absentsubdivisions') }}">Отчет по отсутств. подр.</a>
                        </li>
                        @endif
                    </ul>
                    <ul class="nav navbar-nav navbar-right">
                        @if (Auth::guest())
                        <li><a href="{{ url('/auth/login') }}">Вход</a></li>
                        <li><a href="{{ url('/auth/register') }}">Регистрация</a></li>
                        @else
                        <?php
                        if (config('app.version_type') == 'debtors') {
                            $currentDebtorUser = auth()->user();

                            $pfx_loan = ($currentDebtorUser->hasRole('debtors_personal')) ? 'pn' : 'ln';

                            if ($currentDebtorUser->hasRole('debtors_remote') || ($currentDebtorUser->hasRole('debtors_personal') && $currentDebtorUser->hasRole('debtors_chief'))) {
                                $timeToShow = \Carbon\Carbon::now()->subMinutes(15);
                                if ($currentDebtorUser->hasRole('debtors_remote')) {
                                    $messagesDebtorOnSite = DB::connection('debtors')->table('messages')->where('type', 'like', 'sn%')->where('created_at', '>=', $timeToShow)->where('deleted_at', null);
                                }
                                
                                if ($currentDebtorUser->hasRole('debtors_personal')) {
                                    $messagesDebtorOnSite = DB::connection('debtors')->table('messages')->where('type', 'like', 'vn%')->where('created_at', '>=', $timeToShow)->where('deleted_at', null);
                                }
                                
                                /*if (!$currentDebtorUser->hasRole('debtors_chief')) {
                                    $messagesDebtorOnSite->where('recepient_id', $currentDebtorUser->id);
                                }*/

                                $totalMessages = $messagesDebtorOnSite->count();

                                if ($totalMessages > 0) {
                                    ?>
                                    <li>
                                        <div class='navbar-form'>
                                            <a class='debtoronsite-window-toggler btn btn-danger' href='#'>
                                                <span class='glyphicon glyphicon-exclamation-sign'></span> Должник на сайте
                                            </a>
                                        </div>
                                    </li>
                                    <?php
                                }
                            }

                            $timeToShow = \Carbon\Carbon::now()->subMinutes(15);
                            $messagesDebtorOnSubdivision = DB::connection('debtors')->table('messages')->where('type', 'like', $pfx_loan . '%')->where('created_at', '>=', $timeToShow)->where('deleted_at', null);

                            /*if (!$currentDebtorUser->hasRole('debtors_chief')) {
                                $messagesDebtorOnSubdivision->where('recepient_id', $currentDebtorUser->id);
                            }*/

                            $totalMessages = $messagesDebtorOnSubdivision->count();

                            if ($totalMessages > 0) {
                                ?>
                                <li>
                                    <div class='navbar-form'>
                                        <a class='alertdebtor-window-toggler btn btn-danger' href='#'>
                                            <span class='glyphicon glyphicon-exclamation-sign'></span> Должник на точке
                                        </a>
                                    </div>
                                </li>
                                <?php
                            }

                            if ($currentDebtorUser->hasRole('debtors_remote')) {
                                $messagesLossCalls = DB::connection('debtors')->table('debtors_loss_calls');
                                /*if (!$currentDebtorUser->hasRole('debtors_chief')) {
                                    $messagesLossCalls->where('responsible_user_id', $currentDebtorUser->id);
                                }*/

                                $totalCallsMessages = $messagesLossCalls->count();

                                if ($totalCallsMessages > 0) {
                                    ?>
                                    <li>
                                        <div class='navbar-form'>
                                            <a class='losscalls-window-toggler btn btn-danger' href='#'>
                                                <span class='glyphicon glyphicon-earphone'></span> Пропущенные звонки
                                            </a>
                                        </div>
                                    </li>
                                    <?php
                                }
                            }
                        }
                        ?>
                        <li>
                            <div class='navbar-form'>
                                <a class='chat-window-toggler btn btn-default' href='#'>
                                    <span class='glyphicon glyphicon-envelope'></span> Сообщения
                                </a>
                            </div>
                        </li>
                        <li class="dropdown">
                            <a id="curUserDropdown" href="#" class="dropdown-toggle" data-toggle="dropdown"
                               data-id="{{Auth::user()->id}}"
                               <?php
                               echo (Auth::user()->isAdmin()) ? 'data-isadmin="true"' : '';
                               echo (Auth::user()->isCC()) ? 'data-iscc="true"' : '';
                               ?> 
                               role="button" aria-expanded="false">
                                {{ Auth::user()->name }}
                                <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu" role="menu">
                                @if(config('app.version_type')=='debtors')
                                <li><a href="{{url('auth/logout')}}">Выход</a></li>
                                @else
                                <li><a href="#" onclick="$.app.openWorkTimeModal();
                                        return false;">Выход</a></li>
                                @endif
                            </ul>
                        </li>
                        <li class="dropdown">                  
                            <?php
                            $link = '<a id="curUserSubdivisionDropdown" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" ';
                            $subd = \App\Subdivision::find(Auth::user()->subdivision_id);
                            $link .= (!is_null($subd)) ? ('title="' . $subd->name . '">'
                                    . '<span id="curUserSubdivisionName">'
                                    . substr($subd->name, 0, 50)
                                    . ((strlen($subd->name) > 50) ? '...' : '')) : 'Подразделение не выбрано!';
                            $link .= '</span>';
                            $link .= '<span class="caret"></span>';
                            $link .= '</a>';
                            echo $link;
                            ?>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="{{ url('subdivisions/change') }}">Сменить</a></li>
                            </ul>
                        </li>
                        @endif
                    </ul>
                </div>
                @endif
            </div>
        </nav>

        @if (Session::has('msg'))
        <div class="alert {{ Session::get('class') }}" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{ Session::get('msg') }}
        </div>
        @endif
        @if (Session::has('msg_suc'))
        <div class="alert alert-success" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{ Session::get('msg_suc') }}
        </div>
        @endif
        @if (Session::has('msg_err'))
        <div class="alert alert-danger" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{ Session::get('msg_err') }}
        </div>
        @elseif(isset($msg_err))
        <div class="alert alert-danger" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{ $msg_err }}
        </div>
        @endif
        @if(Session::has('error_1c'))
        <div class="alert alert-danger" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {!! serialize(json_decode(Session::get('error_1c')))!!}
        </div>
        @endif
        @if(!is_null(Auth::user()) && \App\QuizDepartment::isAvailable() && Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_OPEN,\App\Utils\PermLib::SUBJ_QUIZ_DEPT)) && (Request::path()!='quizdept/create'))
        <a href='{{url("quizdept/create")}}' class='btn btn-success' target="_blank">Ответить на опрос</a>
        @endif            

        @yield('content')

        <div class="modal fade" id="searchClientModal" tabindex="-1" role="dialog" aria-labelledby="searchClientModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title" id="searchClientModalLabel">Введите данные клиента</h4>
                    </div>
                    {!!Form::open(['route'=>'claims.create','method'=>'post'])!!}
                    <div class="modal-body">
                        <div class="row">
                            <h5 class="col-xs-12">Данные паспорта</h5>
                        </div>
                        <div class="row">
                            <div class="form-group-sm col-xs-6">
                                <label>Серия</label>
                                {!! Form::text('series',null,array(
                                'required'=>'required', 'placeholder'=>'XXXX',
                                'pattern'=>'[0-9]{4}', 'class'=>'form-control',
                                'data-minlength'=>'4','maxlength'=>'4'
                                ))!!}
                            </div>
                            <div class="form-group-sm col-xs-6">
                                <label>Номер</label>
                                {!! Form::text('number',null,array(
                                'required'=>'required', 'placeholder'=>'XXXXXX',
                                'pattern'=>'[0-9]{6}', 'class'=>'form-control',
                                'data-minlength'=>'6', 'maxlength'=>'6'
                                ))!!}
                            </div>
                        </div>
                        <div class="row">
                            <h5 class="col-xs-12">Данные предыдущего паспорта (если есть)</h5>
                        </div>
                        <div class="row">
                            <div class="form-group-sm col-xs-6">
                                <label>Серия</label>
                                {!! Form::text('old_series',null,array(
                                'placeholder'=>'XXXX',
                                'pattern'=>'[0-9]{4}', 'class'=>'form-control',
                                'data-minlength'=>'4','maxlength'=>'4'
                                ))!!}
                            </div>
                            <div class="form-group-sm col-xs-6">
                                <label>Номер</label>
                                {!! Form::text('old_number',null,array(
                                'placeholder'=>'XXXXXX',
                                'pattern'=>'[0-9]{6}', 'class'=>'form-control',
                                'data-minlength'=>'6', 'maxlength'=>'6'
                                ))!!}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        {!!Form::button('Далее',['class'=>'btn btn-primary','type'=>'submit'])!!}
                    </div>
                    {!!Form::close()!!}
                </div>
            </div>
        </div>

        <div class="modal fade bs-example-modal-lg" id="cardFrameModal" tabindex="-1" role="dialog" aria-labelledby="cardFrameModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-body"></div>
                    <div class="modal-footer">
                        <input name="frame_closed" value="0" hidden />
                        <button type="button" class="btn btn-default" data-dismiss="modal" onclick="$.app.onCardFrameClose();">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>
        @if (Auth::check())
        <script src="{{asset('js/libs/socket.io.js')}}"></script>
        <script type="module">
            import Echo from '{{asset('js/libs/echo.js')}}';
            window.Echo = new Echo({
                broadcaster: 'socket.io',
                host: window.location.hostname + ':6001',
                namespace: '',
            });
            $.infinityController.init(<?php echo Auth::user()->infinity_extension; ?>);
            $.infinityController.closingModalsInit(<?php echo Auth::user()->infinity_extension; ?>);
        </script>
        @endif
        
        @include('elements.chatElements')
        @include('elements.chatDebtorOnSubdivision')
        @include('elements.chatDebtorLossCalls')
        @include('elements.chatDebtorOnSite')
        @include('elements.manualAddPromocodeModal')
        @include('elements.worktimeModal')
        @include('elements.errorModal')
        @include('infinity.incoming_call')
        <div class="modal fade" id="uiBlockerModal" tabindex="-1" role="dialog"  aria-hidden="true">
            <div class="modal-dialog" style="width: 200px">
                <div class="modal-content">
                    <div class="modal-body" style="overflow: hidden; width: 200px; height: 200px;">
                        <div style="text-align: center">
                            <h1>Подождите...</h1>
                            <div class="sk-circle" style="margin: 60px">
                                <div class="sk-circle1 sk-child"></div>
                                <div class="sk-circle2 sk-child"></div>
                                <div class="sk-circle3 sk-child"></div>
                                <div class="sk-circle4 sk-child"></div>
                                <div class="sk-circle5 sk-child"></div>
                                <div class="sk-circle6 sk-child"></div>
                                <div class="sk-circle7 sk-child"></div>
                                <div class="sk-circle8 sk-child"></div>
                                <div class="sk-circle9 sk-child"></div>
                                <div class="sk-circle10 sk-child"></div>
                                <div class="sk-circle11 sk-child"></div>
                                <div class="sk-circle12 sk-child"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @yield('scripts')
    </body>
</html>
