<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="_token" content="<?php echo csrf_token(); ?>">
        <link rel="icon" type="image/png" href="{{asset('images/favicon.png')}}">
        <title>График продаж</title>
        <!--ШРИФТЫ-->
        <style>
            @font-face {
                font-family: 'Ubuntu Condensed';
                font-style: normal;
                font-weight: 400;
                src: local('Ubuntu Condensed'), local('UbuntuCondensed-Regular'), url({{asset('fonts/UbuntuCondensed-Regular.ttf')}}) format('truetype');
            }
        </style>
        <!--СТИЛИ ДЛЯ БИБЛИОТЕК-->
        <link href="{{asset('css/lib-styles/jquery-ui.css')}}" rel="stylesheet" type="text/css"/>
        <link rel="stylesheet" href="{{asset('css/lib-styles/jquery-ui-2.css')}}" type="text/css"/>
        <link rel="stylesheet" href="{{asset('css/lib-styles/bootstrap.min.css')}}" type="text/css">
        <link rel="stylesheet" href="{{asset('css/lib-styles/bootstrap-theme.min.css')}}">
        <link rel="stylesheet" href="{{ asset('css/jquery.kladr.min.css') }}">
        <link href="{{asset('css/lib-styles/suggestions-15.5.css')}}" type="text/css" rel="stylesheet" />
        <link rel="stylesheet" href="{{ asset('css/MooDialog.css') }}">
        <link rel="stylesheet" href="{{ asset('/js/libs/bs-datepicker/css/bootstrap-datepicker3.standalone.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/datatables.bootstrap.css') }}">
        <!--КОНЕЦ: СТИЛИ ДЛЯ БИБЛИОТЕК-->
        <!--ОБЩИЕ СТИЛИ-->
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        <link rel="stylesheet" href="{{ asset('css/form.css') }}">
        <link rel="stylesheet" href="{{ asset('/css/style.css') }}">
        <link rel="stylesheet" href="{{ asset('/css/ajax-loader.css') }}">        
        <!--КОНЕЦ: ОБЩИЕ СТИЛИ-->
        <!--индивидуальные стили для страниц-->
        <link rel="stylesheet" href="{{ asset('css/graph.css?1') }}">
        <!--БИБЛИОТЕКИ-->
        <script src="{{asset('js/libs/cdn/jquery.min.js')}}"></script>
        <script src="{{asset('js/libs/cdn/bootstrap.min.js')}}"></script>
	<script src="{{asset('js/libs/cdn/jquery-UI-1.10.3.custom.min.js')}}"></script>
        <script src="{{asset('js/libs/cdn/jquery-ui.js')}}"></script>
        <script src="{{asset('js/libs/chart.js')}}"></script>
        <script type="text/javascript" src="{{  URL::asset('js/libs/jquery.mask.min.js') }}"></script>
        <script type="text/javascript" src="{{  URL::asset('js/libs/bs-datepicker/js/bootstrap-datepicker.min.js') }}"></script>      
        <script type="text/javascript" src="{{  URL::asset('js/libs/bs-datepicker/locales/bootstrap-datepicker.ru.min.js') }}"></script>
        <!--валидация-->
        <script type="text/javascript" src="{{ asset('js/libs/bs-validator.min.js') }}"></script>
        <!--tinymce-->
        <script type="text/javascript" src="{{ asset('js/libs/tinymce/tinymce.min.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/libs/tinymce/jquery.tinymce.min.js') }}"></script>
        <script type="text/javascript" charset="utf8" src="{{asset('js/libs/cdn/jquery.dataTables.js')}}"></script>
        <script type="text/javascript" src="{{ asset('js/datatables.bootstrap.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/jquery.speller.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/libs/moment-with-locales.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/jquery.mymoney.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/libs/js.cookie.js') }}"></script>
        <!--КОНЕЦ: БИБЛИОТЕКИ-->
        <script>
            $.ajaxSetup({
                headers: {
                        'X-CSRF-Token': $('meta[name="_token"]').attr('content')
                }
            });
        </script>
        <script src="{{URL::asset('js/app.js?2')}}"></script>
    </head>
