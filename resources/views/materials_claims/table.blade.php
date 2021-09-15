@extends('app')
@section('title') Заявления на материалы @stop
@section('content')
<a class="btn btn-default" href="#" data-toggle="modal" data-target="#searchMaterialClaimModal"><span class="glyphicon glyphicon-search"></span> Поиск</a>
<button class="btn btn-default" id="clearMaterialClaimsFilterBtn" disabled>Очистить фильтр</button>
<a class="btn btn-default" href="{{url('matclaims/create')}}"><span class="glyphicon glyphicon-plus"></span> Добавить</a>
<table id="matclaimsTable" class="table table-borderless table-condensed table-striped">
    <thead>
        <tr>
            <th>№</th>
            <th>Дата</th>
            <th>Дата исполнения</th>
            <th>Пользователь</th>
            <th>Подразделение</th>
            <th></th>
        </tr>
    </thead>
</table>

<div class="modal fade" id="searchMaterialClaimModal" tabindex="-1" role="dialog" aria-labelledby="searchMaterialClaimModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="matclaimsFilter">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="searchMaterialClaimModalLabel">Поиск заявления</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="form-group-sm col-xs-12">
                        <label>ФИО</label>
                        <input name="fio" class="form-control"/>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="matclaimsFilterBtn" data-dismiss="modal">
                    <span class="glyphicon glyphicon-search"></span> Поиск
                </button>
            </div>
        </div>
    </div>
</div>
@include('elements.claimForRemoveModal')
@stop
@section('scripts')
<script src="{{asset('js/usersRequestsController.js')}}"></script>
<script>
(function () {
    var tableCtrl = new TableController('matclaims', [
        {data: '0', name: 'matclaim_id'},
        {data: '1', name: 'matclaim_created_at'},
        {data: '2', name: 'matclaim_claim_date'},
        {data: '3', name: 'user_name'},
        {data: '4', name: 'subdivision_name'},
        {data: '5', name: 'actions', searchable: false, orderable: false},
    ], {clearFilterBtn: $('#clearMaterialClaimsFilterBtn')});
})(jQuery);

</script>
@stop