@extends('usertests.usertests_menu')
@section('title') Статистика по тесту @stop
@section('subcontent')
<div class='row'>
    <div class='col-xs-12'>
        <ol class="breadcrumb">
            <li><a href="{{url('usertests/index')}}">Список тестов</a></li>
            <li>{{$test->name}}</li>
        </ol>
        @if(Auth::user()->isAdmin())
        <form>
            <div class="form-group form-inline">
                <label>Специалист</label>
                <input type='hidden' name='user_id' />
                <input type='text' name='user_id_autocomplete' class='form-control' data-autocomplete='users' />
                <button type="submit" class="btn btn-default">OK</button>&nbsp;
                <a href="{{url('usertests/stat/'.$test->id.'?all=1')}}">По всем</a>
                <a href="{{url('usertests/stat/'.$test->id.'?missed=1')}}">Вывести всех, не проходивших тест</a>
            </div>
        </form>
        @endif
        <h2><small>Тест: </small>{{$test->name}}</h2>
        <p>Общий результат: <label class='label label-success' style='font-size: 16px'>{{$total_result}} из {{$questions_num}}</label></p>
        <table class='table table-condensed table-borderless'>
            <tbody>
                <tr>
                    <td>Результаты</td>
                    @foreach($results as $res)
                    <td>
                        {{$res}}/{{$questions_num}}
                    </td>
                    @endforeach
                </tr>
                
                @foreach($questions as $q)
                <?php $qSessionsList = $q->getSessionsList($user); ?>
                <tr class='bg-odd'>
                    <td>
                        {{$q->text}}
                    </td>
                    @foreach($qSessionsList as $sid)
                    <td>
                        @if($q->userAnsweredRight($user,$sid))
                        <label class='label label-success'><span class='glyphicon glyphicon-ok'></span></label>
                        @else
                        <label class='label label-danger'><span class='glyphicon glyphicon-remove'></span></label>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @if(Auth::user()->isAdmin())
                @foreach($q->answers as $a)
                <tr style='font-size: 10px;'>
                    <td>
                        {{$a->text}}
                    </td>
                    @foreach($qSessionsList as $sid)
                    <td class='@if($a->is_right) bg-success @else bg-danger @endif'>
                        @if($a->wasSelected($user->id,$sid,$q->id))
                        <span class='glyphicon glyphicon-ok'></span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@stop