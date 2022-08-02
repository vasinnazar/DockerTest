<div class="modal fade" id="debtorSMS" tabindex="-1" role="dialog" aria-labelledby="debtorSMSLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" id="smsConntent">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        @if ($debtroles['canSend'])
                        @if (count($debtroles))
                        <form id="formSendSMS">
                            <input type="hidden" name="sms_phone_number" value="">
                            <input type="hidden" name="debtor_id_1c" value="{{$data[0]['debtor_id_1c']}}">
                            @if (isset($debtroles['remote']))
                            <h4>Удаленное взыскание</h4>
                            <table style="margin-bottom: 15px;">
                                <tr>
                                    <td>Дата для SMS:</td>
                                    <td style="padding-left: 15px;"><input type="date" name="sms_date" class="form-control" style="width: 200px;" min="{{date('Y-m-d', time())}}"></td>
                                </tr>
                            </table>
                            <table class="table table-bordered table-condensed">
                                <thead>
                                    <tr>
                                        <td></td>
                                        <td><b>Текст SMS</b></td>
                                    </tr>
                                </thead>
                                @foreach ($debtroles['remote'] as $row)
                                <tr>
                                    <td><input type="radio" name="sms_id" value="{{$row['id']}}"></td>
                                    <td class="sms_text" style="text-align: left">{{$row['text_tpl']}}</td>
                                </tr>
                                @endforeach
                            </table>
                            @endif

                            @if (isset($debtroles['personal']))
                            <h4>Личное взыскание</h4>
                            <table class="table table-bordered table-condensed">
                                <thead>
                                    <tr>
                                        <td></td>
                                        <td><b>Текст SMS</b></td>
                                    </tr>
                                </thead>
                                @foreach ($debtroles['personal'] as $row)
                                @if ($row['id'] == 21 && $debtor->qty_delays < 80 && $debtor->base != 'Б-МС')
                                <?php continue; ?>
                                @endif
                                @if ($row['id'] == 21 && $debtor->qty_delays < 30 && $debtor->base == 'Б-МС')
                                <?php continue; ?>
                                @endif
                                <tr>
                                    <td><input type="radio" name="sms_id" value="{{$row['id']}}"></td>
                                    <td class="sms_text" style="text-align: left">{{$row['text_tpl']}}</td>
                                </tr>
                                @endforeach
                            </table>
                            @endif
                        </form>
                        @else
                        <p style="text-align: center">Неизвестна группа специалиста взыскания (удаленное или личное).</p>
                        @endif
                        @else
                        <p style="text-align: center">Исчерпан лимит отправки SMS.</p>
                        @endif
                    </div>
                </div>
            </div>
            @if (count($debtroles))
            <div class="modal-footer">
                <span class="pull-left">Лимит SMS: {{$data[0]['sms_available']}}</span>
                <button type="button" class="btn btn-default" id="showSmsProps">Отправить ссылку на реквизиты</button>
                <button type="button" class="btn btn-default" id="showSmsLink">Отправить ссылку на оплату</button>
                <button type="button" class="btn btn-default" data-dismiss="modal" id="closeSendSMS">Закрыть</button>
                <button type="button" class="btn btn-primary" id="sendSMS" disabled>Отправить</button>
            </div>
            @endif
        </div>
        <div class="modal-content" id="smsLinkContent" style="display: none;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        @if ($debtroles['canSend'])
                        @if (count($debtroles))
                        <form id="formSendSMSLink">
                            <input type="hidden" name="debtor_id_1c" value="{{$data[0]['debtor_id_1c']}}">
                            <h4>Отправка ссылки на оплату</h4>
                            <div class="form-group">
                                <div class="col-xs-4">
                                    <input type="checkbox" name="enableThirdPeople" id="enableThirdPeople">
                                    Тел. третьего лица
                                </div>
                                <div class="col-xs-8">
                                    <input type="text" name="phoneThirdPeople" id="phoneThirdPeople" class="form-control" disabled>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-xs-4">
                                    <input type="radio" name="paymentLinkSumType" id="paymentLinkSumType1" value="1" checked>
                                    Полная оплата
                                </div>
                                <div class="col-xs-8">
                                    <input type="text" name="paymentLinkSumFull" id="paymentLinkSumFull" value="{{ number_format($debtor->sum_indebt / 100, '2', '.', '') }}" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-xs-4">
                                    <input type="radio" name="paymentLinkSumType" id="paymentLinkSumType2" value="2">
                                    Частичная оплата
                                </div>
                                <div class="col-xs-8">
                                    <input type="number" name="paymentLinkSum" id="paymentLinkSum" class="form-control" disabled>
                                </div>
                            </div>
                        </form>
                        @else
                        <p style="text-align: center">Неизвестна группа специалиста взыскания (удаленное или личное).</p>
                        @endif
                        @else
                        <p style="text-align: center">Исчерпан лимит отправки SMS.</p>
                        @endif
                    </div>
                </div>
            </div>
            @if (count($debtroles))
            <div class="modal-footer">
                <span class="pull-left">Лимит SMS: {{$data[0]['sms_available']}}</span>
                <button type="button" class="btn btn-default" id="getMessengerText">Текст для мессенджера</button>
                <button type="button" class="btn btn-default" id="showSmsInfo">Отправить информацию</button>
                <button type="button" class="btn btn-default" data-dismiss="modal" id="closeSendSMS">Закрыть</button>
                <button type="button" class="btn btn-primary" id="sendSMSLink">Отправить</button>
                <div class="form-group" id="textForMessengerBlock" style="display: none; margin-top: 20px;">
                    <div class="col-xs-12">
                        <textarea class="form-control" id="textForMessenger"></textarea>
                    </div>
                </div>
            </div>
            @endif
        </div>
        <div class="modal-content" id="smsPropsContent" style="display: none;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        @if ($debtroles['canSend'])
                        @if (count($debtroles))
                        <form id="formSendSMSProps">
                            <input type="hidden" name="sms_phone_number" value="">
                            <input type="hidden" name="debtor_id_1c" value="{{$data[0]['debtor_id_1c']}}">
                            <h4>Отправка ссылки на реквизиты</h4>
                            <div class="form-group">
                                <div class="col-xs-4">
                                    Ссылка
                                </div>
                                <div class="col-xs-8">
                                    <input type="text" name="smsPropsLink" id="smsPropsLink" value="https://xn--80ajiuqaln.xn--p1ai/faq/rekvizity" class="form-control" readonly>
                                </div>
                            </div>
                        </form>
                        @else
                        <p style="text-align: center">Неизвестна группа специалиста взыскания (удаленное или личное).</p>
                        @endif
                        @else
                        <p style="text-align: center">Исчерпан лимит отправки SMS.</p>
                        @endif
                    </div>
                </div>
            </div>
            @if (count($debtroles))
            <div class="modal-footer">
                <span class="pull-left">Лимит SMS: {{$data[0]['sms_available']}}</span>
                <button type="button" class="btn btn-default" data-dismiss="modal" id="closeSendSMSProps">Закрыть</button>
                <button type="button" class="btn btn-primary" id="sendSMSProps">Отправить</button>
            </div>
            @endif
        </div>
    </div>
</div>