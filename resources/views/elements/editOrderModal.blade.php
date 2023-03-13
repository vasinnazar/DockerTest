<?php

use App\OrderType,
    App\Order;
?>
<div class="modal fade" id="editOrderModal" tabindex="-1" role="dialog" aria-labelledby="editOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="editOrderModalLabel">Редактирование ордера</h4>
            </div>
            {!!Form::open(['route'=>'orders.update','method'=>'post', 'id'=>'editOrderForm'])!!}
            {!! Form::hidden('id')!!}
            {!! Form::hidden('passport_id')!!}
            {!! Form::hidden('issue_claim_data')!!}
            <div class="modal-body">
                <div class="row">
                    <!--                    <div class="form-group col-xs-12 col-md-6">
                                            <label class="control-label">Дата</label>
                                            <input type="date" name="created_at" class="form-control"/>
                                        </div>-->
                    <div class="col-xs-12 issue-claim-alert">
                        
                    </div>
                    <div class="form-group col-xs-12 col-md-6">
                        <label class="control-label">Тип</label>
                        <?php $issueOrderTypes = \App\IssueClaim::getIssueOrderTypesTextIds(); ?>
                        @if(isset($order_types))
                        <select name="type" class="form-control">
                            @foreach($order_types as $type)
                            @if($type->text_id!='CARD' && $type->text_id!='RKO')
                            @if(in_array($type->text_id,$issueOrderTypes))
                            <option data-text-id="{{$type->text_id}}" value="{{$type->id}}" data-is-issue="1" >{{$type->name}}</option>
                            @else
                            <option data-text-id="{{$type->text_id}}" value="{{$type->id}}" data-is-issue="0" >{{$type->name}}</option>
                            @endif
                            @endif
                            @endforeach
                        </select>
                        @else
                        {!! Form::select('type',OrderType::pluck('name', 'id'),null,['class'=>'form-control']) !!}
                        @endif
                    </div>
                    <div class='form-group col-xs-12'>
                        <table id="issueClaimDetailsTable" class="table">
                            <thead>
                                <tr>
                                    <th>Цели, на которые выдается сумма в подотчет</th>
                                    <th>Сумма</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input name="ic_goal" type="text" class="form-control"/></td>
                                    <td><input name="ic_money" class="form-control money"/></td>
                                    <td style="min-width: 100px;">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-default" name="add"><span class="glyphicon glyphicon-plus"></span></button>
                                            <button class="btn btn-default" name="remove"><span class="glyphicon glyphicon-trash"></span></button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="form-group col-xs-12 col-md-6 purpose-holder" style="display: none">
                        <label class="control-label">Счёт</label>
                        <select name="purpose" class="form-control">
                            <option value=""></option>
                            @foreach(Order::getInvoicesNamesList() as $k=>$v)
                            @if(Auth::user()->isAdmin() || $k!=0)
                            <option value="{{$k}}">{{$v}}</option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-xs-12 col-md-12" id="editOrderModalMoneyHolder">
                        <label class="control-label">Сумма</label>
                        {!! Form::text('money',null,['class'=>'form-control money','data-mask'=>'#0.00','data-mask-reverse'=>'true','required'=>'required']) !!}
                    </div>
                    <div class="form-group col-xs-12 col-md-12">
                        {!! Form::hidden('passport_series') !!}
                        {!! Form::hidden('passport_number') !!}
                        <div class="input-group">
                            <input type="text" name="contragent_fio" class="form-control" disabled>
                            <span class="input-group-btn">
                                <a class="btn btn-default" data-toggle="modal" data-target="#findCustomersModal">
                                    <span class="glyphicon glyphicon-search"></span> Поиск контрагента
                                </a>
                            </span>
                        </div>
                    </div>
                    @if(Auth::user()->isAdmin())
                    <div class="form-group col-xs-12 col-md-12">
                        <label class="control-label">Номер</label>
                        {!! Form::text('number',null,['class'=>'form-control']) !!}
                    </div>
                    <div class="form-group col-xs-12 col-md-12">
                        <label class="control-label">Дата</label>
                        {!! Form::text('created_at',null,['class'=>'form-control']) !!}
                    </div>
                    <div class="form-group col-xs-12">
                        <label>Подразделение</label>
                        <input type='hidden' name='subdivision_id' />
                        <input type='text' name='subdivision_id_autocomplete' class='form-control' data-autocomplete='subdivisions' />
                    </div>
                    <div class="form-group col-xs-12">
                        <label>Специалист</label>
                        <input type='hidden' name='user_id' />
                        <input type='text' name='user_id_autocomplete' class='form-control' data-autocomplete='users' />
                    </div>
                    @endif
                    <div class="form-group col-xs-12 col-md-12">
                        <label class="control-label">Основание</label>
                        {!! Form::text('reason',null,['class'=>'form-control']) !!}
                    </div>
                    <div class="form-group col-xs-12 col-md-12">
                        <label class="control-label">Комментарий</label>
                        {!! Form::text('comment',null,['class'=>'form-control']) !!}
                    </div>
                    @if(Auth::user()->isAdmin())
                    <div class="form-group col-xs-12 col-md-12">
                        <label class="control-label">ID 1C Кредитника</label>
                        {!! Form::text('loan_id_1c',null,['class'=>'form-control']) !!}
                    </div>
                    @endif
                </div>
                <br>
            </div>
            <div class="modal-footer">

                {!!Form::button('Сохранить',['class'=>'btn btn-primary','type'=>'submit'])!!}
            </div>
            {!!Form::close()!!}
        </div>
    </div>
</div>
@include('elements.findCustomerModal')
<script src="{{URL::asset('js/orders/ordersController.js?6')}}"></script>
<script>
(function ($) {
    $.ordersCtrl.init();
})(jQuery);
</script>
