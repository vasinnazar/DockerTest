@extends('app')
@section('title') Опрос @stop
@section('content')
{!! Form::model($quizDeptModel,['url' => 'quizdept/store','id'=>'quizDepartmentsForm']) !!}
<div class="container">
    <div class='row'>
        <div class='col-xs-12'>
            Уважаемые коллеги!<br>
            В  целях  обеспечения вашей эффективной работы и создания комфортных 
            условий труда нам  бы хотелось узнать как происходит ваше 
            взаимодействие со всеми отделениями компании, какие проблемы Вас волнуют.
            Мнение каждого из вас играет большую роль в формировании благоприятного климата в компании. 
            Предлагаем  уделить вам 5 минут времени и ответить на предложенные ниже вопросы, вы 
            можете не переживать, данные опроса конфиденциальны.<br>
            P.S. если у вас есть отдельные пожелания/замечания/предложения, 
            то направляйте их на почту <a href='mailto:glspp@pdengi.ru'>glspp@pdengi.ru</a> с пометкой "жизнь в Финтерре"
            <br><br>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class='row'>
                <div class='col-xs-12'>
                    <label>ФИО Вашего руководителя</label>
                    {!! Form::select('fio_ruk',$directors,null,['class'=>'form-control']) !!}
                </div>
            </div>
            <div class='row'>
                <div class='col-xs-12'>
                    <label>ФИО вашего старшего специалиста</label>
                    <input name='fio_star_spec' class='form-control' maxlength="512"/>
                </div>
            </div>
            <hr>
            @foreach($quizDeptModelYesNoFields as $k=>$v)
            <div class='row'>
                <div class='col-xs-12 col-lg-6'>
                    <label>{{$v}}</label>
                </div>
                <div class='col-xs-12 col-lg-2 centered'>
                    <div class="btn-group" data-toggle="buttons">
                        <label class="btn btn-default active">
                            Да{!! Form::radio($k,'1',true) !!}
                        </label>
                        <label class="btn btn-default">
                            Нет{!! Form::radio($k,'0',false) !!}
                        </label>
                    </div>
                </div>
                <div class='col-xs-12 col-lg-4 centered'>
                    <input name='{{$k}}_comment' type='text' class='form-control' placeholder="Ваш вариант (ваши замечания, предложения)" maxlength="512"/>
                </div>
                <br>
                <br>
            </div>
            @endforeach
        </div>
    </div>
    <hr>
    <div class='row'>
        <div class="col-xs-12">
            <button class="btn btn-primary pull-right" type="submit">Сохранить</button>
        </div>
    </div>
</div>
{!! Form::close() !!}
@stop