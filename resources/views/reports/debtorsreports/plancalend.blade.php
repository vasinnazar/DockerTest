<style>
    table{
        border-collapse: collapse;
        width: 100%;
    }
    table td, table th{
        border: 1px solid #ffffcc;
    }
    thead tr th{
        background-color: #ffcc66;
    }
    .user-row{
        background-color: #ffff99;
    }
    .completed-row{
        background-color: #ffffcc;
    }
</style>
<table>
    <thead>
        <tr>
            <th colspan="7">Ответственный</th>
        </tr>
        <tr>
            <th colspan="7">Статус обработки</th>
        </tr>
        <tr>
            <th>Контрагент</th>
            <th>Дата план</th>
            <th>Дата факт</th>
            <th>Тип мероприятия</th>
            <th>Результат</th>
            <th>Отчет</th>
            <th>Группа долга</th>
        </tr>
    </thead>
    <tbody>
        <?php $prev_user = ''; $prev_result=''; ?>
        @foreach($events as $item)
        @if($item->users_name != $prev_user)
        <tr class="user-row">
            <td colspan="7">{{$item->users_name}}</td>
        </tr>
        <?php $prev_user = $item->users_name; $prev_result = ''; ?>
        @endif
        @if((string)$item->completed != (string)$prev_result)
        <tr class="completed-row">
            <td colspan="7">
                @if($item->completed)
                Да
                @else
                Нет
                @endif
            </td>
        </tr>
        <?php $prev_result = $item->completed; ?>
        @endif
        <tr>
            <td>{{$item->passports_fio}}</td>
            <td>{{with(new \Carbon\Carbon($item->plan_date))->format('d.m.Y H:i:s')}}</td>
            <td>{{with(new \Carbon\Carbon($item->fact_date))->format('d.m.Y H:i:s')}}</td>
            <td>@if(array_key_exists($item->event_type_id,config('debtors.event_types'))) {{config('debtors.event_types')[$item->event_type_id]}} @endif</td>
            <td>@if(array_key_exists($item->event_result_id,config('debtors.event_results'))) {{config('debtors.event_results')[$item->event_result_id]}} @endif</td>
            <td>{{$item->report}}</td>
            <td>@if(array_key_exists($item->debt_group_id,config('debtors.debt_groups'))) {{config('debtors.debt_groups')[$item->debt_group_id]}} @endif</td>
        </tr>
        @endforeach
    </tbody>
</table>