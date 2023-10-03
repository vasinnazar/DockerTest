<div class="modal fade" id="deleteDebtorEventModal" tabindex="-1" role="dialog" aria-labelledby="deleteDebtorEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="deleteDebtorEventModalLabel">Вы действительно хотите удалить мероприятие?</h4>
            </div>
            <div class="modal-body">
                <input id="eventId" type="hidden" value="">
                <input id="debtorId" type="hidden" value="">
                <input id="csrfToken" type="hidden" value="{{csrf_token()}}">
                <button type="submit" class="btn btn-primary" onclick="$.debtorsCtrl.deleteDebtorEvent();">Удалить</button>
                <button type="submit" class="btn btn-default" data-dismiss="modal" aria-hidden="true">Отмена</button>
            </div>
        </div>
    </div>
</div>