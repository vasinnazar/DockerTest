@extends('app')
@section('title') Редактирование кандидата @stop
@section('css')
<link rel="stylesheet" href="{{asset('js/libs/jqGrid/css/ui.jqgrid.min.css')}}" />
<link rel="stylesheet" href="{{ asset('css/candidate.css') }}">
@stop
@section('content')
<div class='row'>
    <div class="col col-xs-12">
        <div class='panel panel-default'>
            <div class='panel-heading'>
                <h3 class='panel-title'>Редактирование кандидата</h3>
            </div>
            <div class='panel-body'>
				{!!Form::model($candidate, array('url'=>url('candidate/update')))!!}
					<div class="row">
						<div class="col">
							{!!Form::hidden('id',null,['class'=>'myform-control','required'])!!}
						</div>					
						<div class="col">
							ФИО<br/>
							{!!Form::text('fio',null,['class'=>'myform-control','required'])!!}
						</div>
						<div class="col">
							Город<br/>
							{!!Form::hidden('city_id',null,['class'=>'myform-control','value'=>''])!!}
							{!!Form::text('city',null,['class'=>'myform-control','required','autocomplete'=>'off','data-autocomplete'=>'cities'])!!}
						</div>
						<div class="col">
							Телефон кандидата<br/>
							{!!Form::text('tel_candidate',null,['class'=>'myform-control','maxlength'=>'19','required'])!!}
						</div>
						<div class="col">
							Дата звонка<br/>
							{!!Form::text('call_date',null,['class'=>'myform-control','id'=>'datetimepicker'])!!}
						</div>
						<div class="col">
							Дата собеседование<br/> 
							{!!Form::text('interview_date',null,['class'=>'myform-control','id'=>'datetimepicker1'])!!}
						</div>
						<div class="col">
							Дошел/ Не дошел<br/> 
							{!!Form::select('reach',$reach_list, null,['class'=>'myform-control'])!!}
						</div>
						<div class="col">
							Результат собеседования<br/> 
							{!!Form::select('interview_result',$interviewResult_list, null,['class'=>'myform-control'])!!}
						</div>
						<div class="col">
							Решение СБ<br/>
							{!! Form::select('decision',$decision_list, null,['class'=>'myform-control'])!!}
						</div>
                        <div class="col">
							Дата одобрения<br/> 
                            {!!Form::text('approval_date',null,['class'=>'myform-control','id'=>'datetimepicker3'])!!}
						</div>
						<div class="col">
							@if(auth()->user()->hasRole('DepHR_headman'))
								Комментарий руководителя<br/>
								{!!Form::text('comment_ruk',null,['class'=>'myform-control'])!!}
							@else
								Комментарий менеджера<br/>
								{!!Form::text('comment',null,['class'=>'myform-control'])!!}
							@endif
							
						</div>
						<div class="col">
							Выход на стажировку (дата)<br/>
							{!!Form::text('training',null,['class'=>'myform-control','id'=>'datetimepicker2'])!!}
						</div>
						<div class="col">
							Результат по кандидату<br/>
							{!!Form::select('result',$result_list, null,['class'=>'myform-control'])!!}
						</div>
						<div class="col">
							Региональный центр<br/>
							{!!Form::select('region',([''=>'']+$regions_list), null,['class'=>'myform-control'])!!}
						</div>
						<div class="col">
							Куратор <br/>
							{!!Form::text('mentor',null,['class'=>'myform-control'])!!}
						</div>
                                                <div class="col">
                                                    Руководитель <br/>
                                                    {!!Form::select('headman',($roles_list), null,['class'=>'myform-control'])!!}
                                                </div>
                                                <div class="col">
                                                    Ответственный <br/>
                                                    {!!Form::text('responsible',null,['class'=>'myform-control','readonly'=>'readonly'])!!}
                                                </div>  
						<div class="col">
							<button id="btn-create" class="btn btn-primary pull-right btn-xs" type="submit" value="create">Изменить</button>
						</div>	
					</div>
				{!!Form::close()!!}
			</div>
        </div>
    </div>
</div>
@stop
@section('scripts')
<script src="{{asset('js/libs/bootstrap-datetimepicker/js/bootstrap-datetimepicker.js')}}"></script>
<script>
    $(function () {
        $('#datetimepicker').datetimepicker({
			format: 'YYYY-MM-DD HH:mm:ss',
            locale: 'ru'
        });
		$('#datetimepicker1').datetimepicker({
			format: 'YYYY-MM-DD HH:mm:ss',
            locale: 'ru'
        });
		$('#datetimepicker2').datetimepicker({
			format: 'YYYY-MM-DD HH:mm:ss',
            locale: 'ru'
        });
                $('#datetimepicker3').datetimepicker({
			format: 'YYYY-MM-DD HH:mm:ss',
            locale: 'ru'
        });
    });
	
$(document).ready(function() {
	$('[name=tel_candidate]').bind("change keyup input click", function() {
		if (this.value.match(/[^0-9]/g)) {
			this.value = this.value.replace(/[^0-9]/g, '');
		}
	});
	$('select[name=decision]').click(function(){
		if ( ($('[name=decision]').val()==1) || ($('[name=decision]').val()==2)) {
		$('#datetimepicker3').val(moment().format('YYYY-MM-DD HH:mm:ss'));
		}
	}); 
	$('[name=training]').click(function(){
        if  ($('[name=training]').val()!='') {
           $('[name=mentor]').attr('required', true);
        } else {
			$('[name=mentor]').attr('required', false);
		}
    });		
});	
</script> 

@stop
