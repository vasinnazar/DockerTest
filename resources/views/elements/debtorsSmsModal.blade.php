<div class="modal fade" id="debtorSMS" tabindex="-1" role="dialog" aria-labelledby="debtorSMSLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
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
                <button type="button" class="btn btn-default" data-dismiss="modal" id="closeSendSMS">Закрыть</button>
                <button type="button" class="btn btn-primary" id="sendSMS" disabled>Отправить</button>
            </div>
            @endif
        </div>
    </div>
</div>