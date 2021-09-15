@extends('app')
@section('title') Создание кандидата @stop
@section('css')
<link rel="stylesheet" href="{{asset('js/libs/jqGrid/css/ui.jqgrid.min.css')}}" />
<link rel="stylesheet" href="{{ asset('css/candidate.css') }}">
@stop
@section('content')
<div class='row'>
    <div class="col col-xs-12">
        <div class='panel panel-default'>
            <div class='panel-heading'>
                <h3 class='panel-title'>Создание кандидата</h3>
            </div>
            <div class='panel-body'>
				{!!Form::model($candidate, array('url'=>url('candidate/insert')))!!}
					<div class="row">
						<div class="col">
							ФИО<br/>
							<input class="myform-control" type="text" name="fio" required >
						</div>
						<div class="col">
							Город<br/>
							<input type="hidden" name="city_id" value="">
							<input class="myform-control" type="text" name="city" required data-autocomplete="cities" autocomplete="off">
						</div>
						<div class="col">
							Телефон кандидата<br/>
							<input class="myform-control" type="text" maxlength="19" name="tel_candidate" required >
						</div>
						<div class="col">
							Дата звонка<br/>
							<input type="text" class="myform-control" id="datetimepicker" name="call_date"/>
						</div>
						<div class="col">
							Дата собеседование<br/> 
							<input class="myform-control" id="datetimepicker1" type="text" name="interview_date">
						</div>
						<div class="col">
							Дошел/ Не дошел<br/> 
							<select class="myform-control" name="reach">
								@foreach($reach_list as $reach_key => $reach_value)
									<option value="{{$reach_key}}">
										{{$reach_value}}
									</option>
								@endforeach
							</select>
						</div>
						<th>Результат собеседования<br/> 
                             <select class="myform-control" name="interview_result">
                                 <option value="">
                                     Не выбранно
                                 </option>
                                 @foreach($interviewResult_list as $interviewResult_key => $interviewResult_value)
                                 <option value="{{$interviewResult_key}}">
                                     {{$interviewResult_value}}
                                 </option>
                                 @endforeach
                             </select>
                         </th>
						<div class="col">
							Решение СБ<br/>
							<select class="myform-control" name="decision"> 
								@foreach($decision_list as $decision_key => $decision_value)
									<option value="{{$decision_key}}">
										{{$decision_value}}
									</option>
								@endforeach
							</select>
						</div>
                                                <div class="col">
							Дата одобрения<br/> 
							<input class="myform-control" id="datetimepicker3" type="text" name="approval_date">
						</div>
						<div class="col">
							<?php //dd(auth()->user()->hasRole); ?>
							@if(auth()->user()->hasRole('DepHR_headman'))
								Комментарий руководителя<br/>
								<input class="myform-control" type="text" name="comment_ruk"/>
							@else
								Комментарий менеджера<br/>
								<input class="myform-control" type="text" name="comment"/>
							@endif
						</div>
						<div class="col">
							Выход на стажировку (дата)<br/> 
							<input class="myform-control" id="datetimepicker2" type="text" name="training">
						</div>
						<div class="col">
							Результат по кандидату<br/>
							<select class="myform-control" name="result"> 
								<option value="">
								</option>
								@foreach($result_list as $result_key => $result_value)
									<option value="{{$result_key}}">
										{{$result_value}}
									</option>
								@endforeach
							</select>
						</div>
						<div class="col">
							Региональный центр<br/>
								<select class="myform-control" name="region"> 
									<option value="">
									</option>
									@foreach($regions_list as $region_key => $region_value)
										<option value="{{$region_key}}">
											{{$region_value}}
										</option>
									@endforeach
								</select>
						</div>
						<div class="col">
							Куратор<br/> <input class="myform-control" type="text" name="mentor">
						</div>
                                                <div class="col">
                                                    Руководитель<br/>
                                                    <select class="myform-control" name="headman"> 
							<option value=""></option>
							@foreach($roles_list as $role_key => $role_value)
                                                            <option value="{{$role_key}}">
								{{$role_value}}
                                                            </option>
							@endforeach
                                                    </select>
                                                </div>
                                                <div class="col">
                                                    Ответственный<br/>
                                                    <input class="myform-control" type="text" name="responsible" readonly value="{{Auth::user()->name}}"> 
                                                </div>             
                                                <div class="col">
							<button id="btn-create" class="btn btn-primary pull-right btn-xs" type="submit" value="create">Создать</button>
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
