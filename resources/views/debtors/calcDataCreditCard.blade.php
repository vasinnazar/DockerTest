<?php

use Carbon\Carbon;
?>
<table class='table table-condensed table-bordered'>
    <thead>
        <tr>
            <th></th>
            <th>
                На дату<br>
                <input class='form-control input-sm' name='debt_calc_date_cc' type='date' value="{{$date}}"/>                                            
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><span>Доступный лимит:</span></td>
            <td style="text-align: center;"><strong>{{$calculations['limit'] / 100}} руб.</strong></td>
        </tr>
        <tr>
            <td><span>Задолженность:</span></td>
            <td style="text-align: center;">
                <strong>{{($calculations['debt'] + $calculations['fine'] + $calculations['mop_debt'] + $calculations['overdue_debt'] + $calculations['percent'] +  $calculations['mop_percent'] +  $calculations['overdue_percent']) / 100}} руб.</strong>
            </td>
        </tr>
        <tr>
            <td><span>ОД:</span></td>
            <td style="text-align: center;"><strong>{{($calculations['debt'] + $calculations['mop_debt'] + $calculations['overdue_debt']) / 100}} руб.</strong></td>
        </tr>
        <tr>
            <td><span>Проценты:</span></td>
            <td style="text-align: center;"><strong>{{($calculations['percent'] +  $calculations['mop_percent'] +  $calculations['overdue_percent']) / 100}} руб.</strong></td>
        </tr>
        <tr>
            <td><span>Пеня:</span></td>
            <td style="text-align: center;"><strong>{{$calculations['fine'] / 100}} руб.</strong></td>
        </tr>
        <tr>
            <td><span>МОП:</span></td>
            <td style="text-align: center;"><strong>{{($calculations['mop_debt'] + $calculations['mop_percent']) / 100}} руб.</strong></td>
        </tr>
        <tr>
            <td><span>Дата погашения МОП:</span></td>
            <td style="text-align: center;">
                <strong>
                    @if (Carbon::now()->startOfMonth()->addDays(27)->startOfDay()->gt(Carbon::parse($calculations['loan_end_at'])->startOfDay()))
                    {{Carbon::parse($calculations['loan_end_at'])->format('Y-m-d')}}
                    @else
                    {{Carbon::now()->startOfMonth()->addDays(27)->format('Y-m-d')}}
                    @endif
                </strong>
            </td>
        </tr>
        <tr>
            <td><span>Просроченный МОП:</span></td>
            <td><b>{{($calculations['overdue_debt'] + $calculations['overdue_percent'] + $calculations['fine']) / 100}} руб.</b></td>
        </tr>
        <tr>
            <td><span>Просроченный ОД:</span></td>
            <td style="text-align: center;"><strong>{{$calculations['overdue_debt'] / 100}} руб.</strong></td>
        </tr>
        <tr>
            <td><span>Просроченные проценты:</span></td>
            <td style="text-align: center;"><strong>{{$calculations['overdue_percent'] / 100}} руб.</strong></td>
        </tr>
        <tr>
            <td><span>Пеня:</span></td>
            <td style="text-align: center;"><strong>{{$calculations['fine'] / 100}} руб.</strong></td>
        </tr>
    </tbody>
</table>