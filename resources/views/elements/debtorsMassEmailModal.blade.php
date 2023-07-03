<div class="modal fade" id="debtorMassEmail" tabindex="-1" role="dialog" aria-labelledby="debtorMassEmailLabel"
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
                        <form id="formSendEmail">
                            <input type="hidden" name="debtor_ids" value="">
                            <table class="table table-bordered table-condensed">
                                <thead>
                                    <tr>
                                        <td></td>
                                        <td><b>Текст Email</b></td>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($emailCollect as $email)
                                        <tr>
                                            <td>
                                                <input
                                                        type="radio"
                                                        name="email_id"
                                                        value="{{$email->id}}"
                                                        onclick="($.debtorsCtrl.intiInputModal(this))">
                                            </td>
                                            <td class="email_text" style="text-align: left">{{$email->template_message}}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-left-important">
                <div>
                    <label id="datePaymentLabel" for="datePayment" style="margin: 10px;"></label>
                    <input id="datePayment" name="datePayment" type="date" style="display: none"> <br>
                    <label id="discountPaymentLabel" for="discountPayment" style="margin: 10px"></label>
                    <input id="discountPayment" name="discountPayment" type="number" step="any" style="display: none" ><br>
                    <label id='dateAnswerLabel' for="dateAnswer" style="margin: 10px"></label>
                    <input id="dateAnswer" name="dateAnswer" type="date" style="display: none"><br>
                    <input type="hidden" name="debtor_money_on_day" id="debtor_money_on_day" value="">
                </div>
            </div>
        </div>
    </div>
</div>
