@extends('app')
@section('title') Календарь мероприятий @stop
@section('css')
<link rel="stylesheet" href="{{asset('js/libs/fullcalendar-3.2.0/fullcalendar.min.css')}}" />
@stop
@section('content')

<div class="row" style="padding-left: 60px; padding-right: 60px;">
    <div class="col-xs-4" id="calendarBlock">
        <div id="calendar"></div>
    </div>
    <div class="col-xs-8" id="controlBlock">
        <div class="pull-right">
            <a class="chat-window-toggler btn btn-default" href="#">
                &nbsp;<span class="glyphicon glyphicon-warning-sign"></span>&nbsp;&nbsp;Оплаты&nbsp;
            </a>
        </div>
        <ul class="nav nav-tabs" role="tablist">
            <li class="active"><a href="#first" aria-controls="first" role="tab" data-toggle="tab">Вкладка</a></li>
            <li><a href="#second" aria-controls="second" role="tab" data-toggle="tab">Вторая</a></li>
            <li><a href="#third" aria-controls="third" role="tab" data-toggle="tab">Третья</a></li>
        </ul>
        <br>
        <div class="tab-content" style="min-height: 400px;">
            <div role="tabpanel" class="tab-pane active" id="first">1</div>
            <div role="tabpanel" class="tab-pane" id="second">rg</div>
            <div role="tabpanel" class="tab-pane" id="third">3</div>
        </div>

        <div style="border-top: 1px #cccccc solid;">
            <h4>Управление</h4>
        </div>
    </div>
</div>
@stop
@section('scripts')
<script src="{{asset('js/libs/fullcalendar-3.2.0/fullcalendar.min.js')}}"></script>
<script>
$(document).ready(function () {
    $('#calendar').fullCalendar({
        events: [
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Выезд на шашлычок",
                start: "2017-03-02",
                editable: true
            },
            {
                title: "Шашлычок",
                start: "2017-03-05"
            }
        ],
        locale: "ru",
        height: 800,
        defaultView: "agendaDay",
        header: {
            right: "today prev,next resizeCalendar",
            left: "month,agendaWeek,agendaDay,list"
        },
        buttonText: {
            prevYear: "&nbsp;&lt;&lt;&nbsp;",
            nextYear: "&nbsp;&gt;&gt;&nbsp;",
            today: "Сегодня",
            month: "Месяц",
            week: "Неделя",
            day: "День",
            list: "Список"
        },
        allDayText: "весь день",
        nowIndicator: true,
        eventLimit: true,
        eventClick: function (calEvent, jsEvent, view) {
            $(this).css('background-color', 'red');
        },
        customButtons: {
            resizeCalendar: {
                text: '»',
                click: function () {
                    if (!$(this).hasClass('expanded')) {
                        $('#controlBlock').hide();
                        $('#calendarBlock').attr('class', 'col-xs-12');
                        $(this).html('«');
                        $(this).addClass('expanded');
                    } else {
                        $(this).removeClass('expanded');
                        $('#calendarBlock').attr('class', 'col-xs-4');
                        $('#controlBlock').show();
                        $(this).html('»');
                    }
                }
            }
        }
    });
});
</script>
@stop