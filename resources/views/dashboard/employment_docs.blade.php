@extends('app')
@section('title') Документы на трудоустройство @stop
@section('content')
<div class='container'>
    <div class='row'>
        <div class='col-xs-12'>
            @if(!is_null(Auth::user()) && is_null(Auth::user()->birth_date))
            <div class='form-group'>
                {!! Form::open(['url'=>url('employment/user/update')])!!}
                <label>Дата рождения</label>
                <input type='date' name='birth_date' class='form-control'/>
                <br>
                <button class='btn btn-primary pull-right' type='submit'>Далее</button>
                {!! Form::close() !!}
            </div>
            @else
            <div style='text-align: center'>
                Уважаемый коллега!
                ФинТерра приветствует тебя!
                До того, как ты приступишь к работе, тебе необходимо заполнить  и  подписать документы для трудоустройства.
                Инструкцию ты найдешь ниже.
            </div>
            <p style="text-align: center"><a href="{{$manual['url']}}" target="_blank" class="btn btn-success btn-lg"><span class='glyphicon glyphicon-print'></span> Открыть инструкцию</a></p>
            <ul class='list-group'>
                @foreach($contracts as $contract)
                <li class='list-group-item'>
                    <a href='{{$contract['url']}}' class='btn btn-success' target="_blank"><span class='glyphicon glyphicon-print'></span> Распечатать</a>&nbsp;
                    @if($contract['printed'])
                    <strike>{{$contract['label']}}</strike>
                    @else
                    {{$contract['label']}}
                    @endif
                </li>
                @endforeach
            </ul>
            @if(empty(Auth::user()->employment_agree) || Auth::user()->employment_agree == '0000-00-00 00:00:00')
            {!! Form::open(['url'=>url('employment/docs/signed')])!!}
            {!! Form::checkbox('employment_agree', 1, null, ['class' => 'checkbox', 'id' => 'employmentAgree']) !!}
            <label for="employmentAgree" >Подтверждаю, что мною будут отправлены документы и предоставлен трек-номер почты России</label>
            <br>
            <button class='btn btn-primary pull-right' id='employmentSignedFormSubmitBtn' onclick="$.app.blockScreen(true);">Сохранить</button>
            {!! Form::close() !!}
            @endif
            @endif
        </div>
    </div>
</div>
@stop
@section('scripts')
<script>
    $(document).ready(function () {
        $('#employmentAgree').change(function () {
            $('#employmentSignedFormSubmitBtn').prop('disabled', !$('#employmentAgree').prop('checked'));
        });
        $('#employmentAgree').change();
    });
</script>
@stop