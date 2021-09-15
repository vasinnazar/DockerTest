@extends('adminpanel')
@section('title') Формы договоров @stop
@section('subcontent')
{!! Form::open(['route'=>'contracts.create','method'=>'get']) !!}
{!! Form::button('<span class="glyphicon glyphicon-plus"></span> Создать',['class'=>'btn btn-default','type'=>'submit'])!!}
{!! Form::close() !!}
<table class="table table-condensed table-striped">
    <thead>
        <tr>
            <th>Название</th>
            <th>Сформировать PDF</th>
            <th>Редактировать</th>
            <th>Версии</th>
            <th>Удалить</th>
        </tr>
    </thead>
    <tbody>
        @foreach($contracts as $contract)
        <tr>
            <td>{{$contract->name}}</td>
            <td>
                <a href="{{url('/contracts/pdf/' . $contract->id)}}" title="Печать" class="btn btn-default btn-sm" target="_blank">
                    <span class="glyphicon glyphicon-print"></span>
                </a>
            </td>
            <td>
                <a href="{{url('/contracts/edit/' . $contract->id)}}" title="Редактировать" class="btn btn-default btn-sm">
                    <span class="glyphicon glyphicon-pencil"></span>
                </a>
            </td>
            <td>
                <a href="{{url('/contracts/versions?contract_id=' . $contract->id)}}" title="Версии" class="btn btn-default btn-sm">
                    <span class="glyphicon glyphicon-pencil"></span>
                </a>
            </td>
            <td>
                <a href="{{url('/contracts/delete/' . $contract->id)}}" title="Удалить" class="btn btn-default btn-sm">
                    <span class="glyphicon glyphicon-remove"></span>
                </a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@stop