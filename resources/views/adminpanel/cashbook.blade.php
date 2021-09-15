@extends('adminpanel')
@section('title')Кассовая книга@stop
@section('subcontent')
<table class="table table-borderless table-condensed table-striped">
    <thead>
        <tr>
            <th>Дата</th>
            <th>Сумма</th>
            <th>Баланс</th>
            <th>Действие</th>
            <th>Кредитник</th>
        </tr>
    </thead>
    @foreach($cashbook as $cb)
    <tr>
        <td>{{$cb->created_at}}</td>
        <td>{{$cb->money}}</td>
        <td>{{$cb->balance}}</td>
        <td>{{$cb->action}}</td>
        <td>{{$cb->loan_id}}</td>
    </tr>
    @endforeach
</table>
@stop
@stop