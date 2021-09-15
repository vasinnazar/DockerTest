@extends('usertests.usertests_menu')
@section('title') Список тестов @stop
@section('subcontent')

<table class="table table-condensed">
    <thead>
        <tr>
            <th>Тест</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($usertests as $test)
        <tr>
            <td>{{$test->name}}</td>
            <td>
                <a class="btn btn-default" href="{{url('usertests/view/'.$test->id)}}">Начать</a>
                
                <a class="btn btn-default" href="{{url('usertests/stat/'.$test->id)}}">Статистика</a>
                
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@stop