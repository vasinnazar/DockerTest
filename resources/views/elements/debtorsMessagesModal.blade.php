<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="debtorsMessagesModal" aria-hidden="true" id="debtorsMessagesModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Зачет оплат</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="form-group col-xs-12">
                        <?php $debtorsMessages = \App\DebtorsMessage::where('user_id')->get(); ?>
                        @foreach($debtorsMessages as $dm)
                        <div class='alert alert-success'>
                            {{$dm->message}}
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>