@extends('app')
@section('title') История изменений @stop
@section('content')
<ol class="breadcrumb">
    <li><a href="{{url('debtors/index')}}">Список должников</a></li>
    <li><a href="{{url('debtors/debtorcard/'.$debtor_id)}}">Карточка</a></li>
    <li class="active">История изменений</li>
</ol>
<table class='table table-condensed accordion-table'>
    <thead>
        <tr>
            <th>Дата</th>
            <th>Объект</th>
            <th>Пользователь</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($logs))
        @foreach($logs as $log)
        <tr class='header children-collapsed contract-header-row'>
            <td>
                {{$log->debtor_log_created_at}}
            </td>
            <td>
                @if($log->doctype==0)
                Должник
                @else
                Мероприятие
                @endif
            </td>
            <td>
                {{$log->username}}
            </td>
        </tr>
        @if(!is_null($log->after) || !is_null($log->before))
        <tr>
            <td colspan="3">
                <table class='table table-condensed'>
                    <thead>
                        <tr>
                            <th>Поле</th>
                            <th>До</th>
                            <th>После</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($log->after as $k=>$v)
                        <?php $changedClass = (!is_null($log->before) && isset($log->before->{$k}) && $log->before->{$k} != $v) ? 'bg-warning' : '' ?>
                        <tr class='{{$changedClass}}'>
                            <td>{{\App\DebtorLog::getFieldName($k,$log->doctype)}}</td>
                            <td>
                                @if(!is_null($log->before) && isset($log->before->{$k}))
                                {{ $log->before->{$k} }}
                                @else
                                -
                                @endif
                            </td>
                            <td>{{$v}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
        @else
        <tr>
            <td colspan="3">-</td>
        </tr>
        @endif
        @endforeach
        @else
        <tr>
            <td colspan="3">Нет изменений</td>
        </tr>
        @endif
    </tbody>
</table>
@stop