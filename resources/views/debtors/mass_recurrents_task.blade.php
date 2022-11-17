@extends('app')
@section('title') Массовый безакцепт {{ ($recurrent_type == 'olv_chief' || $recurrent_type == 'ouv_chief') ? 'Ведущий' : '' }}@stop
@section('content')

<h1>Массовый безакцепт {{ ($recurrent_type == 'olv_chief' || $recurrent_type == 'ouv_chief') ? 'Ведущий' : '' }}</h1>
<input type="hidden" id="recurrent_type" value="{{ $recurrent_type }}">
@if ($canStartToday)
<input type="button" id="startMassRecurrents" class="btn btn-primary" value="Запустить" />
<div id="task_status"></div>
@else
@if ($completed)
<div id="task_status">Задача уже была запущена и выполнена сегодня.</div>
@else
<div id="task_status">Задача создана и находится в обработке.</div>
<div id="task_progress">Обработано договоров: <span id="progress">-</span> из <span id="debtors_count">-</span></div>
@endif
@endif

@stop

@section('scripts')
@if ($canStartToday)
<script>
$(document).ready(function(){
    $(document).on('click', '#startMassRecurrents', function(){
        $(this).hide();
        
        $('#task_status').html('Создание задачи...');
        
        $.ajax({
            url: '/debtor/recurrent/massquerytask',
            method: 'post',
            data: {start: 1, type: $('#recurrent_type').val()},
            success: function(data) {
                var json_data = JSON.parse(data);
                $('#task_status').html('Задача создана. Количество договоров: ' + json_data.debtors_count);
                
                $.ajax({
                    url: '/debtor/recurrent/massquery',
                    method: 'post',
                    data: {task_id: json_data.task_id, recurrent_type: $('#recurrent_type').val()},
                    success: function(answer) {
                        location.reload();
                    }
                });
            }
        });
    });
});
</script>
@else
@if (!$completed)
<script>
$(document).ready(function(){
    function loop() {
        $.ajax({
            url: '/debtor/recurrent/getstatus',
            method: 'post',
            data: {type: $('#recurrent_type').val()},
            success: function(data) {
                var json_data = JSON.parse(data);
                if (json_data.status == 'completed') {
                    location.reload();
                } else {
                    $('#debtors_count').html(json_data.debtors_count);
                    $('#progress').html(json_data.progress);
                    setTimeout(() => { loop(); }, 1000);
                }
            }
        });
    }
    
    loop();
});
</script>
@endif
@endif
@stop