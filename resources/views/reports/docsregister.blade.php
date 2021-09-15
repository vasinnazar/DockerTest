@extends('reports.reports')
@section('title')Реестр документов @stop
@section('subcontent')
<?php

use App\MySoap;
use Carbon\Carbon;
use App\User;
use App\Subdivision;
?>
<div class="form-inline">
    <div class="input-group input-group-sm">
        <span class="input-group-addon">от</span>
        <input class="form-control input-sm" name="from" placeholder="Дата от" type="date" id="docsDateFrom" value="{{Carbon::now()->day(1)->format('Y-m-d')}}"/>
    </div>
    <div class="input-group input-group-sm">
        <span class="input-group-addon">до</span>
        <input class="form-control input-sm" name="to" placeholder="Дата по" type="date" id="docsDateTo" value="{{Carbon::now()->day(Carbon::now()->daysInMonth)->format('Y-m-d')}}"/>
    </div>
    @if(Auth::user()->isAdmin())
    <input name='user_id' type='hidden'/>
    <input type='text' name='user_id_autocomplete' class='form-control input-sm' data-autocomplete='users' placeholder="специалист" />
    <input name='subdivision_id' type='hidden'/>
    <input type='text' name='subdivision_id_autocomplete' class='form-control input-sm' data-autocomplete='subdivisions' placeholder="подразделение" />
    <label><input type='checkbox' name='remove_order_numbers' /> Убрать порядковые номера карт</label>
    @endif
    {!! Form::select('doctype',[
    '-1'=>'Все',
    MySoap::ITEM_LOAN=>'Кредитный договор',
    MySoap::ITEM_REP_DOP=>'Доп. договор',
    MySoap::ITEM_REP_CLAIM=>'Заявление о приостановке',
    MySoap::ITEM_REP_PEACE=>'Соглашение об урегулировании задолженности',
    MySoap::ITEM_RKO=>'РКО',
    MySoap::ITEM_PKO=>'ПКО',
    MySoap::ITEM_SFP=>'Книга учета карт СФП',
    MySoap::ITEM_CLAIM=>'Заявки',
    MySoap::ITEM_NPF=>'НПФ',
    ],null,['class'=>'form-control input-sm', 'id'=>'docType']) !!}
    <button target="_blank" class="btn btn-default btn-sm" onclick="openRegister()"><span class="glyphicon glyphicon-book"></span> Сформировать</button>
</div>
<hr>
<!--если админ или шумакова-->
@if(Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_OPEN, \App\Utils\PermLib::SUBJ_SALES_REPORT)))
<h2>Отчет по продажам за день</h2>
<form class='form-inline' id='salesReport1Form'>
    <div class="input-group input-group-sm">
        <span class="input-group-addon">Дата</span>
        <input class="form-control input-sm" name="start_date" placeholder="Дата от" type="date" id="salesReportDateFrom" value="{{Carbon::now()->format('Y-m-d')}}"/>
    </div>
    <button type='button' class="btn btn-default btn-sm" onclick="openSalesReport1()"><span class="glyphicon glyphicon-book"></span> Сформировать</button>
</form>
@endif
@stop
@section('scripts')
<script>
    function openRegister() {
        var url = armffURL + 'reports/docsregister/pdf?start=' + $('#docsDateFrom').val() + '&end=' + $('#docsDateTo').val() + '&doctype=' + $('#docType').val();
        if ($('input[name="subdivision_id"]').length > 0) {
            url += '&subdivision_id=' + $('input[name="subdivision_id"]').val();
            url += '&user_id=' + $('[name="user_id"]').val();
        }
        if ($('[name="remove_order_numbers"]').prop('checked')) {
            url += '&remove_order_numbers=1';
        }
        window.open(url);
    }
    function openSalesReport1() {
        window.open($.app.url + '/reports/salesreports/1/pdf?' + $('#salesReport1Form').serialize());
    }
</script>
<!--<script src="{{URL::asset('js/libs/jqueryui/jquery-ui.min.js')}}"></script>-->
<!--<script src="{{ URL::asset('js/reports/reportController.js') }}"></script>-->
@stop