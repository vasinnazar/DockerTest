@extends('usertests.usertests_menu')
@section('title') Статистика по тесту (не проходившие) @stop
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
                </tr>
            </thead>
            <tbody>
                <?php $last_region = ''; ?>
                @foreach($users as $u)
                @if($last_region != $u['region'])
                <tr>
                    <td class='bg-warning'>{{$u['region']}}</td>
                </tr>
                <?php $last_region = $u['region']?>
                @endif
                <tr>
                    <td>
                        {{$u['name']}}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@stop