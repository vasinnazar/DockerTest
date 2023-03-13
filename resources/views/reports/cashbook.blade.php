@extends('reports.reports')
@section('title')Кассовая книга@stop
@section('subcontent')
<?php

use Carbon\Carbon;
?>
<div class="form-inline">
    <div class="input-group input-group-sm">
        <span class="input-group-addon">от</span>
        <input class="form-control input-sm" name="from" placeholder="Дата от" type="date" id="cashbookDateFrom" value="{{Carbon::now()->format('Y-m-d')}}"/>
    </div>
    <div class="input-group input-group-sm">
        <span class="input-group-addon">до</span>
        <input class="form-control input-sm" name="to" placeholder="Дата по" type="date" id="cashbookDateTo" value="{{Carbon::now()->format('Y-m-d')}}"/>
    </div>
    @if(Auth::user()->isAdmin())
    <div class="input-group input-group-sm">
        <!--{!!Form::select('cashbookSubdivision',\App\Subdivision::pluck('name','id'),null,['class'=>'form-control','id'=>'cashbookSubdivision'])!!}-->
        <input type='hidden' name='subdivision_id' id="cashbookSubdivision" />
        <input type='text' name='subdivision_id_autocomplete' class='form-control' data-autocomplete='subdivisions' />
    </div>
    @else
    <input type='hidden' name='subdivision_id' id="cashbookSubdivision" value="{{Auth::user()->subdivision_id}}" />
    @endif
    <button target="_blank" class="btn btn-default btn-sm" onclick="openCashbook()"><span class="glyphicon glyphicon-book"></span> Сформировать</button>
    <br>
    <br>
    @if(Auth::user()->isAdmin())
<!--    <div class="input-group input-group-sm">
        <span class="input-group-addon">Дата</span>
        <input class="form-control input-sm" name="from" placeholder="Дата от" type="date" id="cashbookDate" value="{{Carbon::now()->format('Y-m-d')}}"/>
    </div>-->
<!--    <div class="input-group input-group-sm">
        {!!Form::select('cashbookSubdivision2',\App\Subdivision::pluck('name','id'),null,['class'=>'form-control','id'=>'cashbookSubdivision2'])!!}
        <input type='hidden' name='subdivision_id' id="cashbookSubdivision2" />
        <input type='text' name='subdivision_id_autocomplete' class='form-control' data-autocomplete='subdivisions' />
    </div>
    <button class="btn btn-default btn-sm" onclick="syncCashbook()"><span class="glyphicon glyphicon-book"></span> Синхронизировать</button>-->
    
    <div>
        ОТЧЕТ по кассе в точках продаж и кассе офиса - ежедневный ООО МФО "ПростоДЕНЬГИ"<br>
        <div class="input-group input-group-sm">
            <span class="input-group-addon">Дата</span>
            <input class="form-control input-sm" name="from" type="date" id="dailyCashSummaryDate" value="{{Carbon::now()->format('Y-m-d')}}"/>
        </div>
        <button class='btn btn-primary' onclick="openDailyCashSummary(); return false;">Сформировать</button>
    </div>
    @endif
</div>
@stop
@section('scripts')
<script>
    function openCashbook() {
        window.open(armffURL + 'reports/pdf/cashbook?dateFrom=' + $('#cashbookDateFrom').val() + '&dateTo=' + $('#cashbookDateTo').val() + '&subdivisionId=' + $('#cashbookSubdivision').val());
    }
    function syncCashbook() {
        $.app.blockScreen(true);
        $.get(armffURL + 'cashbook/sync', {date: $('#cashbookDate').val(), subdivision_id: $('#cashbookSubdivision2').val()}).done(function (data) {
            $.app.blockScreen(false);
            $.app.ajaxResult(data);
        });
    }
    function openDailyCashSummary(){
        window.open(armffURL + 'reports/pdf/dailycashsummary?date='+$('#dailyCashSummaryDate').val());
    }
</script>
<!--<script src="{{URL::asset('js/libs/jqueryui/jquery-ui.min.js')}}"></script>-->
<!--<script src="{{ URL::asset('js/reports/reportController.js') }}"></script>-->
@stop
