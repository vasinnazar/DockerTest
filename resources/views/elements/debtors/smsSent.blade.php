<?php
$arSmsStatuses = [
    1 => 'Нужно отправить',
    2 => 'Отправляется',
    3 => 'Отправлено',
    4 => 'Проверяется',
    5 => 'Доставлено',
    6 => 'Ошибка'
];
?>
<div class="modal fade" id="debtorSmsSent" tabindex="-1" role="dialog" aria-labelledby="debtorSmsSentLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                 <h4 class="modal-title">Отправленные SMS</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        @if (count($arSmsSent) && !isset($arSmsSent['errors']))
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Сообщение</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($arSmsSent as $sms)
                                <tr>
                                    <td>{{date('d.m.Y', strtotime($sms['send_at']))}}</td>
                                    <td>{{$sms['text']}}</td>
                                    <td>{{$arSmsStatuses[$sms['status_id']]}}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @elseif (count($arSmsSent) && !isset($arSmsSent['errors']))
                        Ошибка. {{$arSmsSent['message']}}
                        @else
                        Уведомления не отправлялись
                        @endif
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>