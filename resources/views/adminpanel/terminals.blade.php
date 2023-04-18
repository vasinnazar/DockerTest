@extends('adminpanel')
@section('title') Терминалы @stop
@section('subcontent')
<div id="terminalsFilter" class="form-inline">
    <label>Название</label>
    <input class="form-control input-sm" name="description"/>
    <button class="btn btn-primary btn-sm" id="terminalsFilterBtn">
        <span class="glyphicon glyphicon-search"></span>
    </button>
    <a href="{{url('adminpanel/terminals/create')}}" class="btn btn-sm btn-default"><span class="glyphicon glyphicon-plus"></span> Добавить</a>
    <!--<button class="btn btn-default" id="getSubdivsBtn">Получить подразделения из 1С</button>-->
</div>
<table class="table table-borderless table-condensed table-striped" id="terminalsTable">
    <thead>
        <tr>
            <th>Наименование</th>
            <th>Статус</th>
            <th>Сумма для выдачи</th>
            <th>Кол-во купюр для выдачи</th>
            <th>Сумма внесенных средств</th>
            <th>Статус блокировки</th>
            <th></th>
        </tr>
    </thead>
</table>
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="incassModal" aria-hidden="true" id="incassModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="incassModalLabel">Инкассация</h4>
            </div>
            {!! Form::open(['route'=>'terminals.incass']) !!}
            <div class="modal-body">
                <div class="form-group">
                    <input type="hidden" name="id"/>
                    <label class="control-label">Сумма</label>
                    <input class="form-control" name="bill_cash"/>
                    <label class="control-label">Кол-во купюр</label>
                    <input class="form-control" name="bill_count"/>
                    <label class="control-label">Кол-во купюр изъятых из выдачи</label>
                    <input class="form-control" name="dispenser_count"/>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="incassBtn" type="submit">Сохранить</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="addCashModal" aria-hidden="true" id="addCashModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="addCashModalLabel">Пополнение</h4>
            </div>
            {!! Form::open(['route'=>'terminals.addcash']) !!}
            <div class="modal-body">
                <div class="form-group">
                    <input type="hidden" name="id"/>
                    <label class="control-label">Кол-во купюр</label>
                    <input class="form-control" name="dispenser_count"/>
                    <label class="control-label">Сумма</label>
                    <input class="form-control" name="dispenser_cash"/>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="incassBtn" type="submit">Сохранить</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-hidden="true" id="addCommandModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Добавить команду</h4>
            </div>
            {!! Form::open(['route'=>'adminpanel.terminals.command.add']) !!}
            <div class="modal-body">
                <div class="form-group">
                    <input type="hidden" name="point_id"/>
                    <label class="control-label">Команда</label>
                    <!--<input class="form-control" name="name"/>-->
                    <select name="name" class="form-control">
                        <option value="reboot">reboot</option>
                        <option value="update">update</option>
                        <option value="get">get</option>
                    </select>
                    <label class="control-label">Параметры [param,param,...,param]</label>
                    <input class="form-control" name="params"/>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="addCommandBtn" type="submit">Сохранить</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
@stop
@section('scripts')
<script src="{{asset('js/terminalsController.js')}}"></script>
<script>
    (function () {
        var tableCtrl = new TableController('terminals', [
            {data: 'name', name: 'name'},
            {data: 'status', name: 'status'},
            {data: 'dispenser_cash', name: 'dispenser_cash'},
            {data: 'dispenser_count', name: 'dispenser_count'},
            {data: 'bill_cash', name: 'bill_cash'},
            {data: 'lock_status', name: 'lock_status', orderable: false, searchable: false},
            {data: 'actions', name: 'actions', orderable: false, searchable: false},
        ], {listURL: 'ajax/adminpanel/terminals/list'});
    })(jQuery);
</script>
@stop
