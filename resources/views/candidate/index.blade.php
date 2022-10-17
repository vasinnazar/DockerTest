@extends('app')
@section('title') Список кандидатов @stop
@section('css')
<link rel="stylesheet" href="{{ asset('js/libs/jqGrid/css/ui.jqgrid.min.css')}}" />
<link rel="stylesheet" href="{{ asset('js/libs/bootstrap-select.min.css')}}" />
<link rel="stylesheet" href="{{ asset('css/candidate.css') }}">
<script src="{{asset('js/libs/sheep/Sheep_js.js')}}"></script>
<script src="{{asset('js/libs/sheep/build/sheep.min.js')}}"></script>

<script>
  /*var sheep;
  var addSheep = function() {
       var sh = new Sheep({
            floors: "window, .panel-default, .table-bordered, .yellowLine, .greenLine, .redLine, .alert-danger, .pagination"
      });
      if (!sheep) {
        sheep = sh;
      }
  };

 window.setTimeout(function(){
  addSheep();
  }, 30000); *//* добавить функцию к объекту onclick="addSheep();"*/
</script>
<script src="{{asset('js/libs/cdn/jquery.min.js')}}"></script>
<script type="text/javascript" src="{{asset('js/claim/claimController.js')}}"></script>
@stop
@section('content')
<div class='row'>
    <div class="col col-xs-12">
        <div class='panel panel-default'>
            <div class='panel-heading'>
                <h3 class='panel-title' onclick="addSheep();">
					Кандидаты
                </h3>
            </div>
            <div class='panel-body'>
                <form method="GET">
                    <?php echo $candidate_list->appends(Request::input())->render(); ?>
                    <div style="float:right">
						@if(!is_null(Auth::user()) && Auth::user()->hasRole('DepHR_director'))
                        <button class="btn btn-primary pull-right btn-xs" id="btn-modal" type="button" data-toggle="modal" data-target="#candidateReportModal"><span class='glyphicon glyphicon-download-alt'></span> Отчет</button>
                        @endif
						
                        <button class="btn btn-primary pull-right btn-xs" id="btn-excel" type="submit" value="1" name="toExcel"><span class='glyphicon glyphicon-export'></span> В Excel</button>
						<button class="btn btn-primary pull-right btn-xs" id="btn-modal2" type="button" data-toggle="modal" data-target="#candidateEventsSearchModal"><span class='glyphicon glyphicon-download-alt'></span> Поиск</button>
                        <a href="{{url('candidate/create')}}"  id="btn-create" class="btn btn-primary pull-right btn-xs">Создать</a>
                    </div>	
                    <table class='table table-condensed table-bordered' id='candidateTable'>
                        <thead>
                            <tr>
                                <th style="width: 20px; vertical-align: middle; background-color: aliceblue;">id</th>
                                <th style="vertical-align: middle; background-color: aliceblue;">ФИО</th>
                                <th style="vertical-align: middle; background-color: aliceblue;">Город</th>
                                <th style="width: 95px; vertical-align: middle; background-color: aliceblue;">Телефон кандидата</th>
                                <th style="vertical-align: middle; background-color: aliceblue;"> Дата звонка</th>
                                <th style="vertical-align: middle; background-color: aliceblue;">Дата собеседование</th>
                                <th style="vertical-align: middle; background-color: aliceblue;">Дошел/ Не дошел<br/></th>
								<th style="vertical-align: middle; background-color: aliceblue;">Результат собеседования</th>
                                <th style="width: 115px; vertical-align: middle; background-color: aliceblue;">Решение СБ</th>
                                <th style="vertical-align: middle; background-color: aliceblue;">Дата одобрения</th>
                                <th style="vertical-align: middle; background-color: aliceblue;">Комментарий менеджера</th>
                                <th style="vertical-align: middle; background-color: aliceblue;">Выход на стажировку (дата)</th>
                                <th style="vertical-align: middle; background-color: aliceblue;">Результат по кандидату</th>
                                <th style="vertical-align: middle; background-color: aliceblue;">Комментарий руководителя</th>
                                <th style="vertical-align: middle; background-color: aliceblue;">Куратор</th>
                                <th style="vertical-align: middle; background-color: aliceblue;">Руководитель</th>
                                <th style="vertical-align: middle; background-color: aliceblue;">Ответственный</th>
                                <th style="vertical-align: middle; background-color: aliceblue;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($candidate_list as $candidate)
                            <?php
                                foreach($decision_list as $decision_key => $decision_value) {
                                    if($decision_key == $candidate['decision']) {
                                        $candDecision = $decision_value;
                                    }				
                                }
								
								foreach($result_list as $result_key => $result_value) {
									if($result_key == $candidate['result']) {
										$candResult = $result_value;
									}
                                }
								
                                $date1 = \Carbon\Carbon::now();
								
								if ($candidate['approval_date']!="") { //Дата одобрения
                                    $date2 = with(new \Carbon\Carbon($candidate['approval_date']));
                                } else{
                                    $date2 = \Carbon\Carbon::yesterday();
                                }

                                //$date3 = $date1->diffInDays($date2); // разница в днях
								$date3 = with(new \Carbon\Carbon($date2))->addDay(1)->setTime(12,0,0);
								
								if($candResult=='Отказ кандидата' or $candResult=='Отказ руководителя') {
									echo '<tr>';
								} elseif (($candDecision=="Одобрено" or $candDecision=="Одобрение при согласовании руководителя" or $candDecision=="Одобрение при согласовании руководителя СБ") and ($candResult!='' or !empty($candResult)) and $candidate['mentor']=="" and ($candidate['training']=="" or $candidate['training']=="0000-00-00 00:00:00")) {
									echo '<tr">';
								} elseif (($candDecision=="Одобрено"or $candDecision=="Одобрение при согласовании руководителя" or $candDecision=="Одобрение при согласовании руководителя СБ") and ($candidate['training']!="" or $candidate['training']!="0000-00-00 00:00:00") and $candidate['mentor']!="") {
									echo '<tr class="greenLine">';
								} elseif (($candDecision=="Одобрено" or $candDecision=="Одобрение при согласовании руководителя" or $candDecision=="Одобрение при согласовании руководителя СБ") and $date1 > $date3) {
									echo '<tr class="redLine">';
								} elseif ($candDecision=="Одобрено" or $candDecision=="Одобрение при согласовании руководителя" or $candDecision=="Одобрение при согласовании руководителя СБ") {
									echo '<tr class="yellowLine">';
                                } else {
                                    echo '<tr>';
                                }
								
                            ?>
                                <th>{{$candidate['id']}}</th>
                                <th>{{$candidate['fio']}}</th>
                                <th>{{$candidate['city']}}</th>
                                <th>{{$candidate['tel_candidate']}}</th>
                                <th><?php echo date("d.m.Y H:i", strtotime($candidate['call_date']));?></th>
                                <th><?php echo date("d.m.Y H:i", strtotime($candidate['interview_date']));?></th>
                                <th>
                                    @foreach($reach_list as $reach_key => $reach_value)
                                        @if($reach_key == $candidate['reach'])
                                            {{$reach_value}}
                                        @endif
                                    @endforeach								
                                </th>
								<th>
                                    @foreach($interviewResult_list as $interviewResult_key => $interviewResult_value)
                                        @if($interviewResult_key == $candidate['interview_result'])
                                            {{$interviewResult_value}}
                                        @endif
                                    @endforeach								
                                </th>
                                <th>
                                    {{$candDecision}}								
                                </th>
                                <th><?php if ($candidate['approval_date']=='0000-00-00 00:00:00') {
											echo '';
										} else {
											echo date("d.m.Y H:i", strtotime($candidate['approval_date']));
										}
									?>
								</th>
                                <th>{{$candidate['comment']}}</th>
                                <th>
									<?php if ($candidate['training']=='0000-00-00 00:00:00') {
											echo '';
										} else {
											echo date("d.m.Y H:i", strtotime($candidate['training']));
										}
									?>
								 </th>
								<th>
									{{$candResult}}
                                </th>
                                <th>
									{{$candidate['comment_ruk']}}
                                </th>
                                <th>{{$candidate['mentor']}}</th>
                                <th>
                                    
										<?php
											$respUser = \App\User::find($candidate['headman']);
											if (!is_null($respUser)) {
												$respUserName = $respUser->name;
											} else {
												$respUserName = 'не определено';
											}
										?>
									{{ $respUserName }}
                                </th>
                                <th>{{$candidate['responsible']}}</th>
                                <th><a href="{{url('candidate/delete?id='.$candidate['id'])}}" class="btn btn-primary btn-xs tbl-btn" onclick="return confirmDelete();"><span class='glyphicon glyphicon-trash tbl-btn'></span></a><a href="{{url('candidate/update?id='.$candidate['id'])}}" class="btn btn-primary btn-xs tbl-btn" target="_blank"><span class='glyphicon glyphicon-edit tbl-btn'></span></a></th>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="candidateReportModal" tabindex="-1" role="dialog" aria-labelledby="debtorsReportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="debtorsFilter">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="debtorsReportModalLabel">Выберите ДАТУ ЗВОНКА</h4>
            </div>
            {!!Form::open(['route'=>'candidate.excel.report','method'=>'post','id'=>'reportAll'])!!}
            <div class="modal-body candidatewindow">
                <div class="row">
                    <div class="col-xs-12">
                        <table class='table table-borderless'>
                            <tr>
                                <td>
									С <input type="text" class="myform-control" id="datetimepicker10" name="start_date"/>
								</td>
                                <td>
                                    ПО <input type="text" class="myform-control" id="datetimepicker11" name="end_date"/>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                {!!Form::button('Отчет',['class'=>'btn btn-primary','type'=>'submit', 'id'=>'btn-excel2','value'=>'1','name'=>'reportToExcel'])!!}
				{!!Form::button('Города',['class'=>'btn btn-primary','type'=>'submit', 'id'=>'btn-excel3','value'=>'1','name'=>'reportCity'])!!}
            </div>
            {!!Form::close()!!}
        </div>
    </div>
