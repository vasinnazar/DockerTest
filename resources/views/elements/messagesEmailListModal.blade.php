<form action="{{route('email.send')}}" method="post">
    <input type="hidden" name="_token" value="{{csrf_token()}}">
    <input type="hidden" name="debtor_id" id="debtor_id" value="">
    <div class="row">
        <div class="form-group col-xs-12">
            @if(!empty($collectEmailsMessages))
                <label for="emails">Выберите тип сообщения</label>
                <select name="email_id" id="emailsList" onchange="($.debtorsCtrl.intiInputModal(this))">
                    @foreach($collectEmailsMessages as $emailMessage)
                        <option value="{{$emailMessage->name}}">
                            {{$emailMessage->name}} {{$emailMessage->template_message}}
                        </option>
                    @endforeach
                </select>
            @endif
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
    <button type="submit" class="btn btn-success">
        Отправить
    </button>
    <script>
        let debtId = $('#debtCardId').val();
        $('#debtor_id').val(debtId);
    </script>
</form>

