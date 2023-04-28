@extends('app')
@section('title') История заемщика @stop
@section('content')
<ol class="breadcrumb">
  <li><a href="{{url('debtors/index')}}">Список должников</a></li>
  <li><a href="{{url('debtors/debtorcard/'.$debtor_id)}}">Карточка</a></li>
  <li class="active">История заемщика</li>
</ol>
<table class='table table-condensed'>
    <thead>
        <tr>
            <th>Дата</th>
            <th>ФИО</th>
            <th>Подразделение</th>
            <th>Ответственный</th>
            <th>Статус</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($loans as $loan)
        <tr>
            <td>{{with(new \Carbon\Carbon($loan->loan_created_at))->format('d.m.Y')}}</td>
            <td>{{$loan->fio}}</td>
            <td>{{$loan->subdiv_name}}</td>
            <td>{{$loan->user_name}}</td>
            <td>
                @if($loan->closed)
                <?php
                $rep = DB::connection('arm')->table('repayments')
                        ->where('loan_id', $loan->loan_id)
                        ->where('repayment_type_id', 3)
                        ->first();
                ?>
                <span class='label label-success'>Закрыт{{($rep) ? ' ' . date('d.m.Y', strtotime($rep->created_at)) : ''}}</span>
                @else
                <span class='label label-danger'>Открыт</span>
                @endif
            </td>
            <td>
                @if($loan->closed==0)
                <a href='/debtors/loans/summary/{{$loan->loan_id}}' class='btn btn-default btn-sm' target="_blank"><span class='glyphicon glyphicon-eye-open'></span></a>
                
                <?php
                $now_day = date('Y-m-d', time());
                $opened = App\DebtorUpdateHistory::where('arm_loan_id', $loan->loan_id)
                        ->where('created_at', '>=', $now_day . ' 00:00:00')
                        ->where('created_at', '<=', $now_day . ' 23:59:59')
                        ->first();
                ?>
                
                @if (is_null($opened))
                <button data-loanid="{{$loan->loan_id}}" data-loanid1c="{{$loan->arm_loan_id_1c}}" data-debtorid="{{$debtor_id}}" class="btn btn-default btn-sm update-loan"><span class='glyphicon glyphicon-refresh'></span></button>
                @endif
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@stop

@section('scripts')
<script src="{{asset('js/debtors/debtorsController.js?1')}}"></script>
<script>
$(document).ready(function() {
    $.debtorsCtrl.updateLoan();
});
</script>
@stop
