<style>
    table{
        width: 100%;
        border-collapse: collapse;
    }
    table td,table th{
        border: 1px solid black;
    }
    .group-cell{
        background-color: #ffffcc;
    }
</style>
<h1>Отчет по выполнению плана и сбору ДЗ</h1>
<div>
    Параметры: {{$user->name}}<br>
    Начало периода: {{$start_date}}<br>
    Конец периода: {{$end_date}}<br>
</div>
<br>
<table>
    <thead>
        <tr>
            <th colspan="2" style='width: 30%'>Группы</th>
            <th rowspan="2">Портфель задолжности</th>
            <th rowspan="2">План</th>
            <th rowspan="2">Проценты срочные</th>
            <th rowspan="2">Проценты просроченные</th>
            <th rowspan="2">Пени штраф</th>
            <th rowspan="2">Сумма сбора</th>
            <th rowspan="2">Процент сбора от ДЗ</th>
        </tr>
        <tr>
            <th>Регион</th>
            <th>Сотрудник</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $total_portfel = 0;
        $total_plan = 0;
        $total_pc = 0;
        $total_exp_pc = 0;
        $total_fine = 0;
        $total_sum = 0;
        $total_procendz = 0;
        $prev_group = '';
        ?>
        @foreach($items as $item)
        @if($item->group != $prev_group)
        <tr>
            <td colspan="9" class="group-cell">
                {{$item->group}}
            </td>
        </tr>
        <?php $prev_group = $item->group; ?>
        @endif
        <tr>
            <td>{{(string)$item->region}}</td>
            <td>{{(string)$item->fio}}</td>
            <td>{{number_format((float)$item->portfel/100,2,'.','')}}</td>
            <td>{{number_format((float)$item->plan/100,2,'.','')}}</td>
            <td>{{number_format((float)$item->pc/100,2,'.','')}}</td>
            <td>{{number_format((float)$item->exp_pc/100,2,'.','')}}</td>
            <td>{{number_format((float)$item->fine/100,2,'.','')}}</td>
            <td>{{number_format((float)$item->sum/100,2,'.','')}}</td>
            <td>{{number_format((float)$item->procendz/100,2,'.','')}}</td>
        </tr>
        <?php 
        $total_portfel += (float)$item->portfel;
        $total_plan += (float)$item->plan;
        $total_pc += (float)$item->pc;
        $total_exp_pc += (float)$item->exp_pc;
        $total_fine += (float)$item->fine;
        $total_sum += (float)$item->sum;
        $total_procendz += (float)$item->procendz;
        ?>
        @endforeach
        <tr>
            <td colspan="2" style="text-align: right">Итого</td>
            <td>{{number_format($total_portfel/100,2,'.','')}}</td>
            <td>{{number_format($total_plan/100,2,'.','')}}</td>
            <td>{{number_format($total_pc/100,2,'.','')}}</td>
            <td>{{number_format($total_exp_pc/100,2,'.','')}}</td>
            <td>{{number_format($total_fine/100,2,'.','')}}</td>
            <td>{{number_format($total_sum/100,2,'.','')}}</td>
            <td>{{number_format($total_procendz/100,2,'.','')}}</td>
        </tr>
    </tbody>
</table>