<body> 
<div class='row'>
    <div class='panel panel-default'>
        <div class="col-md-4" id="mytable">
        </div>
        <div class="col-md-6">
            <div class='panel-body'>
                <div class="contanerChart">
                    <canvas id="popChart" width="600" height="400"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <form method="GET" id="fdate">
                <div id="sandbox-container">
                    <div>
                        <div id="datepicker1" data-date="<?= date('Y-m-d', strtotime('-1 month')); ?>"></div>
                        <input type="hidden" id="my_hidden_input1" name="OldDate" value="<?= date('Y-m-d H:i:s', strtotime('-1 month')); ?>" >
                        <!--button type="submit" class="btn btn-primary btn btn-block" id="BuildGraph">Построить график</button-->
                    </div>
                    <div>
                        <div id="datepicker2" data-date="<?= date('Y-m-d'); ?>"></div>
                        <input type="hidden" id="my_hidden_input2" name="CurDate" value="<?= date('Y-m-d H:i:s'); ?>">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
   function printtable(data) {
       $('#mytable').html(data);
    }
	// Строим график
	var barChart;
	function GraphSales(data) {
		var salesGraph = $("#popChart");
		var salesGraph = document.getElementById("popChart");
		var salesGraph = document.getElementById("popChart").getContext("2d");				
		// массив линий
		var lines = [];
		//массив все точек x
		var allPointsX = [];
		//конфиги линий для chart.js
		var data_sets = [];
		var speedData = {};
		var colors = ['orange','purple','green','red','yellow','blue'];
		var color_index = 0;
		// получаем  json
		//перебираем объекты и формируем нужные массивы
		data.graph.forEach(function (d) {    
			if (lines[d.date]===undefined) {
				lines[d.date]= [{'time': d.time , 'money' : Math.round(d.money/100)}];
			} else {
				lines[d.date].push({'time': d.time , 'money' : Math.round(d.money/100)});
			}
			if (allPointsX.indexOf( d.time ) == -1) {
				allPointsX.push(d.time);
			}		    	             	
		});
		// сортируем по возрастанию времени
		allPointsX.sort();
		for(line_index in lines) {
			// точки по x
			var one_line_values = [];
			var sum = 0;
			for (x in allPointsX) {
					for (y in lines[line_index] ) {
						if (allPointsX[x]==lines[line_index][y].time) {
								sum = sum + lines[line_index][y].money
						}
					}
				one_line_values.push(sum);
			}
			data_sets.push( {
					label: line_index,
					data: one_line_values,
					fill: false,
					borderColor: colors[color_index],
					pointBorderColor: colors[color_index],
					pointBackgroundColor: colors[color_index++],
					pointRadius: 1,
					pointHoverRadius: 5,
					pointHitRadius: 1,
					pointBorderWidth: 1
				}
			);
		}
		speedData.labels = allPointsX;
		speedData.datasets = data_sets;
		//console.log(speedData);
		// Опции для графика
		var chartOptions = {
                    animation : false,
			legend: {
                            display: true,
                            position: 'top',
                            fullWidth: true,
                            labels: {
				boxWidth: 10,
				fontColor: 'black'
                            }
			}
		};
		barChart = new Chart(salesGraph, {
			type: 'line',
			data: speedData,
			options: chartOptions
		});	
	}	
	function updateConfigByMutating(barChart) {
            GraphSales ();
	}
	// функция обновления
	function updatedata() {
		toggleDatepicker(true);
		$.post('/graph/index/graphdata', $('#fdate').serialize()).done(function (data) {
			toggleDatepicker(false);
			GraphSales(data);
			//TablData(data); // передаем данные для построение таблице
			//barChart.update();
                        printtable(data.HTML);
			//console.log('data',$cities1);
		});
	};
	// Конец функция обновления
	function toggleDatepicker(disable) {
		if (disable) {
			$('#sandbox-container').append('<div id="zaglushka"></div>');
		} else {
			$('#zaglushka').remove();
		}
	}
	// сколько то назад
	$('#datepicker1').datepicker({ 
		language: "ru",
		format: 'yyyy-mm-dd',
		//todayBtn: "linked",
		daysOfWeekHighlighted: [0,6]
	});
	$('#datepicker1').on('changeDate', function() {
		$('#my_hidden_input1').val(
			$('#datepicker1').datepicker('getFormattedDate')
		);
		updatedata();// Вызываем обновления даных
	});
	// сегодня
	$('#datepicker2').datepicker({ 
		language: "ru",
		format: 'yyyy-mm-dd',
		todayBtn: "linked",
		daysOfWeekHighlighted: [0,6]
	});
	$('#datepicker2').on('changeDate', function() {
		$('#my_hidden_input2').val(
			$('#datepicker2').datepicker('getFormattedDate')
		);
		updatedata();// Вызываем обновления даных
	});
	$(document).ready(function (){
            updatedata();
            setInterval(function () {
		updatedata();
            }, 900000);
	});
</script>
    
</body>
</html>

