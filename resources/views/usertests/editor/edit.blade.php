@extends('app')
@section('title') Редактирование теста @stop
@section('content')

{!! Form::model($usertest,['url'=>url('usertests/editor/update'),'id'=>'usertestEditorForm']) !!}
{!!Form::hidden('id')!!}
@if(isset($json))
<input name='questions' type='hidden' value="{{$json}}"/>
@else
<input name='questions' type='hidden'/>
@endif

<div class="row">
    <div class="col-xs-12">
        <div class='form-group'>
            <label>Название теста</label>
            {!!Form::text('name',null,['class'=>'form-control'])!!}
        </div>
    </div>
</div>
{!! Form::close() !!}
<div class="row">
    <div class="col-xs-12" id="userTestQuestionsHolder">
    </div>
    <div class="col-xs-12">
        <div class="panel panel-default hidden question" id="userTestQuestionTemplate">
            <input type="hidden" name="id"/>
            <div class="panel-heading">
                <table style="width: 100%;">
                    <tr>
                        <td class='text-left'>
                            <span class="question-label"></span>
                        </td>
                        <td class="text-right">
                            <button type="button" class="btn btn-default btn-sm" name="remove" title="Удалить"><span class="glyphicon glyphicon-trash"></span></button>
                            <button type="button" class="btn btn-default btn-sm" name="toggle" title="Свернуть"><span class="glyphicon glyphicon-chevron-up"></span></button>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label>Вопрос:</label>
                    <textarea name="text" class="form-control"></textarea>
                </div>
                <p>Варианты ответов:</p>
                <div class="panel panel-default answers-holder">
                    <ul class="list-group answers-list">
                        <li class="list-group-item answer">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <input type="checkbox" name="is_right" value="1">
                                </span>
                                <input type="hidden" name="id">
                                <input type="text" class="form-control" name="text">
                                <span class="input-group-btn">
                                    <button class='btn btn-default' name='remove_answer'>
                                        <span class='glyphicon glyphicon-trash'></span>
                                    </button>
                                </span>
                            </div>
                        </li>
                    </ul>
                    <div class="panel-footer text-right">
                        <button type="button" class="btn btn-default" onclick="$.utEditorCtrl.addAnswerBtnClick(this);
                                return false;"><span class="glyphicon glyphicon-plus"></span> Добавить вариант ответа</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xs-12 text-right">
        <button type="button" class="btn btn-default" onclick="$.utEditorCtrl.addQuestion();
                return false;"><span class="glyphicon glyphicon-plus"></span> Добавить вопрос</button>
        <button class='btn btn-default' onclick='$("#usertestEditorForm").submit()'><span class="glyphicon glyphicon-save"></span> Сохранить</button>
    </div>
</div>
@stop
@section('scripts')
<script src="{{asset('js/common/DataGridController.js')}}"></script>
<script src="{{asset('js/usertests/userTestEditorController.js')}}"></script>
<script>
            (function ($) {
                $.utEditorCtrl.init();
            })(jQuery);
</script>
@stop