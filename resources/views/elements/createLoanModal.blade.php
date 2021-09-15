<div class="modal fade" id="createLoanModal" tabindex="-1" role="dialog" aria-labelledby="createLoanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="createLoanModalLabel">Формирование кредитного договора и РКО</h4>
            </div>
            {!! Form::open(['route'=>'loans.create']) !!}
            {!! Form::hidden('claim_id') !!}
            {!! Form::hidden('customer_id') !!}
            <div class="modal-body">
                <div id="createLoanFormHolder">
                    <div class="row">
                        <div class="col-xs-12">
                            <label>Вид займа</label>
                            {!! Form::select('loantype_id',[],null,['class'=>'form-control']) !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <label>Сумма</label>
                            <input name="money" type="number" class="form-control" step="1000" min="1000"/>
                        </div>
                        <div class="col-xs-6">
                            <label>Срок <span class="loan-end-date-holder"></span></label>
                            <input name="time" type="number" class="form-control" step="1" min="1"/>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-xs-6">
                            <div class="form-group">
                                {!! Form::checkbox('promocode',1,null,['class'=>'checkbox','id'=>'promocodeCheckbox']) !!}
                                <label class="control-label" for="promocodeCheckbox">Получить промокод</label>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-xs-6">
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-default active" for="createLoanModalInCash">
                                    Наличными
                                    {!! Form::radio('in_cash','1',true,['id'=>'createLoanModalInCash']) !!}
                                </label>
                                <label class="btn btn-default" for="createLoanModalOnCard">
                                    На карту
                                    {!! Form::radio('in_cash','0',false,['id'=>'createLoanModalOnCard']) !!}
                                </label>
                            </div>
                        </div>
                        <div class="col-xs-6 hidden" id="cardInputHolder">
                            {!! Form::hidden('card_id') !!}
                            <div class="form-group">
                                {!! Form::checkbox('use_new_card_anyway_cb',1,null,['class'=>'checkbox','id'=>'useNewCardAnywayCb']) !!}
                                <label class="control-label" for="useNewCardAnywayCb">Все равно использовать новую карту</label>
                            </div>
                            <label>Номер карты</label>
                            <div class="input-group">
                                {!! Form::text('card_number',null,[
                                'placeholder'=>'2700XXXXXXXXX',
                                'pattern'=>'\d{13}', 'class'=>'form-control',
                                'minlength'=>'13','maxlength'=>'13'
                                ])!!}
                                <div class="input-group-btn">
                                    <button id="createNewCardBtn" class="btn btn-default"><span class="glyphicon glyphicon-plus"></span></button>
                                </div>
                            </div>
                            <label>Секретное слово</label>
                            {!! Form::text('secret_word',null,['class'=>'form-control']) !!}
                        </div>
                    </div>
                </div>
                <div id="cardApproveHolder" class="hidden">
                    <div class="btn-group" data-toggle="buttons">
                        <label class="btn btn-default" for="createLoanModalSMS">
                            Да
                            {!! Form::radio('gotSMS','1',false,['id'=>'createLoanModalSMS']) !!}
                        </label>
                        <label class="btn btn-default active" for="createLoanModalNoSMS">
                            Нет
                            {!! Form::radio('gotSMS','0',true,['id'=>'createLoanModalNoSMS','checked'=>'checked']) !!}
                        </label>
                    </div>
                    <label for="gotSMS" >Клиенту пришло SMS сообщение</label>
                </div>
            </div>
            <div class="modal-footer">
                {!! Form::submit('Далее',['class'=>'btn btn-primary']) !!}
                <!--<button type="button" class="btn btn-primary">Далее</button>-->
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>