</div>
<div class="modal fade" id="candidateEventsSearchModal" tabindex="-1" role="dialog" aria-labelledby="candidateEventsSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="candidateEventsFilter">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="debtorEventsSearchModalLabel">Введите данные для поиска</h4>
            </div>
            {!!Form::open(['method'=>'get'])!!}
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <table class='table table-borderless' id='debtorEventsFilter'>
							<tr>
								<td>id</td>
                                <td colspan="2"><input type="text" class="myform-control" name="id"/></td>
                            </tr>
							<tr>
								<td>ФИО</td>
                                <td colspan="2"><input type="text" class="myform-control" name="fio"/></td>
                            </tr>
							<tr>
								<td>Город</td>
                                <td colspan="2"><input type="text" class="myform-control" name="city"/></td>
                            </tr>
							<tr>
								<td>Телефон кандидата</td>
                                <td colspan="2"><input type="text" class="myform-control" name="tel_candidate"/></td>
                            </tr>
							<tr>
								<td>Дата звонка</td>
                                <td>с <input type="text" class="myform-control" id="datetimepicker20" name="call_date_start"/></td>
								<td>по <input type="text" class="myform-control" id="datetimepicker21" name="call_date_end"/></td>
                            </tr>
							<tr>
								<td>Дата собеседование</td>
                                <td>с <input type="text" class="myform-control" id="datetimepicker22" name="interview_date_start"/></td>
								<td>по <input type="text" class="myform-control" id="datetimepicker23" name="interview_date_end"/></td>
                            </tr>
							<tr>
								<td>Дошел/ Не дошел</td> 
								<td colspan="2">
									<select class="selectpicker" name="reach[]" multiple multiple data-actions-box="true">
                                       @foreach($reach_list as $reach_key => $reach_value)
                                       <option value="{{$reach_key}}">
                                           {{$reach_value}}
                                       </option>
                                       @endforeach
                                   </select>
								 </td>
                             </tr>
							 <tr>
								<td>Результат собеседования</td> 
								<td colspan="2">
                                    <select class="selectpicker" name="interview_result[]" multiple multiple data-actions-box="true">
                                        @foreach($interviewResult_list as $interviewResult_key => $interviewResult_value)
                                        <option value="{{$interviewResult_key}}">
                                            {{$interviewResult_value}}
                                        </option>
                                        @endforeach
                                    </select>
								</td>
                             </tr>
							 <tr>
                                <td>Решение СБ</td>
								<td colspan="2">
                                    <select class="selectpicker" name="decision[]" multiple multiple data-actions-box="true"> 
                                        @foreach($decision_list as $decision_key => $decision_value)
                                        <option value="{{$decision_key}}">
                                            {{$decision_value}}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                             </tr>
							 <tr>
                                <td>Дата одобрения</td> 
								<td>с <input type="text" class="myform-control" id="datetimepicker24" name="approval_date_start"/></td>
								<td>по <input type="text" class="myform-control" id="datetimepicker25" name="approval_date_end"/></td>
                             </tr>
							 <tr>
                                <td>Комментарий менеджера</td> 
								<td colspan="2"><input class="myform-control" type="text" name="comment"/></td>
							 </tr>
							 <tr>
                                <td>Комментарий руководителя</td> 
								<td colspan="2"><input class="myform-control" type="text" name="comment_ruk"/></td>
							 </tr>
							 <tr>	
                                <td>Выход на стажировку (дата)</td>
								<td>с <input type="text" class="myform-control" id="datetimepicker26" name="training_date_start"/></td>
								<td>по <input type="text" class="myform-control" id="datetimepicker27" name="training_date_end"/></td>
                             </tr>	
							 <tr>
                                <td>Результат по кандидату</td>
								<td colspan="2">
									<select class="selectpicker" name="result[]" multiple multiple data-actions-box="true">
                                        @foreach($result_list as $result_key => $result_value)
                                        <option value="{{$result_key}}">
                                            {{$result_value}}
                                        </option>
                                        @endforeach
                                    </select>
								</td>
							 </tr>	
							 <tr>
                                <td>РЦ</td>
								<td colspan="2">
									<select class="selectpicker" name="region[]" multiple multiple data-actions-box="true">
                                        <option value="">
                                        </option>
                                        @foreach($regions_list as $region_key => $region_value)
                                        <option value="{{$region_key}}">
                                            {{$region_value}}
                                        </option>
                                        @endforeach
                                    </select>
								</td>
							</tr>	
							<tr>
                                <td>Куратор</td> 
								<td colspan="2"><input class="myform-control" type="text" name="mentor"></td>
							</tr>	
							<tr>	
								<td>Руководитель</td>
								<td colspan="2">
                                    <select class="selectpicker" name="headman[]" multiple multiple data-actions-box="true">
                                        <option value="">
                                        </option>
                                        @foreach($roles_list as $role_key => $role_value)
                                        <option value="{{$role_key}}">
                                            {{$role_value}}
                                        </option>
                                        @endforeach
                                    </select>
								</td>
							</tr>	
							<tr>
                                <td>Ответственный</td> 
								<td colspan="2"><input class="myform-control" type="text" name="responsible"></td>
                            </tr>
						</table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                {!!Form::button('Очистить фильтр',['class'=>'btn btn-default','type'=>'button', 'id'=>'debtorEventsClearFilterBtn'])!!}
                {!!Form::button('Найти',['class'=>'btn btn-primary','type'=>'submit', 'id'=>'candidateEventsFilterBtn'])!!}
            </div>
            {!!Form::close()!!}
        </div>
    </div>
