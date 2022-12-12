<div class='col-xs-12'>
    <hr>
    <h4>Общее количество запланированных</h4>
    <table class="table table-condensed table-bordered" id="totalDebtorEvents">
        <thead>
        <tr>
            <th>Тип</th>
            @foreach($total_debtor_events['cols'] as $col)
                @if($col==\Carbon\Carbon::today()->format('d.m.y'))
                    <th><a class='btn btn-default btn-xs planned-table-selected-btn' style="font-size:8px;" href="#"
                           onclick="$.debtorsCtrl.changeEventsDate('{{$col}}', this); return false;">{{substr($col,0,-3)}}
                    </th>
                @else
                    <th><a class='btn btn-default btn-xs' style="font-size:8px;" href="#"
                           onclick="$.debtorsCtrl.changeEventsDate('{{$col}}', this); return false;">{{substr($col,0,-3)}}
                    </th>
                @endif
            @endforeach
            <th>Итог</th>
        </tr>
        </thead>
        <tbody>
        @foreach($total_debtor_events['data'] as $k=>$v)
            <tr>
                <td>
                    @if (isset($event_types[$k]))
                        {{$event_types[$k]}}
                    @else
                        Не определено
                    @endif
                </td>
                @foreach($total_debtor_events['cols'] as $col)
                    @if(array_key_exists($col,$total_debtor_events['data'][$k]))
                        <td>{{$total_debtor_events['data'][$k][$col]}}</td>
                    @else
                        <td></td>
                    @endif
                @endforeach
                <td>{{$total_debtor_events['total_types'][$k]}}</td>
            </tr>
        @endforeach
        <tr>
            <td>Общий итог</td>
            @foreach($total_debtor_events['total_days'] as $k=>$num)
                <td>{{$num}}</td>
            @endforeach
            <td>{{$total_debtor_events['total']}}</td>
        </tr>
        <tr>
        <td>Общая сумма договорённости</td>
        @foreach($total_debtor_events['totalDayAmount'] as $k=>$num)
            <td>{{$num}}</td>
        @endforeach
        </tr>
        </tbody>
    </table>
</div>
@if ($user_id == 916 || $user_id == 227)
    <div class='col-xs-12'>
        <hr>
        <h4>Общее в работе</h4>
        <table class="table table-striped table-condensed" id="overallEvents">
            <thead>
            <tr>
                <th style="width: 100px;">База</th>
                <th style="width: 70px;">Кол-во</th>
                <th style="width: 150px;">Зад-сть</th>
                <th style="width: 150px;">Сумма по ОД</th>
            </tr>
            </thead>
            <tbody class='debtors-frame'>
            @foreach($debtorsOverall['items'] as $item)
                <tr>
                    <td>{{$item->base}}</td>
                    <td>{{$item->num}}</td>
                    <td>{{$item->sum_debt}}</td>
                    <td>{{$item->sum_od}}</td>
                </tr>
            @endforeach
            <tr class='bg-danger'>
                <td>{{$debtorsOverall['total']['base']}}</td>
                <td>{{$debtorsOverall['total']['num']}}</td>
                <td>{{$debtorsOverall['total']['sum_debt']}}</td>
                <td>{{$debtorsOverall['total']['sum_od']}}</td>
            </tr>
            </tbody>
        </table>
    </div>
@endif
