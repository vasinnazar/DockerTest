@extends('adminpanel')
@section('title') Запросы на удаление @stop
@section('subcontent')
<!--<button class="btn btn-default" data-toggle="modal" data-target="#searchRemoveRequestModal"><span class="glyphicon glyphicon-search"></span> Поиск</button>-->
<!--<button class="btn btn-default" disabled id="clearFilterBtn">Очистить фильтр</button>-->
<table id="removeRequestsTable" class="table table-borderless table-condensed table-striped">
    <thead>
        <tr>
            <th>Статус</th>
            <th>Дата</th>
            <th>Тип документа</th>
            <th>Номер документа</th>
            <th>Пользователь</th>
            <th>Комментарий</th>
            <th>Удалить</th>
        </tr>
    </thead>
</table>

<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="remreqInfoModal" aria-hidden="true" id="remreqInfoModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="remreqInfoModalLabel">Подробная информация</h4>
            </div>
            <div class="modal-body">
                <div id="remreqInfoHolder">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

@stop
@section('scripts')
<script src="{{asset('js/usersRequestsController.js')}}"></script>
<script>
    (function () {
        var tableCtrl = new TableController('removeRequests', [
            {data: '0', name: 'req_status'},
            {data: '1', name: 'req_created_at'},
            {data: '2', name: 'req_doc_type'},
            {data: '3', name: 'req_id_1c'},
            {data: '4', name: 'req_requester'},
            {data: '5', name: 'req_comment'},
            {data: '6', name: 'actions', searchable: false, orderable: false},
        ],{order:[[1,"desc"],[0,"asc"]]});
    })(jQuery);
</script>
@stop