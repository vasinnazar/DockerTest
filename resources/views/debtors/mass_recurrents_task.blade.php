@extends('app')
@section('title')
    Массовый безакцепт
@stop
@section('content')
    <input type="hidden" id="str_podr" value="{{ $str_podr }}">
    @foreach ($collectionTasks->where('completed', 0) as $task)
        <input type="hidden" class="exec-tasks" name="executing_tasks[]" value="{{ $task->id }}">
    @endforeach

    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <h1>Массовый безакцепт
                    @if ($str_podr == '000000000006-1')
                        - старший УПР
                    @elseif ($str_podr == '000000000007-1')
                        - ведущий УДР
                    @endif
                </h1>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-4">
                <button id="startMassRecurrents1" class="btn btn-primary"
                        value="east"{{ ($collectionTasks->contains('timezone', 'all') || $collectionTasks->contains('timezone', 'east')) ? ' disabled' : '' }}>
                    Запустить Восток (-1 +5)
                </button>
            </div>
            <div class="col-xs-4">
                <button id="startMassRecurrents2" class="btn btn-primary"
                        value="west"{{ ($collectionTasks->contains('timezone', 'all') || $collectionTasks->contains('timezone', 'west')) ? ' disabled' : '' }}>
                    Запустить Запад (-2 -5)
                </button>
            </div>
            <div class="col-xs-4">
                <button type="button" id="startMassRecurrents" class="btn btn-primary"
                        value=""{{ $collectionTasks->count() ? ' disabled' : '' }}>Запустить весь пулл
                </button>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <table class="table table-bordered" style="margin-top: 50px;">
                    <caption style="text-align: center;">
                        <strong>Задачи, запущенные сегодня</strong>
                    </caption>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Отдел</th>
                        <th>Инициатор</th>
                        <th>Часовые пояса</th>
                        <th>Прогресс</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if (!$collectionTasks->count())
                        <tr>
                            <td colspan="5">Задачи отсутствуют.</td>
                        </tr>
                    @else
                        @foreach($collectionTasks as $task)
                            <tr>
                                <td>{{ $task->id }}</td>
                                <td>
                                    @if ($task->str_podr == '000000000006')
                                        УПР
                                    @elseif($task->str_podr == '000000000006-1')
                                        УПР Старший
                                    @elseif($task->str_podr == '000000000007')
                                        УДР
                                    @elseif($task->str_podr == '000000000007-1')
                                        УДР Ведущий
                                    @else
                                        Не определен
                                    @endif
                                </td>
                                <td>
                                    {{ $task->user->login }}
                                    <br>
                                    <span style="font-size: 80%; color: grey; font-style: italic;">{{ $task->created_at->format('d.m.Y H:i:s') }}</span>
                                </td>
                                <td>
                                    @if ($task->timezone == 'east')
                                        Восток
                                    @elseif($task->timezone == 'west')
                                        Запад
                                    @elseif($task->timezone == 'all')
                                        Весь пулл
                                    @else
                                        Не определен
                                    @endif
                                </td>
                                <td>
                                    @if ($task->completed)
                                        Выполнена
                                        <br>
                                        <span style="font-size: 80%; color: grey; font-style: italic;">{{ $task->updated_at->format('d.m.Y H:i:s') }}</span>
                                    @else
                                        <span id="progress-count-{{ $task->id }}">?</span> / {{ $task->debtors_count }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@stop

@section('scripts')
        <script>
            $(document).ready(function () {
                $('button').click(function () {
                    if (confirm('Вы уверены, что хотите запустить задачу?')) {
                        $(this).attr('disabled', true);
                        $(this).html('Задача создается, ожидайте...');

                        let timezone = $(this).val();
                        $.ajax({
                            url: '/debtor/recurrent/massquerytask',
                            method: 'post',
                            data: {start: 1, str_podr: $('#str_podr').val(), timezone: timezone},
                            success: function (data) {
                                var json_data = JSON.parse(data);

                                $.ajax({
                                    url: '/debtor/recurrent/massquery',
                                    method: 'post',
                                    data: {
                                        task_id: json_data.task_id
                                    },
                                    success: function () {

                                    }
                                });

                                location.reload();
                            }
                        });
                    }
                });
            });
        </script>
        @if ($collectionTasks->where('completed', 0)->count())
        <script>
            $(document).ready(function () {
                function loop() {
                    $.ajax({
                        url: '/debtor/recurrent/getstatus',
                        method: 'post',
                        data: {tasks: $('input[name="executing_tasks[]"]').serializeArray()},
                        success: function (data) {
                            var json_data = JSON.parse(data);
                            if (json_data.status == 'completed') {
                                location.reload();
                            } else {
                                $.each(json_data.tasks, function(task_id, count) {
                                    $('#progress-count-' + task_id).html(count);
                                });
                            }
                            setTimeout(() => {
                                loop();
                            }, 2000);
                        }
                    });
                }

                loop();
            });
        </script>
        @endif
@stop