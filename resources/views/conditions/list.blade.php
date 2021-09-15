@extends('app')
@section('title') Список условий @stop

@if(isset($msg))
<div class="{{$class}}">{{$msg}}</div>
@endif

@section('content')
<a href="/conditions/create" class="btn btn-default pull-left"><span class="glyphicon glyphicon-plus"></span> Создать</a>
<table class="table">
    <thead>
        <tr>
            <th>Наименование</th>
            <th>Условие</th>
            <th></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach ($conditions as $cond)
        <tr>
            <td>{{ $cond->name }}</td>
            <td>{{ $cond->condition }}</td>
            <td>
                <a href="/conditions/edit/{{$cond->id}}" class="btn btn-default">
                    <span class="glyphicon glyphicon-pencil"></span>
                </a>
            </td>
            <td>
                <a href="/conditions/delete/{{$cond->id}}" class="btn btn-default">
                    <span class="glyphicon glyphicon-remove"></span>
                </a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@stop