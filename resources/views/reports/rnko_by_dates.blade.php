<html>
    <head>
        <title>Отчет РНКО</title>
        <style>
            body{
                font-family: 'Arial', sans-serif;
            }
            table{
                border-collapse: collapse;
            }
            table td, table th{
                min-width: 50px;
                text-align: center;
                border:1px solid black;
            }
            .weekend{
                background-color: #ffcccc;
            }
            table tbody tr:hover td{
                background-color: #ccff99;
            }
            table tbody tr:hover td.weekend{
                background-color: #87ac61;
            }
        </style>
    </head>
    <body>
        <h3>Обработанные карты с {{$date_start}} по {{$today}}</h3>

        <table>
            <thead>
                <tr>
                    <th style="min-width: 300px;">ФИО</th>
                    @foreach ($stat[0]->days as $k => $v)
                    @if($v[2])
                    <th class='weekend'>{{$k}}</th>
                    @else
                    <th>{{$k}}</th>
                    <th>{{$k}}</th>
                    @endif
                    @endforeach
                    <th>Итого с 09:00 до 18:00</th>
                    <th>Итого после 18:00</th>
                    <th>Итого на выходных</th>
                </tr>
                <tr>
                    <th></th>
                    @foreach ($stat[0]->days as $k => $v)
                    @if($v[2])
                    <th class='weekend'>вых.</th>
                    @else
                    <th>раб.вр.</th>
                    <th>своб.вр</th>
                    @endif
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <?php
                $total = 0;
                $totalWork = 0;
                $totalAfterSix = 0;
                $totalWeekend = 0;
                $colNum = (count($stat[0]->days) * 2) + 1;
                foreach ($stat[0]->days as $num) {
                    if ($num[2]) {
                        $colNum--;
                    }
                }
                ?>
                @foreach ($stat as $user)
                @if(!isset($user->hide) || $user->hide!=1)
                <tr>
                    <td>{{$user->fio}}</td>

                    @foreach ($user->days as $num)
                    @if($num[2])
                    <td class='weekend'>{{$num[0]+$num[1]}}</td>
                    @else
                    <td>{{$num[0]}}</td>
                    <td>{{$num[1]}}</td>
                    @endif
                    @endforeach
                    <td>{{$user->total}}</td>
                    <td>{{$user->totalAfterSix}}</td>
                    <td>{{$user->totalWeekend}}</td>
                    <?php
//                    $total += $user->total + $user->totalAfterSix + $user->totalWeekend;
//                    $totalWork += $user->total;
//                    $totalAfterSix += $user->totalAfterSix;
//                    $totalWeekend += $user->totalWeekend;
                    ?>
                </tr>
                @endif
                @endforeach
                <tr>
                    <td colspan="{{$colNum}}"></td>
                    <td>
                        <!--{{$totalWork}}-->
                    </td>
                    <td>
                        <!--{{$totalAfterSix}}-->
                    </td>
                    <td>
                        <!--{{$totalWeekend}}-->
                    </td>
                </tr>
            </tbody>
        </table>
    </body>
</html>