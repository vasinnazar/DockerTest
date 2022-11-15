@extends('app')
@section('title') Список должников @stop
@section('css')
<link rel="stylesheet" href="{{asset('js/libs/jqGrid/css/ui.jqgrid.min.css')}}" />
<style>
    .debtors-frame {
        height: 250px;
        overflow-y:scroll;
    }
    .debtors-table-frame {
        border: 1px solid #ccc;
        padding: 10px;
    }
    .dataTables_processing {
        font-weight: bold;
        color:red;
        font-size: 20px;
    }
    #debtorsTable,#debtoreventsTable {
        font-size: 12px;
    }
</style>
@stop
@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class='pull-right btn-group'>
            <a href="{{url('debtors/recommends')}}" class="btn {{($recommends_count > 0) ? 'btn-danger' : 'btn-default'}}"{{($recommends_count > 0) ? '' : ' disabled'}}>Рекомендации ({{$recommends_count}})</a>
            <a href="##" class="btn btn-default" onclick="$.debtorsCtrl.debtorsTotalPlanned({{$user_id}})">Общее количество запланированных</a>
            @if ($personalGroup['isGroup'])
            <a href="{{url('debtors/departuremap')}}" class="btn btn-default">Карта выездов ({{$personalGroup['count']}})</a>
            <a href="{{url('debtors/departureprint')}}" class="btn btn-default" {{($personalGroup['count'] == 0) ? 'disabled' : ''}}>Печать выездов ({{$personalGroup['count']}})</a>
            @endif
            <a href="{{url('debtors/forgotten')}}" class="btn btn-default" target="_blank">"Забытые" должники</a>
            <a href="{{url('addressdoubles/index')}}" class="btn btn-default">Дубли адресов</a>
            <input type="button" class="btn btn-default" value="Зачет оплат" data-target='#userPaymentsModal' data-toggle='modal' />
            <a class="btn btn-default" href="{{url('reports/paysheet')}}" target="_blank">Расчетный лист</a>
            @if ($user_id == 69 || $user_id == 916 || $user_id == 885 || $user_id == 3448 || $user_id == 970 || $user_id == 3465)
            <a href="{{url('debtor/recurrent/massquerytask')}}"  class="btn btn-default">Массовое списание</a>
            @endif
            @if ($user_id == 69 || $user_id == 916)
            <a href="{{url('debtor/recurrent/massquerytask?type=olv_chief')}}"  class="btn btn-default">Масс. списание Ведущий</a>
            @endif
            @if ($user_id == 69 || $user_id == 3448)
            <a href="{{url('debtor/recurrent/massquerytask?type=ouv_chief')}}"  class="btn btn-default">Масс. списание Ведущий</a>
            @endif
            @if (!$personalGroup['isGroup'])
            <a href="{{url('usertests/index')}}" class="btn btn-default" target="_blank">Тесты</a>
            @endif
            <!--button type="button" class='btn btn-default' onclick="$.debtorsCtrl.uploadOldDebtorEvents(); return false;">Подгрузить мероприятия</button-->
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Отчеты <span class="caret"></span>
            </button>
            <ul class="dropdown-menu">
                <li><a href="{{url('debtorsreports/dzcollect')}}">Сбор ДЗ</a></li>
                <li><a href="{{url('reports/paysheet')}}">Расчетный лист</a></li>
                <li><a href="{{url('debtors/reports/plancalend')}}">Календарь планов</a></li>
                <li><a href="{{url('debtors/reports/jobsdoneact')}}">Акт выполненных работ</a></li>
                <li><a href="{{url('debtors/reports/ovz')}}">ОВЗ</a></li>
                <li><a href="#" data-toggle="modal" data-target="#debtorsSiteLoginReportModal">Логин на сайт</a></li>
                @if ($is_chief)
                <li><a href="{{url('debtors/editSmsCount')}}">SMS</a></li>
                @if ($personalGroup['isGroup'])
                <li><a href="{{url('debtors/export/postregistry?mode=lv')}}">Реестр писем</a></li>
                @else
                <li><a href="{{url('debtors/export/postregistry?mode=uv')}}">Реестр писем</a></li>
                @endif
                @endif
                @if ($user_id == 817 || $user_id == 69)
                <li><a href="{{url('/usertests/editor/index')}}">Тесты</a></li>
                @endif
                @if ($is_chief)
                <li><a href="{{url('/debtors/notices/index')}}">Отправка писем</a></li>
                @endif
            </ul>
            @if ($canEditSmsCount)
            <a href="{{url('debtors/editSmsCount')}}" class="btn btn-default">Количество SMS</a>
            @endif
        </div>
        <br>
        <hr>
    </div>
