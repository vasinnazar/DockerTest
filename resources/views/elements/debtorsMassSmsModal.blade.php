<div class="modal fade" id="debtorMassSMS" tabindex="-1" role="dialog" aria-labelledby="debtorMassSMSLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <form id="formSendSMS">
                            <input type="hidden" name="debtor_ids" value="">
                            @if (Auth::user()->hasRole('debtors_remote'))
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
                                <?php
                                $arSmsRows = \App\DebtorSmsTpls::getSmsTpls('remote');
                                ?>
                                @foreach ($arSmsRows as $row)
                                <tr>
                                    <?php
                                    $row['text_tpl'] = str_replace('##sms_till_date##', date('d.m.Y', time()), $row['text_tpl']);
                                    $row['text_tpl'] = str_replace('##sms_loan_info##', '[данные договора]', $row['text_tpl']);
                                    ?>
                                    <td><input type="radio" name="sms_id" value="{{$row['id']}}"></td>
                                    <td class="sms_text" style="text-align: left">{{$row['text_tpl']}}</td>
                                </tr>
                                @endforeach
                            </table>
                            @endif

                            @if (Auth::user()->hasRole('debtors_personal'))
                            <h4>Личное взыскание</h4>
                            <table class="table table-bordered table-condensed">
                                <thead>
                                    <tr>
                                        <td></td>
                                        <td><b>Текст SMS</b></td>
                                    </tr>
                                </thead>
                                <?php
                                $arSmsRows = \App\DebtorSmsTpls::getSmsTpls('personal');
                                ?>
                                @foreach ($arSmsRows as $row)
                                <tr>
                                    <?php
                                    $row['text_tpl'] = str_replace('##sms_till_date##', date('d.m.Y', time()), $row['text_tpl']);
                                    $row['text_tpl'] = str_replace('##sms_loan_info##', '[данные договора]', $row['text_tpl']);
                                    ?>
                                    <td><input type="radio" name="sms_id" value="{{$row['id']}}"></td>
                                    <td class="sms_text" style="text-align: left">{{$row['text_tpl']}}</td>
                                </tr>
                                @endforeach
                            </table>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>