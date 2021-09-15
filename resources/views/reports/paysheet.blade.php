@extends('reports.reports')
@section('title')Расчетный лист@stop
@section('subcontent')
<?php use Carbon\Carbon,     App\ContractForm,     App\Subdivision,     App\User; ?>
<div class="form-inline">
    <div class="input-group input-group-sm">
        <span class="input-group-addon">месяц</span>
        <select name="month" class="form-control input-sm" id="paysheetMonth">
            <?php  $months = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'] ?>
            @for($i=0;$i<12;$i++)
            @if(Carbon::now()->month==$i+1)
            <option selected value="{{$i+1}}">{{$months[$i]}}</option>
            @else
            <option value="{{$i+1}}">{{$months[$i]}}</option>
            @endif
            @endfor
        </select>
    </div>
    <div class="input-group input-group-sm">
        <span class="input-group-addon">год</span>
        <input class="form-control input-sm" name="year" value="{{Carbon::now()->format('Y')}}" id="paysheetYear"/>
    </div>
    <label><input name='advance' type='checkbox'/> Расчет за первую половину месяца</label>
    @if(Auth::user()->isAdmin())
    <input type='hidden' name='user_id' id="userID"/>
    <input type='text' name='user_id_autocomplete' class='form-control' data-autocomplete='users' />
    @endif
    <button target="_blank" class="btn btn-default btn-sm" onclick="openPaysheet()"><span class="glyphicon glyphicon-book"></span> Сформировать</button>
</div>
@stop
@section('scripts')
<script>
    function openPaysheet() {
        var url = armffURL + 'reports/paysheet/pdf?month=' + $('#paysheetMonth').val() + '&year=' + $('#paysheetYear').val();
        if($('#userID').length>0){
            url += '&user_id='+$('#userID').val();
        }
        if($('[name="newzup"]').length>0 && $('[name="newzup"]').prop('checked')){
            url += '&newzup=1';
        }
        if($('[name="advance"]').length>0 && $('[name="advance"]').prop('checked')){
            url += '&advance=1';
        }
        window.open(url);
//        window.open(armffURL + 'reports/paysheet/pdf?month=' + $('#paysheetMonth').val() + '&year=' + $('#paysheetYear').val());
    }
</script>
<!--<script src="{{URL::asset('js/libs/jqueryui/jquery-ui.min.js')}}"></script>-->
<!--<script src="{{ URL::asset('js/reports/reportController.js') }}"></script>-->
@stop