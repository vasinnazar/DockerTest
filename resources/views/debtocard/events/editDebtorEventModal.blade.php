<?php

use App\OrderType,
    App\Order;
?>
<div class="modal fade" id="editDebtorEventModal" tabindex="-1" role="dialog" aria-labelledby="editDebtorEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="editDebtorEventModalLabel">Редактирование мероприятия</h4>
            </div>
            {!!Form::open(['url'=>url('debtors/event/update'), 'method'=>'post', 'id'=>'editDebtorEventForm'])!!}
            {!! Form::hidden('id') !!}
            <input type="hidden" name="debtor_id" value="{{$debtor->id}}">
            <div class="modal-body">
                <div class='form-horizontal'>
                    <div class='form-group'>
                        <label class='col-xs-12 col-sm-4 text-right'>Тип мероприятия:</label>
                        <div class='col-xs-12 col-sm-8'>
                            <select name="event_type_id" class="form-control">
                                <option value=""></option>
                                @foreach ($debtdata['event_types'] as $k => $type)
                                <option value="{{$k}}">{{$type}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class='form-group'>
                        <label class='col-xs-12 col-sm-4 text-right'>Дата мероприятия:</label>
                        <div class='col-xs-12 col-sm-8 form-inline'>
                            <input type="text" name="created_at" value="{{date('d.m.Y H:i', time())}}" class="form-control" readonly>
                        </div>
                    </div>
                    <div class='form-group'>
                        <label class='col-xs-12 col-sm-4 text-right'>Дата планирования мероприятия:</label>
                        <div class='col-xs-12 col-sm-8 form-inline'>
                            <input type="text" name="date" value="{{date('d.m.Y H:i', time())}}" class="form-control">
                        </div>
                    </div>
                    <div class='form-group'>
                        <label class='col-xs-12 col-sm-4 text-right'>Ответственный:</label>
                        <div class='col-xs-12 col-sm-8 form-inline'>
                            <input name="users@name" type="text" class="form-control autocomplete ui-autocomplete-input" data-hidden-value-field="search_field_users@id_1c" autocomplete="off" style="width: 100%;">
                            <input name="search_field_users@id_1c" type="hidden" value="">
                        </div>
                    </div>
                    <div class='form-group'>
                        <label class='col-xs-12 col-sm-4 text-right'>Причина просрочки:</label>
                        <div class='col-xs-12 col-sm-8'>
                            <select name="overdue_reason_id" class="form-control">
                                <option value=""></option>
                                @foreach ($debtdata['overdue_reasons'] as $k => $type)
                                <option value="{{$k}}">{{$type}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class='form-group'>
                        <label class='col-xs-12 col-sm-4 text-right'>Группа долга:</label>
                        <div class='col-xs-12 col-sm-8'>
                            <select name="debt_group_id" class="form-control">
                                <option value=""></option>
                                @foreach ($debtdata['debt_groups'] as $k => $type)
                                <option value="{{$k}}">{{$type}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class='form-group'>
                        <label class='col-xs-12 col-sm-4 text-right'>Результат:</label>
                        <div class='col-xs-12 col-sm-8'>
                            <select name="event_result_id" class="form-control">
                                <option value=""></option>
                                @foreach ($debtdata['event_results'] as $k => $type)
                                <option value="{{$k}}">{{$type}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class='form-group'>
                        <label class='col-xs-12 col-sm-4 text-right'>Отчет о мероприятии:</label>
                        <div class='col-xs-12 col-sm-8'>
                            <textarea style="min-height: 150px;" name="report" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                {!!Form::button('Сохранить',['class'=>'btn btn-primary','type'=>'submit'])!!}
            </div>
            {!!Form::close()!!}
        </div>
    </div>
</div>
@include('elements.findCustomerModal')
<script src="{{URL::asset('js/orders/ordersController.js?2')}}"></script>
<script>
(function ($) {
    $.ordersCtrl.init();
})(jQuery);
</script>