</div>
<div class='row'>
    <div class="col-xs-12 col-lg-5">
        <div class='panel panel-default'>
            <div class='panel-heading'>
                <h3 class='panel-title'>Мероприятия <button type="button" class="btn btn-default pull-right btn-xs" data-toggle="modal" data-target="#debtorEventsSearchModal"><span class='glyphicon glyphicon-search'></span> Поиск</button>
                &nbsp;
                <a target="_blank" class="btn btn-default pull-right btn-xs" onclick="$.debtorsCtrl.debtorsEventsToExcel(); return false;"><span class='glyphicon glyphicon-export'></span> В Excel</a>
                </h3>
            </div>
            <div class='panel-body'>
                <table class='table table-condensed table-striped table-bordered' id='debtoreventsTable'>
                    <thead>
                        <tr>
                            <th>Дата план</th>
                            <th>Тип мероприятия</th>
                            <th>Контрагент</th>
                            <th>Дата факт</th>
                            <th>Ответственный</th>
                            <th></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xs-12 col-lg-7">
        <div class='panel panel-default'>
            <div class='panel-heading'>
                <h3 class='panel-title'>Должники 
                    <button type="button" class="btn btn-default pull-right btn-xs" data-toggle="modal" data-target="#debtorsSearchModal"><span class='glyphicon glyphicon-search'></span> Поиск</button>
                    &nbsp;
                    <a target="_blank" class="btn btn-default pull-right btn-xs" onclick="$.debtorsCtrl.debtorsToExcel(); return false;"><span class='glyphicon glyphicon-export'></span> В Excel</a>
                </h3>
            </div>
            <div class='panel-body'>
                <table class='table table-condensed table-striped table-bordered' id='debtorsTable'>
                    <thead>
                        <tr>
                            <th>Дата закреп</th>
                            <th>ФИО</th>
                            <th>Договор</th>
                            <th>Дней проср.</th>
                            <th>Зад-сть</th>
                            <th>ОД</th>
                            <th>База</th>
                            <th>Телефон</th>
                            <th>Группа долга</th>
                            <th>Ответственный</th>
                            <th>Стр. подр.</th>
                            <th></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<div class='row' id="totalNumberPlaned">
