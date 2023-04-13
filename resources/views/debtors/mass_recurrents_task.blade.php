@extends('app')
@section('title')
    Массовый безакцепт
@stop
@section('content')

    <h1>Массовый безакцепт
        @if ($str_podr == '000000000006-1')
            - старший УПР
        @elseif ($str_podr == '000000000007-1')
            - ведущий УДР
        @endif
    </h1>
    <input type="hidden" id="str_podr" value="{{ $str_podr }}">
    <input type="hidden" id="recurrent_task_id" value="{{ ($recurrent_task) ? $recurrent_task->id : '' }}">
    @if ($canStartToday)
        @if (auth()->user()->hasRole('debtors_chief'))
            <div class="timezone">
                <h4>Интервал часовых поясов:</h4>
                <button id="startMassRecurrents1" class="btn btn-primary"
                        value="east"{{ ($collectionTasks->contains('timezone', 'all') || $collectionTasks->contains('timezone', 'east')) ? ' disabled' : '' }}>
                    Запустить -1+5
                </button>
                <button id="startMassRecurrents2" class="btn btn-primary"
                        value="west"{{ ($collectionTasks->contains('timezone', 'all') || $collectionTasks->contains('timezone', 'west')) ? ' disabled' : '' }}>
                    Запустить -2-5
                </button>
            </div>
        @endif
        <h4 style="{{ !auth()->user()->hasRole('debtors_chief') ? 'display: none' : '' }}">Запустить весь пулл:</h4>
        <button type="button" id="startMassRecurrents" class="btn btn-primary"
                value=""{{ $collectionTasks->count() ? ' disabled' : '' }}>Запустить
        </button>
        <div id="task_status"></div>
    @else
        @if ($completed)
            <div id="task_status">Задача уже была запущена и выполнена сегодня.</div>
        @else
            <div id="task_status">Задача создана и находится в обработке.</div>
            <div id="task_progress">Обработано договоров: <span id="progress">-</span> из <span
                        id="debtors_count">-</span></div>
        @endif
    @endif

@stop

@section('scripts')
    @if ($canStartToday)
        <script>
            $(document).ready(function () {
                $('button').click(function () {
                    $(this).attr('disabled', true);

                    let timezone = $(this).val();
                    $.ajax({
                        url: '/debtor/recurrent/massquerytask',
                        method: 'post',
                        data: {start: 1, type: $('#recurrent_type').val(), timezone: timezone},
                        success: function (data) {
                            var json_data = JSON.parse(data);
                            $('#task_status').html('Задача создана. Количество договоров: ' + json_data.debtors_count);

                            $.ajax({
                                url: '/debtor/recurrent/massquery',
                                method: 'post',
                                data: {
                                    task_id: json_data.task_id,
                                    recurrent_type: $('#recurrent_type').val(),
                                    timezone: timezone
                                },
                                success: function (answer) {
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
                $(document).ready(function () {
                    function loop() {
                        $.ajax({
                            url: '/debtor/recurrent/getstatus',
                            method: 'post',
                            data: {type: $('#recurrent_type').val(), recurrent_task_id: $('#recurrent_task_id').val()},
                            success: function (data) {
                                var json_data = JSON.parse(data);
                                if (json_data.status == 'completed') {
                                    location.reload();
                                } else {
                                    $('#debtors_count').html(json_data.debtors_count);
                                    $('#progress').html(json_data.progress);
                                    setTimeout(() => {
                                        loop();
                                    }, 1000);
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