</div>

@stop
@section('scripts')
<script src="{{asset('js/libs/bootstrap-select.min.js')}}"></script>
<script type="text/javascript" src="{{ asset('js/libs/bootstrap-datetimepicker/js/bootstrap-datetimepicker.js') }}"></script>
<script>
$(function () {
    $('#datetimepicker').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'ru'
    });
    $('#datetimepicker1').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'ru'
    });
    $('#datetimepicker2').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'ru'
    });
    $('#datetimepicker3').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'ru'
    });
	$('#datetimepicker10').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'ru'
    });
	$('#datetimepicker11').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'ru'
    });
	$('#datetimepicker20').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'ru'
    });
	$('#datetimepicker21').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'ru'
    });
	$('#datetimepicker22').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'ru'
    });
	$('#datetimepicker23').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'ru'
    });
	$('#datetimepicker24').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'ru'
    });
	$('#datetimepicker25').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'ru'
    });
	$('#datetimepicker26').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'ru'
    });
	$('#datetimepicker27').datetimepicker({
        format: 'YYYY-MM-DD',
        locale: 'ru'
    });
	$('#btn-excel3').click(function(){
		$('#reportAll').attr('action', $.app.url+'/candidate/excel/report/city');
     });
	$('#btn-excel2').click(function(){
		$('#reportAll').attr('action', $.app.url+'/candidate/excel/report');
    });
});

function confirmDelete() {
    if (confirm("Вы подтверждаете удаление?")) {
        return true;
    } else {
        return false;
    }
}
</script>

@stop
