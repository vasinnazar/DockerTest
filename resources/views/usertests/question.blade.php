@extends('usertests.usertests_menu')
@section('title') @if(!is_null($cur_question)) {{$cur_question->text}} @else Вопрос @endif @stop
@section('subcontent')
<div class="row">
    <div class="col-xs-12">
        <div class="progress">
            <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" aria-valuenow="{{$test_completion}}" aria-valuemin="0" aria-valuemax="100" style="width: {{$test_completion}}%">
                <span class="sr-only">{{$test_completion}}%</span>
            </div>
        </div>
    </div>
</div>
<div class='row'>
    <div class="col-xs-12">
        <h3><span class='label label-warning'>В каждом вопросе может быть несколько правильных вариантов ответов</span></h3>
    </div>
</div>
<div class='row'>
    <div class='col-xs-12'>
        {!! Form::open(['url'=>url('usertests/answer')]) !!}
        {!! Form::hidden('question_id',$cur_question->id) !!}
        {!! Form::hidden('session_id',$session_id) !!}
        {!! Form::hidden('test_id',$cur_question->user_test_id) !!}
        <h2>
            <small>Вопрос:</small><br>
            {{$cur_question->text}}
        </h2>
        <ul class='list-group'>
            @foreach($cur_question->answers as $a)
            <li class='list-group-item'>
                <label>
                    <input name='answer[]' type='checkbox' value='{{$a->id}}'/> 
                    {{$a->text}} 
                </label>
            </li>
            @endforeach
        </ul>
        <button class="btn btn-primary" type="submit">Ответить</button>
        {!! Form::close() !!}
    </div>
</div>
@stop
