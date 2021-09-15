@extends('reports.reports')
@section('title') Отчет по отсутствующим подразделениям @stop
@section('subcontent')
<table class='table table-condensed'>
    <thead>
        <tr>
            <th>Подразделение</th>
            <th>Код</th>
        </tr>
    </thead>
    <tbody>
        @foreach($subdivisions as $s)
        <tr>
            <td>{{$s->name}}</td>
            <td>{{$s->name_id}}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@stop