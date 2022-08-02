<div
        class="modal fade income-call-modal"
        id="infinity-incoming-call"
        tabindex="-1"
        role="dialog"
        aria-labelledby="incomeCallingModalLabel"
        aria-hidden="true"
>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title text-center" id="incomeCallingModalLabel">Входящий звонок</h4>
            </div>
            <div class="modal-body">
                <div class='row text-center lg'>
                    <h2>Источник: <span id="infinity-incoming-call-source"></span></h2>
                </div>
                <div class='row text-center lg'>
                    <h2 id="infinity-incoming-call-fio"></h2>
                </div>
                <div class="row text-center lg">
                    <h4 id="infinity-incoming-call-phone"></h4>
                </div>
                <div class="row text-center lg">
                    <h4 id="infinity-incoming-responsibleUser"></h4>
                </div>
                <hr>
                <div class="row" style="display: flex; justify-content: space-between">
                    <div
                            id="infinity_call_accept"
                            class="btn btn-success btn-xl"
                            onclick="$.infinityController.openCard()"
                    >Открыть карточку
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
