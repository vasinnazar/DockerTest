@extends('app')
@section('title') Кол-во контрагентов на ответственном @stop
@section('css')
<style>
    .debtors-frame{
        height: 250px;
        overflow-y:scroll;
    }
    .debtors-table-frame{
        border: 1px solid #ccc;
        padding: 10px;
    }
    @media print {
        form {
            display: none;
        }
    }
</style>
@stop
@section('content')
<br>
<div class="row">
    <div class="col-xs-12">
        <table class="table table-condensed table-striped table-bordered" id="customersCountTable">
            <thead>
                <tr>
                    <th>Ответственный</th>
                    <th>Кол-во контрагентов</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($arData as $row)
                <tr>
                    <td>{{ $row['username'] }}</td>
                    <td>{{ $row['count'] }}</td>
                </tr>
                @endforeach
            </tbody>
   </table>
    </div>
</div>
@stop
@section('scripts')
@stop
