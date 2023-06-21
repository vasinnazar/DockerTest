<div class="modal fade" id="debtorMassSMS" tabindex="-1" role="dialog" aria-labelledby="debtorMassSMSLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4>{{$nameGroup}}</h4>
                <button type="button" class="close form-close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <form id="formSendSMS">
                            <input type="hidden" name="debtor_ids" value="">
                            <table class="table table-bordered table-condensed">
                                <thead>
                                <tr>
                                    <td></td>
                                    <td><b>Текст SMS</b></td>
                                </tr>
                                </thead>
                                @foreach ($smsCollect as $sms)
                                        <?php
                                        $sms->text_tpl = str_replace('##sms_till_date##', date('d.m.Y', time()),
                                            $sms->text_tpl);
                                        $sms->text_tpl = str_replace('##sms_loan_info##', '[данные договора]',
                                            $sms->text_tpl);
                                        ?>
                                    <tr>
                                        <td><input type="radio" name="sms_id" value="{{$sms->id}}"></td>
                                        <td class="sms_text" style="text-align: left">{{$sms->text_tpl}}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
