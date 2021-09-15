@extends('app')
@section('title') Прочие контакты @stop
@section('content')
<ol class="breadcrumb">
    <li><a href="{{url('debtors/index')}}">Список должников</a></li>
    <li><a href="{{url('debtors/debtorcard/'.$debtor_id)}}">Карточка</a></li>
    <li class="active">Прочие контакты</li>
</ol>
<table class='table table-condensed'>
    <thead>
        <tr>
            <th>Дата заявки</th>
            <th>Телефоны</th>
        </tr>
    </thead>
    <tbody>
        @foreach($contacts as $key => $con)
        <tr>
            <td>{{$con['claim_date']['value']}}</td>
            <td>
                <ul class='list-group'>
                    @foreach($con as $k=>$v)
                    @if($k!='claim_date')
                    <li class='list-group-item'>
                        <table>
                            <tr>
                                <td style="border-right: 1px #000000 solid; padding-right: 20px; width: 250px; text-align: right;">{{$v['name']}}</td>
                                <td style="padding-left: 20px;">
                                    @if(empty($v['value']))
                                    -
                                    @else
                                    {{$v['value']}}
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </li>
                    @endif
                    @endforeach
                </ul>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@stop