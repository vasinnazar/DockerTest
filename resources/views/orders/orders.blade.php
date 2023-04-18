@extends('app')
@section('title') Кассовые операции @stop
@section('content')
<button class="btn btn-default" data-toggle="modal" data-target="#searchOrderModal"><span class="glyphicon glyphicon-search"></span> Поиск</button>
<button class="btn btn-default" disabled id="clearFilterBtn">Очистить фильтр</button>
<a href="#" data-toggle="modal" data-target="#editOrderModal" class="btn btn-default"><span class="glyphicon glyphicon-plus"></span> Добавить</a>
<button class="btn btn-default" id="repeatLastSearchBtn">Повторить последний запрос</button>
<a href="{{url('orders/issueclaims/index')}}" class='btn btn-link'>Заявки на ордера на подотчет</a>
<!--<a href="#" class="btn btn-default" onclick="$.ordersCtrl.uploadForToday(); return false;"><span class="glyphicon glyphicon-refresh"></span> Обновить за сегодня</a>-->

<table id="ordersTable" class="table table-borderless table-condensed table-striped">
    <thead>
        <tr>
            <th>Дата</th>
            <th>Тип</th>
            <th>Номер</th>
            <th>Сумма</th>
            <th>Ответственный</th>
            <th>Контрагент</th>
            <th></th>
        </tr>
    </thead>
</table>

<div class="modal fade" id="searchOrderModal" tabindex="-1" role="dialog" aria-labelledby="searchOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="searchOrderModalLabel">Поиск ордера</h4>
            </div>
            <div class="modal-body">
                {!!Form::open(['method'=>'post','class'=>'form-horizontal','id'=>'ordersFilter'])!!}
                {!!Form::hidden('subdivision_id',$subdivision_id,['class'=>'unchangeable'])!!}
                <div class="form-group">
                    <label class="col-sm-4 control-label">ФИО</label>
                    <div class="col-sm-8">
                        {!! Form::text('fio',null,['placeholder'=>'ФИО','class'=>'form-control'])!!}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Серия</label>
                    <div class="col-sm-8">
                        {!! Form::text('series',null,array(
                        'placeholder'=>'XXXX',
                        'pattern'=>'[0-9]{4}', 'class'=>'form-control',
                        'data-minlength'=>'4','maxlength'=>'4'
                        ))!!}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Номер</label>
                    <div class="col-sm-8">
                        {!! Form::text('number',null,array(
                        'placeholder'=>'XXXXXX',
                        'pattern'=>'[0-9]{6}', 'class'=>'form-control',
                        'data-minlength'=>'6', 'maxlength'=>'6'
                        ))!!}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Номер ордера</label>
                    <div class="col-sm-8">
                        {!! Form::text('order_number',null,['placeholder'=>'','class'=>'form-control'])!!}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Ответственный</label>
                    <div class="col-sm-8">
                        {!! Form::text('responsible',null,['placeholder'=>'','class'=>'form-control'])!!}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Дата с:</label>
                    <div class="col-sm-8">
                        <input type="date" class="form-control" name="created_at_min"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-4 control-label">Дата по:</label>
                    <div class="col-sm-8">
                        <input type="date" class="form-control" name="created_at_max"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label">Подразделение</label>
                    <div class="col-sm-9">
                        <input type='hidden' name='subdivision_id' />
                        <input type='text' name='subdivision_id_autocomplete' class='form-control' data-autocomplete='subdivisions' />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label">Тип</label>
                    <div class="col-sm-9">
                        <select name="plus" class="form-control">
                            <option value="">Все</option>
                            <option value="0">Расход</option>
                            <option value="1">Приход</option>
                        </select>
                    </div>
                </div>
                {!!Form::close()!!}
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" data-dismiss="modal" id="ordersFilterBtn">
                    <span class="glyphicon glyphicon-search"></span> Поиск
                </button>
            </div>
        </div>
    </div>
</div>
<div class="alert alert-info">
    Если Вы не можете найти какой-то ордер, попробуйте найти его через поиск, указав дату создания ордера в поле "Дата с"
</div>
@include('elements.editOrderModal')
@include('elements.claimForRemoveModal')


@stop
@section('scripts')
<script src="{{asset('js/usersRequestsController.js')}}"></script>
<script>
(function () {
    var tableCtrl = new TableController('orders', [
    {data: 'order_created_at', name: 'order_created_at'},
    {data: 'order_type', name: 'order_type'},
    {data: 'order_number', name: 'order_number'},
    {data: 'order_money', name: 'order_money'},
    {data: 'responsible', name: 'responsible'},
    {data: 'customer_fio', name: 'customer_fio'},
    {data: 'actions', name: 'actions', searchable: false, orderable: false},
    ],{
        repeatLastSearchBtn:$('#repeatLastSearchBtn')
    });
})(jQuery);
</script>
@stop
