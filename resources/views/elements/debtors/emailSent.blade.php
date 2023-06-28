<?php
$arEmailSentStatuses = [
    0 => 'Не отправлено',
    1 => 'Отправлено'
];
?>
<div class="modal fade" id="debtorEmailSent" tabindex="-1" role="dialog" aria-labelledby="debtorEmailSentLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                 <h4 class="modal-title">Отправленные Email</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        @if (count($arEmailSent))
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Дата</th>
                                        <th>Сообщение</th>
                                        <th>Статус</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($arEmailSent as $email)
                                    <tr>
                                        <td>{{date('d.m.Y', strtotime($email->created_at))}}</td>
                                        <td>{{$email->message}}</td>
                                        <td>{{$arEmailSentStatuses[$email->status]}}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @elseif (count($arEmailSent))
                            Ошибка.
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