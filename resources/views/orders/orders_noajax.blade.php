@extends('app')
@section('title') Кассовые операции @stop
@section('content')
<?php
use App\MySoap;
use Carbon\Carbon;
?>
<button class="btn btn-default" data-toggle="modal" data-target="#searchOrderModal"><span class="glyphicon glyphicon-search"></span> Поиск</button>
<button class="btn btn-default" disabled id="clearFilterBtn">Очистить фильтр</button>
<a href="#" data-toggle="modal" data-target="#editOrderModal" class="btn btn-default"><span class="glyphicon glyphicon-plus"></span> Добавить</a>
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
    <tbody>
        @foreach($orders as $o)
        <tr>
            <td>{{with(new Carbon($o->order_created_at))->format('d.m.Y H:i:s')}}</td>
            <td>{{$o->order_type}}</td>
            <td>{{$o->order_number}}</td>
            <td>{{$o->order_money}}</td>
            <td>{{$o->responsible}}</td>
            <td>{{$o->customer_fio}}</td>
            <td>
                <div class="btn-group">
                    <a href="{{url('orders/pdf/' . $o->order_id)}}" class="btn btn-default btn-sm" target="_blank"><span class="glyphicon glyphicon-print"></span></a>
                    @if (is_null($o->order_claimed_for_remove) && with(new Carbon($o->order_created_at))->setTime(0, 0, 0)->eq(Carbon::now()->setTime(0, 0, 0)))
                    <button onclick="$.uReqsCtrl.claimForRemove({{$o->order_id}},{{(($o->order_plus) ? MySoap::ITEM_PKO : MySoap::ITEM_RKO)}})"
                            class="btn btn-default btn-sm"><span class="glyphicon glyphicon-exclamation-sign"></span></button>
                    @else
                    @if (!is_null($o->order_claimed_for_remove))
                    <button disabled class="btn btn-danger btn-sm"  title="Было запрошено удаление"><span class="glyphicon glyphicon-exclamation-sign"></span></button>
                    @else
                    <button disabled class="btn btn-default btn-sm"><span class="glyphicon glyphicon-exclamation-sign"></span></button>
                    @endif
                    @endif
                    @if (Auth::user()->isAdmin())
                    <button onclick="$.ordersCtrl.editOrder({{$o->order_id}}); return false;" class="btn btn-default btn-sm edit-order-btn"><span class="glyphicon glyphicon-pencil"></span></button>
                    <a href="{{url('orders/remove/' . $o->order_id)}}" class="btn btn-default btn-sm"> <span class="glyphicon glyphicon-remove"></span></a>
                    @endif
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
{!! $orders->render() !!}

<div class="modal fade" id="searchOrderModal" tabindex="-1" role="dialog" aria-labelledby="searchOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="searchOrderModalLabel">Поиск ордера</h4>
            </div>
            {!!Form::open(['url'=>url('orders'),'method'=>'get','class'=>'form-horizontal','id'=>'ordersFilter'])!!}
            <div class="modal-body">
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
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type='submit' id="ordersFilterBtn">
                    <span class="glyphicon glyphicon-search"></span> Поиск
                </button>
            </div>
            {!!Form::close()!!}
        </div>
    </div>
</div>

@include('elements.editOrderModal')
@include('elements.claimForRemoveModal')

@stop
@section('scripts')
<script src="{{asset('js/usersRequestsController.js')}}"></script>
@stop
@stop