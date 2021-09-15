<thead>
    <tr>
        <th>Тип</th>
        @foreach($total_debtor_events['cols'] as $col)
        @if($col==\Carbon\Carbon::today()->format('d.m.y'))
        <th><a class='btn btn-default btn-xs planned-table-selected-btn' style="font-size:8px;" href="#" onclick="$.debtorsCtrl.changeEventsDate('{{$col}}', this); return false;">{{substr($col,0,-3)}}</th>
        @else
        <th><a class='btn btn-default btn-xs' style="font-size:8px;" href="#" onclick="$.debtorsCtrl.changeEventsDate('{{$col}}', this); return false;">{{substr($col,0,-3)}}</th>
        @endif
        @endforeach
        <th>Итог</th>
    </tr>
</thead>
<tbody>
    @foreach($total_debtor_events['data'] as $k=>$v)
    <tr>
        <td>{{$event_types[$k]}}</td>
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
</tbody>