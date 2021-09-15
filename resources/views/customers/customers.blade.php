@extends('app')
@section('title') Физ. лица @stop
@section('content')
<a class="btn btn-default" href="#" data-toggle="modal" data-target="#searchCustomerModal"><span class="glyphicon glyphicon-search"></span> Поиск</a>
<button class="btn btn-default" id="clearCustomersFilterBtn" disabled>Очистить фильтр</button>
<a class="btn btn-default" href="{{url('customers/create')}}"><span class="glyphicon glyphicon-plus"></span> Добавить</a>
<table id="customersTable" class="table table-borderless table-condensed table-striped">
    <thead>
        <tr>
            <th></th>
            <th>ФИО</th>
            <th>Дата рождения</th>
            <th>Серия</th>
            <th>Номер</th>
            <th>Телефон</th>
            <th>Номер карты</th>
            <th></th>
        </tr>
    </thead>
</table>

<div class="modal fade" id="searchCustomerModal" tabindex="-1" role="dialog" aria-labelledby="searchCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="customersFilter">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="searchCustomerModalLabel">Поиск физ.лица</h4>
            </div>
            <div class="modal-body">
                <form class="row">
                    <div class="form-group-sm col-xs-6">
                        <label>Серия</label>
                        <input name="series" placeholder="XXXX" pattern="[0-9]{4}" class="form-control" data-minlength="4" maxlength="4"/>
                    </div>
                    <div class="form-group-sm col-xs-6">
                        <label>Номер</label>
                        <input name="number" placeholder="XXXXXX" pattern="[0-9]{6}" class="form-control" data-minlength="6" maxlength="6"/>
                    </div>
                    <div class="form-group-sm col-xs-12">
                        <label>ФИО</label>
                        <input name="fio" class="form-control"/>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="customersFilterBtn" data-dismiss="modal">
                    <span class="glyphicon glyphicon-search"></span> Поиск
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="cardsListModal" tabindex="-1" role="dialog" aria-labelledby="cardsListModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="cardsListModalLabel">Карты</h4>
            </div>
            <div class="modal-body">
                <div id="cardsListHolder" class="list-group">

                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="addCardModal" tabindex="-1" role="dialog" aria-labelledby="addCardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="addCardModalLabel">Добавить карту вручную <small>(используется если в 1С карта есть, а на сайте нет)</small></h4>
            </div>
            {!! Form::open(['route'=>'ajax.cards.add', 'class'=>'form-horizontal']) !!}
            <div class="modal-body">
                <div class="form-group">
                    <label class="control-label col-sm-3">Номер карты</label>
                    <div class="col-sm-9">
                        {!! Form::text('card_number',null,['class'=>'form-control']) !!}
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3">Секретное слово</label>
                    <div class="col-sm-9">
                        {!! Form::text('secret_word',null,['class'=>'form-control']) !!}
                    </div>
                </div>
                {!! Form::hidden('customer_id') !!}

            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="submit" onclick="$.custCtrl.addCard(); return false;">Сохранить</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
@stop
@section('scripts')
<script src="{{asset('js/customers/customerController.js')}}"></script>
<script>
(function () {
    var tableCtrl = new TableController('customers', [
        {data: '0', name: 'customer_id'},
        {data: '1', name: 'fio'},
        {data: '2', name: 'birth_date'},
        {data: '3', name: 'series'},
        {data: '4', name: 'number'},
        {data: '5', name: 'telephone'},
        {data: '6', name: 'card_number'},
        {data: '7', name: 'actions', searchable: false, orderable: false},
    ], {clearFilterBtn: $('#clearCustomersFilterBtn')});
})(jQuery);

</script>
@stop