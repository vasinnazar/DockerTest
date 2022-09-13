<?php
if (Auth::id() == 69) { ?>
<p style="text-align: center; color: blue;">Данные из базы 1С: {{ $loans['base_type'] }}</p>
<?php
}
?>
<p style="text-align: center; color: blue; font-weight: bold; padding: 0;">Общая сумма долга: <span id="totalSumDebt">{{ number_format($loans['summary'] / 100, '2', '.', '') }}</span> руб.</p>
<p style="text-align: center; color: blue; font-weight: bold; padding: 0;">Проценты: {{ number_format($loans['total_pc'] / 100, '2', '.', '') }} руб.</p>
<p style="text-align: center; color: blue; font-weight: bold; padding: 0;">Изменение общей суммы долга: <span id="diffTotalSumChange">0</span> руб.</p>
<?php
foreach ($loans as $id_1c => $mLoan) {

if ($id_1c == 'current_loan_id_1c' || $id_1c == 'summary' || $id_1c == 'base_type' || $id_1c == 'total_pc') {
    continue;
}
?>
@if ($loans['current_loan_id_1c'] != $id_1c)
@if ($mLoan['has_result'] == 0)
<a href="{{ url('/debtors/debtorcard/' . $mLoan['debtor_id']) }}" class='btn btn-danger' target="_blank" style="width: 100%; margin-bottom: 5px; font-size: 80%;">Некорректная инф. для {{ $id_1c }}</a>
<br>
@else
<a href="{{ ($mLoan['debtor_id'] == 0) ? 'javascript:void(0);' : url('/debtors/debtorcard/' . $mLoan['debtor_id']) }}" class='btn btn-{{ ($mLoan['debt'] == 0 || $mLoan['debtor_id'] == 0) ? 'success' : 'danger' }}' target="_blank" style="width: 100%; margin-bottom: 5px; font-size: 80%;">№ {{ $id_1c }} от {{ date('d.m.Y', strtotime($mLoan['created_at'])) }} г. [ {{ ($mLoan['debt'] == 0) ? 'закрыт' : number_format($mLoan['debt'] / 100, '2', '.', '') . ' руб.' }} ] &nbsp;&nbsp; <span class="glyphicon glyphicon-time" aria-hidden="true"></span> {{ $mLoan['exp_days'] }} {{ $mLoan['responsible_user_id_1c'] }}</a>
<br>
@endif
@endif
<?php
}
?>
<script>
$(document).ready(function(){
    if ($('#overall_sum_today').val() == '') {
        $('#overall_sum_today').val({{ number_format($loans['summary'] / 100, '2', '.', '') }});
    } else {
        
    }
    $('#overall_sum_onday').val({{ number_format($loans['summary'] / 100, '2', '.', '') }});
    
    var sumToday = parseFloat($('#overall_sum_today').val());
    var sumOnDay = parseFloat($('#overall_sum_onday').val());
    
    var diffOverallSum = sumOnDay - sumToday;
    
    $('#diffTotalSumChange').text(diffOverallSum.toFixed(2));
});
</script>