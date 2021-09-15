@extends('app')
@section('title') Договоры НПФ @stop
@section('content')
<a class="btn btn-default" href="#" data-toggle="modal" data-target="#searchNpfModal"><span class="glyphicon glyphicon-search"></span> Поиск</a>
<button class="btn btn-default" id="clearNpfsFilterBtn" disabled>Очистить фильтр</button>
<a class="btn btn-default" href="{{url('npf/create')}}"><span class="glyphicon glyphicon-plus"></span> Добавить</a>
<table id="npfTable" class="table table-borderless table-condensed table-striped">
    <thead>
        <tr>
            <th></th>
            <th>Дата</th>
            <th>Фонд</th>
            <th>ФИО</th>
            <th>СНИЛС</th>
            <th></th>
        </tr>
    </thead>
</table>

<div class="modal fade" id="searchNpfModal" tabindex="-1" role="dialog" aria-labelledby="searchNpfModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="npfFilter">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="searchNpfModalLabel">Поиск договора</h4>
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
                <button class="btn btn-primary" id="npfFilterBtn" data-dismiss="modal">
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
    var tableCtrl = new TableController('npf', [
        {data: '0', name: 'npfid'},
        {data: '1', name: 'npfdate'},
        {data: '2', name: 'fondname'},
        {data: '3', name: 'fio'},
        {data: '4', name: 'snils'},
        {data: '5', name: 'actions', searchable: false, orderable: false},
    ], {clearFilterBtn: $('#clearNpfsFilterBtn')});
})(jQuery);

</script>
@stop