</div>
<div class="modal fade" id="debtorsSearchModal" tabindex="-1" role="dialog" aria-labelledby="debtorsSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id='debtorsFilter'>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="debtorsSearchModalLabel">Введите данные клиента</h4>
            </div>
            {!!Form::open()!!}
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <table class='table table-borderless'>
                            @foreach($debtorsSearchFields as $dsf)
                            <tr>
                                <td>{{$dsf['label']}}</td>
                                <td>
                                    @if ($dsf['name'] != 'debtors@qty_delays_from' AND $dsf['name'] != 'debtors@qty_delays_to' AND $dsf['name'] != 'passports@fact_address_region' AND $dsf['name'] != 'passports@address_region')
                                    <select class='form-control' name='{{'search_field_'.$dsf['name'].'_condition'}}'>
                                        <option value="=">=</option>
                                        <option value="<"><</option>
                                        <option value="<="><=</option>
                                        <option value=">">></option>
                                        <option value=">=">>=</option>
                                        <option value="like" {{($dsf['name'] == 'passports@fact_address_city') ? 'selected' : ''}}>подобно</option>
                                    </select>
                                    @endif
                                </td>
                                <td>
                                    @if(array_key_exists('hidden_value_field',$dsf))
                                    <input name='{{$dsf['name']}}' type='{{$dsf['input_type']}}' class='form-control autocomplete' data-hidden-value-field='{{'search_field_'.$dsf['hidden_value_field']}}'/>
                                    <input name='{{'search_field_'.$dsf['hidden_value_field']}}' type='hidden' />
                                    @else
                                    <input name='{{'search_field_'.$dsf['name']}}' type='{{$dsf['input_type']}}' class='form-control'/>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <!--span class="pull-left"><input type="checkbox" name="search_field_planned_departures@debtor_id" value="1">&nbsp;&nbsp;Запланированные к выезду</span-->
                <div class="pull-left" style="text-align: left;">
                <span><input type="checkbox" name="search_field_debtors@is_bigmoney" value="1">&nbsp;Большие деньги</span>
                <br>
                <span><input type="checkbox" name="search_field_debtors@is_pledge" value="1">&nbsp;Залоговые займы</span>
                <br>
                <span><input type="checkbox" name="search_field_debtors@is_pos" value="1">&nbsp;Товарные займы</span>
                </div>
                {!!Form::button('Очистить фильтр',['class'=>'btn btn-default','type'=>'button', 'id'=>'debtorsClearFilterBtn'])!!}
                {!!Form::button('Найти',['class'=>'btn btn-primary','type'=>'button', 'id'=>'debtorsFilterBtn'])!!}
            </div>
            {!!Form::close()!!}
        </div>
    </div>
</div>
<div class="modal fade" id="debtorEventsSearchModal" tabindex="-1" role="dialog" aria-labelledby="debtorEventsSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="debtorsEventsFilter">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="debtorEventsSearchModalLabel">Введите данные клиента</h4>
            </div>
            {!!Form::open()!!}
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <table class='table table-borderless' id='debtorEventsFilter'>
                            @foreach($debtorEventsSearchFields as $dsf)
                            <tr>
                                <td>{{$dsf['label']}}</td>
                                <td>
                                    @if($dsf['input_type'] != 'checkbox' && $dsf['input_type'] != 'date')
                                    <select class='form-control' name='{{'search_field_'.$dsf['name'].'_condition'}}'>
                                        <option value='='>=</option>
                                        <option value="<"><</option>
                                        <option value="<="><=</option>
                                        <option value=">">></option>
                                        <option value=">=">>=</option>
                                        <option value="like">подобно</option>
                                    </select>
                                    @endif
                                </td>
                                <td>
                                    <input type="hidden" name="search_field_debtor_events@date">
                                    @if(array_key_exists('hidden_value_field',$dsf))
                                    <input name='{{$dsf['name']}}' type='{{$dsf['input_type']}}' class='form-control autocomplete' data-hidden-value-field='{{'search_field_'.$dsf['hidden_value_field']}}'/>
                                    <input name='{{'search_field_'.$dsf['hidden_value_field']}}' type='hidden' />
                                    @else
                                    <input name='{{'search_field_'.$dsf['name']}}' type='{{$dsf['input_type']}}' class='form-control'/>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                {!!Form::button('Очистить фильтр',['class'=>'btn btn-default','type'=>'button', 'id'=>'debtorEventsClearFilterBtn'])!!}
                {!!Form::button('Найти',['class'=>'btn btn-primary','type'=>'button', 'id'=>'debtorEventsFilterBtn'])!!}
            </div>
            {!!Form::close()!!}
        </div>
    </div>
</div>
@include('elements.debtors.userPaymentsModal')
@include('elements.debtors.debtorsSiteLoginReportModal')
@stop
@section('scripts')
<script src="{{asset('js/debtors/debtorsController.js?5')}}"></script>
<script>
                        $(document).ready(function () {
                        $.debtorsCtrl.init();
                        });
</script>
@stop
