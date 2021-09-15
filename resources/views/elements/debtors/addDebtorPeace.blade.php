<div class="modal fade" id="newDebtorPeace" tabindex="-1" role="dialog" aria-labelledby="newDebtorPeaceLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Мировое соглашение</h4>
            </div>
            <form action="/debtors/peaceclaim/new" method="POST">
                <div class="modal-body">
                    {{csrf_field()}}
                    <input type="hidden" name="repayment_type_id" value="14">
                    <input type="hidden" name="loan_id_1c" value="{{$debtor->loan_id_1c}}">
                    <input type="hidden" name="debtor_id" value="{{$debtor->id}}">
                    <div class="row">
                        <div class="col-xs-4">
                            Срок (мес.)
                        </div>
                        <div class="col-xs-8">
                            <input type="number" name="times" min="1" max="12" class="form-control" required>
                        </div>
                    </div>
                    <div class="row" style="padding-top: 15px;">
                        <div class="col-xs-4">
                            Сумма
                        </div>
                        <div class="col-xs-8">
                            <input type="text" name="amount" class="form-control" required>
                        </div>
                    </div>
                    <div class="row" style="padding-top: 15px;">
                        <div class="col-xs-4">
                            Действует до
                        </div>
                        <div class="col-xs-8">
                            <input type="date" name="end_at" class="form-control" required>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal" id="closeNewDebtorPeace">Закрыть</button>
                    <button type="submit" class="btn btn-primary" id="editNewDebtorPeace" onclick="$('#editNewDebtorPeace').prop('disabled');">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>