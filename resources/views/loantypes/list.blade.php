@extends('adminpanel')
@section('title') Список видов займа @stop

@if(isset($msg))
<div class="{{$class}}">{{$msg}}</div>
@endif

@section('subcontent')
<div class="row">
    <div class="col-xs-12 col-md-6 col-lg-6">
        <a href="{{url('/loantypes/create')}}" class="btn btn-default pull-left"><span class="glyphicon glyphicon-plus"></span> Создать вид займа</a>
        <table class="table table-condensed">
            <thead>
                <tr>
                    <th>Наименование</th>
                    <th>Сумма</th>
                    <th>Срок</th>
                    <th>Дата начала</th>
                    <th>Дата завершения</th>
                    <th style="width: 150px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($loanTypes as $type)
                <tr>
                    <td>{{ $type->name }}</td>
                    <td>{{ $type->money }}</td>
                    <td>{{ $type->time }}</td>
                    <td>{{ date_format(date_create($type->start_date), 'd.m.Y') }}</td>
                    <td>{{ date_format(date_create($type->end_date), 'd.m.Y') }}</td>
                    <td>
                        <a href="{{url('/loantypes/edit/'.$type->id)}}" class="btn btn-default btn-sm">
                            <span class="glyphicon glyphicon-pencil"></span>
                        </a>
                        <a href="{{url('/loantypes/delete/'.$type->id)}}" class="btn btn-default btn-sm">
                            <span class="glyphicon glyphicon-remove"></span>
                        </a>
                        <a href="{{url('/loantypes/clone/'.$type->id)}}" class="btn btn-default btn-sm" title="Скопировать">
                            <span class="glyphicon glyphicon-plus"></span>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="col-xs-12 col-md-6 col-lg-6">
        <a href="{{url('/conditions/create')}}" class="btn btn-default pull-left"><span class="glyphicon glyphicon-plus"></span> Создать условие</a>
        <table class="table table-condensed">
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
                        <a href="{{url('/conditions/edit/'.$cond->id)}}" class="btn btn-default btn-sm">
                            <span class="glyphicon glyphicon-pencil"></span>
                        </a>
                    </td>
                    <td>
                        <a href="{{url('/conditions/delete/'.$cond->id)}}" class="btn btn-default btn-sm">
                            <span class="glyphicon glyphicon-remove"></span>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@stop