@extends('reports.reports')
@section('title') Оборотно-сальдовая ведомость по 71 счету @stop
@section('subcontent')
<table class="table table-bordered">
    <thead>
        <tr>
            <th colspan="3">{{Auth::user()->name}}</th>
        </tr>
        <tr>
            <th>Сумма вашего подотчета</th>
            <th>Сумма за которую Вы отчитались</th>
            <th>Сумма за которую Вам необходимо отчитаться</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{$balanceDt}} руб.</td>
            <td>{{$balanсeKt}} руб.</td>
            <td>{{$balance}} руб.</td>
        </tr>
    </tbody>
</table>
<div class="alert-danger alert" style="text-align: center; font-size: 30px;">
    Срок отчета по суммам - не более 15 дней!
</div>

@stop
@section('scripts')
@stop