@extends('app')
@section('title') Печать @stop
@section('content')
<div class='row'>
    <div class="col-xs-12">
        <table class="table">
            <tr>
                <td style="text-align: right; vertical-align: middle;">
                    Дата уведомления: 
                </td>
                <td style="text-align: left;">
                    <input type="date" name="date_demand" class="form-control" style="width: 200px;" value="{{date('Y-m-d', time())}}" />
                </td>
            </tr>
            @foreach ($debtors as $debtor)
            <tr>
                <td style="width: 30%;"><a href='{{url('debtors/debtorcard/'.$debtor->debtor_id)}}' target="_blank">{{$debtor->fio}}</a></td>
                <td style="text-align: left;">
                    <a href="{{url('debtors/debtorcard/createPdf/' . $contractforms['anketa'] . '/'.$debtor->debtor_id.'/0')}}" class="btn btn-default" target="_blank">Печать анкеты</a>
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <a href="{{url('debtors/debtorcard/createPdf/' . $contractforms['notice_personal'] . '/'.$debtor->debtor_id.'/')}}" class="btn btn-default" id="printNotice">Печать уведомления</a>
                </td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@stop
@section('scripts')
<script src="{{asset('js/debtors/debtorsController.js')}}"></script>
<script src="{{asset('js/libs/bootstrap-datetimepicker/js/bootstrap-datetimepicker.js')}}"></script>
<script>
$(document).ready(function () {
    $.debtorsCtrl.init();
    $.debtorsCtrl.initDebtorsCard();
    
    $(document).on('click', '#printNotice', function() {
        window.open($(this).attr('href') + '/' + $('input[name="date_demand"]').val());
        return false;
    });
});
</script>

@stop
