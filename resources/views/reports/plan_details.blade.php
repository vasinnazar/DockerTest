@extends('app')
@section('content')
<style>
    table{
        border-collapse: collapse;
        font-size: 12px;
    }
    td{
        vertical-align: top;
    }
    .splitter{
        width: 20px;
    }
</style>
<h2>{{$subdivision->name}}</h2>
<h2>{{$user->name}}</h2>
<table class="table table-bordered table-condensed">
    <thead>
        <tr>
            <th></th>
            <th>План</th>
            <th>Факт</th>
            <th>Процент выполнения</th>
            <th>Личный вклад</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Займы</td>
            <td>{{$data['loans']['plan']}} руб.</td>
            <td>{{$data['loans']['fact']}} руб.</td>
            <td>{{$data['loans']['complete']}}%</td>
            <td>{{$data['loans']['personal']}} руб.</td>
        </tr>
        <tr>
            <td>НПФ</td>
            <td>{{$data['npf']['plan']}} шт.</td>
            <td>{{$data['npf']['fact']}} шт.</td>
            <td>{{$data['npf']['complete']}}%</td>
            <td>{{$data['npf']['personal']}}%</td>
        </tr>
        <tr>
            <td>УКИ</td>
            <td>{{$data['uki']['plan']}} шт.</td>
            <td>{{$data['uki']['fact']}} шт.</td>
            <td>{{$data['uki']['complete']}}%</td>
            <td>{{$data['uki']['personal']}}%</td>
        </tr>
    </tbody>
</table>
<table>
    <tr>
        <td>По реестру документов</td>
        <td class='splitter'></td>
        <td>Общий</td>
        <td class='splitter'></td>
        <td>Личный</td>
    </tr>
    <tr>
        <td>
            <table class='table table-bordered table-condensed'>
                <thead>
                    <tr>
                        <th>ID_1C</th>
                        <th>ФИО</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($docs as $loan)
                    @if($loan['absent'])
                    <tr class='danger'>
                    @else
                    <tr>
                    @endif
                        <td>{{$loan['number']}}</td>
                        <td>{{$loan['fio']}}</td>
                        <td></td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="2">Итого</td>
                        <td>{!! count($docs) !!} Шт.</td>
                    </tr>
                </tbody>
            </table>
        </td>
        <td class='splitter'></td>
        <td>
            <table class='table table-bordered table-condensed'>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ID_1C</th>
                        <th>ФИО</th>
                        <th>Сумма</th>
                        <th></th>
                        <th>Ответственный</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loans as $loan)
                    @if($loan['absent'])
                    <tr class='danger'>
                    @else
                    <tr>
                    @endif
                        <td>{{$loan->loan_id}}</td>
                        <td>{{$loan->loan_id_1c}}</td>
                        <td>{{$loan->fio}}</td>
                        <td>{{$loan->money}}</td>
                        <td><a href="<?php echo url('loans/summary/'.$loan->loan_id) ?>" target="_blank">Открыть</a></td>
                        <td>{{$loan->username}}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="3">Итого</td>
                        <td>{{$total_money}} руб.</td>
                    </tr>
                    <tr>
                        <td colspan="3">Итого</td>
                        <td>{!! count($loans) !!} Шт.</td>
                    </tr>
                </tbody>
            </table>
        </td>
        <td class='splitter'></td>
        <td>
            <table class='table table-bordered table-condensed'>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ID_1C</th>
                        <th>ФИО</th>
                        <th>Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loans_my as $loan)
                    <tr>
                        <td>{{$loan->loan_id}}</td>
                        <td>{{$loan->loan_id_1c}}</td>
                        <td>{{$loan->fio}}</td>
                        <td>{{$loan->money}}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="3">Итого</td>
                        <td>{{$total_money_my}} руб.</td>
                    </tr>
                    <tr>
                        <td colspan="3">Итого</td>
                        <td>{!! count($loans_my) !!} Шт.</td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td>УКИ общий</td>
        <td  class='splitter'></td>
        <td>УКИ личный</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td>
            <table class='table table-bordered table-condensed'>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ID_1C</th>
                        <th>ФИО</th>
                        <th>Сумма</th>
                        <th>Ответственный</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ukis as $loan)
                    <tr>
                        <td>{{$loan->loan_id}}</td>
                        <td>{{$loan->loan_id_1c}}</td>
                        <td>{{$loan->fio}}</td>
                        <td>{{$loan->money}}</td>
                        <td>{{$loan->username}}</td>
                        <td><a href='{{url('loans/summary/').$loan->loan_id}}' target="_blank">Открыть</a></td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="3">Итого</td>
                        <td>{!! count($ukis) !!} Шт.</td>
                    </tr>
                </tbody>
            </table>
        </td>
        <td  class='splitter'></td>
        <td>
            <table class="table table-bordered table-condensed">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ID_1C</th>
                        <th>ФИО</th>
                        <th>Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ukis_my as $loan)
                    <tr>
                        <td>{{$loan->loan_id}}</td>
                        <td>{{$loan->loan_id_1c}}</td>
                        <td>{{$loan->fio}}</td>
                        <td>{{$loan->money}}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="3">Итого</td>
                        <td>{!! count($ukis_my) !!} Шт.</td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>
@stop