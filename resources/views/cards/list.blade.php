@extends('app')
@section('title') Замена карт @stop
@section('content')
<a class="btn btn-default" href="#" data-toggle="modal" data-target="#searchCardChangeModal"><span class="glyphicon glyphicon-search"></span> Поиск</a>
<button class="btn btn-default" id="clearCardChangesFilterBtn" disabled>Очистить фильтр</button>
<button class="btn btn-default" data-toggle="modal" data-target="#addCardChangeModal"><span class="glyphicon glyphicon-plus"></span> Добавить</button>
<table id="cardChangesTable" class="table table-borderless table-condensed table-striped">
    <thead>
        <tr>
            <th></th>
            <th>Дата</th>
            <th>Номер старой карты</th>
            <th>Номер новой карты</th>
            <th>Пользователь</th>
            <th>Контрагент</th>
            <th></th>
        </tr>
    </thead>
</table>

<div class="modal fade" id="searchCardChangeModal" tabindex="-1" role="dialog" aria-labelledby="searchCardChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="customersFilter">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="searchCardChangeModalLabel">Поиск замены</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="form-group-sm col-xs-12">
                        <label>ФИО</label>
                        <input name="fio" class="form-control"/>
                    </div>
                    <div class="form-group-sm col-xs-12">
                        <label>Старая карта</label>
                        <input name="old_card_number" class="form-control"/>
                    </div>
                    <div class="form-group-sm col-xs-12">
                        <label>Новая карта</label>
                        <input name="new_card_number" class="form-control"/>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="cardChangesFilterBtn" data-dismiss="modal">
                    <span class="glyphicon glyphicon-search"></span> Поиск
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="addCardChangeModal" tabindex="-1" role="dialog" aria-labelledby="addCardChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="addCardChangeModalLabel">Заменить карту</h4>
            </div>
            {!! Form::open(['route'=>'cardchanges.add', 'class'=>'form-horizontal']) !!}
            <div class="modal-body">
                <div class="form-group">
                    <label class="control-label col-sm-3">Номер старой карты</label>
                    <div class="col-sm-9">
                        {!! Form::text('old_card_number',null,['class'=>'form-control']) !!}
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3">Номер новой карты</label>
                    <div class="col-sm-9">
                        {!! Form::text('new_card_number',null,['class'=>'form-control']) !!}
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3">Секретное слово</label>
                    <div class="col-sm-9">
                        {!! Form::text('secret_word',null,['class'=>'form-control']) !!}
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="submit">Сохранить</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
@stop
@section('scripts')
<script>
(function () {
    var tableCtrl = new TableController('cardChanges', [
        {data: '0', name: 'ccid'},
        {data: '1', name: 'ccdate'},
        {data: '2', name: 'ccold'},
        {data: '3', name: 'ccnew'},
        {data: '4', name: 'username'},
        {data: '5', name: 'fio'},
        {data: '6', name: 'actions', searchable: false, orderable: false},
    ], {clearFilterBtn: $('#clearCardChangesFilterBtn')});
})(jQuery);

</script>
@stop
@stop