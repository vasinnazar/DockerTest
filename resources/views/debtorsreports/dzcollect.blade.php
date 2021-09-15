@extends('app')
@section('title') Отчет "Сбор ДЗ" @stop
@section('css')
<style>
    .debtors-frame{
        height: 250px;
        overflow-y:scroll;
    }
    .debtors-table-frame{
        border: 1px solid #ccc;
        padding: 10px;
    }
    @media print {
        form {
            display: none;
        }
    }
</style>
@stop
@section('content')
<div class='row'>
    <div class="col-xs-12">
        <center>
            <form action="?" method="GET">
                <table id="debtorTransferAction">
                    <tr>
                        <td style="padding-right: 10px;">Дата с:</td>
                        <td>
                            <input type="date" name="start_date" class="form-control" style="width: 350px;" value="{{($start_date) ? $start_date : date('Y-m-d', time())}}" />
                        </td>
                        <td style="padding-left: 20px; padding-right: 10px;">по: </td>
                        <td>
                            <input type="date" name="end_date" class="form-control" style="width: 350px;" value="{{($end_date) ? $end_date : date('Y-m-d', time())}}" />
                        </td>
                        <td style="padding-left: 15px;">
                            <button class="btn btn-primary">Пуск</button>
                        </td>
                    </tr>
                </table>
            </form>
        </center>
    </div>
</div>
<br>
<div class="row">
    <div class="col-xs-12">
        <table class="table table-condensed table-striped table-bordered" id="dzcollectTable">
            <thead>
                <tr>
                    <th>Группа/Регион</th>
                    <th>Сотрудник</th>
                    <th>Портфель задолженности</th>
                    <th>План</th>
                    <th>Проценты срочные</th>
                    <th>Проценты просроченные</th>
                    <th>Пени штраф</th>
                    <th>Сумма сбора</th>
                    <th>Процент сбора от ДЗ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $groupname => $userdata)
                <tr style="background-color: #cccccc;">
                    <td colspan="7"><b>{{$groupname}}</b></td>
                    <?php
                    $group_sum = 0;
                    foreach ($userdata as $user) {
                        $group_sum += $user['sum'];
                    }
                    ?>
                    <td><b>{{number_format($group_sum / 100, 2, '.', ',')}}</b></td>
                    <td></td>
                </tr>
                    @foreach($userdata as $username => $user)
                    <tr>
                        <td>{{$user['region']}}</td>
                        <td>{{$user['fio']}}</td>
                        <td></td>
<td></td>
<td></td>
<td></td>
<td></td>
<td>{{number_format($user['sum'] / 100, 2, '.', ',')}}</td>
<td></td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
   </table>
    </div>
</div>
@stop
@section('scripts')
    <script src="{{asset    ('js/debtors/debtorsController.js?4')}}"></script>
                <script>
                $(document).ready(function () {
                    $.debtorsCtrl.init();

                });
                </script>
                @stop
