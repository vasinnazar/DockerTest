@extends('app')
@section('title') История передачи должников @stop
@section('css')
<style>

</style>
@stop
@section('content')
<div style="margin-left: 10%; margin-right: 10%;">
    <div class="row">
        <div class="col-xs-12">
            @if (!count($arTransfers))
            <table class="table table-condensed table-striped table-bordered">
                <tr>
                    <td style="text-align: center;">Сегодня никто ничего не передавал.</td>
                </tr>
            </table>
            @else
            @foreach ($arTransfers as $transfer_data)
            <div style="width: 100%; border-bottom: 2px solid #000000;">
                Операция #{{ $transfer_data['operation_number'] }} от {{ $transfer_data['transfer_time'] }}, пользователь: {{ $transfer_data['operation_user_name'] }}
            </div>
            <table class="table table-condensed table-striped table-bordered">
                <thead>
                    <tr>
                        <th style="width: ">Должник</th>
                        <th>Ответственный, до</th>
                        <th>Ответственный, после</th>
                        <th>База, до</th>
                        <th>База, после</th>
                        <th>Подразделение, до</th>
                        <th>Подразделение, после</th>
                        <th>Дата закрепления, до</th>
                        <th>Дата закрепления, после</th>
                        <th>Автозакрепление</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transfer_data['transfers'] as $transfer)
                    <?php

                    $debtor_name = $transfer->debtor_id_1c;

                    $debtor = App\Debtor::where('debtor_id_1c', $transfer->debtor_id_1c)->first();
                    if (!is_null($debtor)) {
                        $passport = \App\Passport::where('series', $debtor->passport_series)->where('number', $debtor->passport_number)->first();
                        if (!is_null($passport)) {
                            $debtor_name = $passport->fio;
                        }
                    }
                    ?>
                    <tr>
                        <td><a href="/debtors/debtorcard/{{ $debtor->id }}" target="_blank">{{ $debtor_name }}</a></td>
                        <td>{{ $transfer->responsible_user_id_1c_before }}</td>
                        <td>{{ $transfer->responsible_user_id_1c_after }}</td>
                        <td>{{ $transfer->base_before }}</td>
                        <td>{{ $transfer->base_after }}</td>
                        <td>{{ (mb_strlen($transfer->str_podr_before) > 0) ? $arSubdivisions[$transfer->str_podr_before] : '' }}</td>
                        <td>{{ (mb_strlen($transfer->str_podr_after) > 0) ? $arSubdivisions[$transfer->str_podr_after] : '' }}</td>
                        <td>{{ date('d.m.Y', strtotime($transfer->fixation_date_before)) }}</td>
                        <td>{{ date('d.m.Y', strtotime($transfer->fixation_date_after)) }}</td>
                        <td>{{ ($transfer->auto_transfer == 0) ? 'Нет' : 'Да' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endforeach
            @endif
        </div>
    </div>
</div>
@stop
@section('scripts')
<script src="{{asset('js/debtors/debtorsController.js?2')}}"></script>
<script>
$(document).ready(function () {

});
</script>
@stop
