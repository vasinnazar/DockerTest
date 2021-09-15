@extends('usertests.usertests_menu')
@section('title') Статистика по тесту @stop
@section('subcontent')
<div class='row'>
    <div class='col-xs-12'>
        <ol class="breadcrumb hidden-print">
            <li><a href="{{url('usertests/index')}}">Список тестов</a></li>
            <li>{{$test->name}}</li>
        </ol>
        <h2><small>Тест: </small>{{$test->name}}</h2>
        <table class='table table-condensed table-borderless'>
            <thead>
                <tr>
                    <th>ФИО</th>
                    <th>Отвечено</th>
                    <th>Кол-во попыток</th>
                </tr>
            </thead>
            <tbody>
                <?php $last_region = ''; ?>
                @foreach($users as $u)
                @if($last_region != $u['region'])
                <tr>
                    <td colspan="3" class='bg-warning'>{{$u['region']}}</td>
                </tr>
                <?php $last_region = $u['region']?>
                @endif
                <tr>
                    <td>
                        {{$u['name']}}
                    </td>
                    <td>
                        @if($u['answered_num']<=5)
                        {{$u['answered_num']}}
                        @elseif(($u['answered_num']+6)>30)
                        30
                        @else
                        {{$u['answered_num']+6}}
                        @endif
                    </td>
                    <td>
                        {{$u['sessions_num']}}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@stop