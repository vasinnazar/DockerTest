<?php

use App\OrderType;
use App\Order;
use Carbon\Carbon;
use App\MySoap;
?>
<div class="modal fade" id="editRepaymentModal" tabindex="-1" role="dialog" aria-labelledby="editRepaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="editRepaymentModalLabel">Редактирование документа</h4>
            </div>
            {!!Form::open(['route'=>'repayments.update','method'=>'post'])!!}
            <input type="hidden" name="id"/>
            <div class="modal-body">
                <div class="row">
<!--                                        <div class="form-group col-xs-12 col-md-4">
                                            <label>Дата</label>
                                            <input type="date" name="created_at" class="form-control repayment-date" />
                                        </div>
                                        <div class="form-group col-xs-12 col-md-3">
                                            <label>Сумма взноса (руб.)</label>
                                            <input type="text" name="paid_money" class="form-control repayment-paid-money money"/>
                                        </div>-->
                    <div class="form-group col-xs-12 col-md-2">
                        <label>Срок (дни)</label>
                        <input type="number" name="time" class="form-control repayment-time" min="1"/>
                    </div>
                    <div class="form-group col-xs-12 col-md-3">
                        <label>Срок (месяцы)</label>
                        <input type="number" name="months" class="form-control repayment-time" min="1"/>
                    </div>
                    @if(Auth::user()->isAdmin())
                    <div class='form-group col-xs-12'>
                        <label>Пр.проценты на момент заключения</label>
                        <input name='was_exp_pc' class='form-control money' />
                    </div>
                    <div class='form-group col-xs-12'>
                        <label>Проценты на момент заключения</label>
                        <input name='was_pc' class='form-control money' />
                    </div>
                    <div class='form-group col-xs-12'>
                        <label>ОД на момент заключения</label>
                        <input name='was_od' class='form-control money' />
                    </div>
                    <div class='form-group col-xs-12'>
                        <label>Пеня на момент заключения</label>
                        <input name='was_fine' class='form-control money' />
                    </div>
                    @endif
                    <!--                    <div class="form-group col-xs-12 col-md-3">
                                            <label>Скидка</label>
                                            <select name="discount" class="form-control repayment-discount">
                                                <option value="0">0</option>
                                                <option value="5">5%</option>
                                                <option value="10">10%</option>
                                                <option value="20">20%</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-xs-12 col-md-6">
                                            <label>Сумма остатка процентов (руб.)</label>
                                            <input type="text" name="pc" class="form-control repayment-pc money"/>
                                        </div>
                                        <div class="form-group col-xs-12 col-md-6">
                                            <label>Сумма остатка пр. проц. (руб.)</label>
                                            <input type="text" name="exp_pc" class="form-control repayment-exp-pc money"/>
                                        </div>
                                        <div class="form-group col-xs-12 col-md-6">
                                            <label>Сумма остатка пени (руб.)</label>
                                            <input type="text" name="fine" class="form-control repayment-fine money"/>
                                        </div>
                                        <div class="form-group col-xs-12 col-md-6">
                                            <label>Сумма остатка ОД (руб.)</label>
                                            <input type="text" name="od" class="form-control repayment-fine money"/>
                                        </div>-->
                </div>
            </div>
            <div class="modal-footer">
                {!!Form::button('Сохранить',['class'=>'btn btn-primary','type'=>'submit'])!!}
            </div>
            {!!Form::close()!!}
        </div>
    </div>
</div>
<div class="modal fade" id="addOrderModal" tabindex="-1" role="dialog" aria-labelledby="addOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="addOrderModalLabel">Добавление ПКО</h4>
            </div>
            {!!Form::open(['route'=>'repayments.payment','method'=>'post'])!!}
            <input type="hidden" name="loan_id" value="{{$loan->id}}"/>
            <input type="hidden" name="type" value="{{OrderType::getPKOid()}}"/>
            <input type="hidden" name="passport_id" value="{{$loan->claim->passport->id}}"/>
            @if($repsNum>0)
            @if($repayments[$repsNum-1]->repaymentType->isSuzStock())
            <input type="hidden" name="repayment_id" value="{{$repayments[$repsNum-2]->id}}"/>
            @else
            <input type="hidden" name="repayment_id" value="{{$repayments[$repsNum-1]->id}}"/>
            @endif
            @else
            <input type="hidden" name="repayment_id" value=""/>
            @endif
            <input type="hidden" name="used" value="0"/>
            <div class="modal-body">
                <div class="row">
                    @if(isset($curPayMoney) && $curPayMoney>0)
                    <div class="col-xs-12">
                        Рекомендуемый платёж: {{\App\StrUtils::kopToRub($curPayMoney)}} руб.
                        <br>
                        <br>
                    </div>
                    @endif
                    @if(Auth::user()->isAdmin())
                    <div class="form-group col-xs-12 col-md-12">
                        <label>Дата</label>
                        <input type="date" name="created_at" class="form-control"/>
                    </div>
                    @endif
                    <div class="form-group col-xs-12 col-md-12">
                        <label>Сумма (руб.)</label>
                        <input type="text" name="money" class="form-control money"/>
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
<div class="modal fade" id="editPeacePayModal" tabindex="-1" role="dialog" aria-labelledby="editPeacePayModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="editPeacePayModalLabel">Редактирование приходника</h4>
            </div>
            {!!Form::open(['route'=>'peacepays.update','method'=>'post'])!!}
            <input type="hidden" name="repayment_id" value="{{($repsNum>0)?$repayments[$repsNum-1]->id:''}}"/>
            <input type="hidden" name="id"/>
            <div class="modal-body">
                <div class="row">
                    <div class="form-group col-xs-12 col-md-4">
                        <label>Дата</label>
                        <input type="date" name="end_date" class="form-control"/>
                    </div>
                    <div class="form-group col-xs-12 col-md-3">
                        <label>Сумма (руб.)</label>
                        <input type="text" name="money" class="form-control money"/>
                    </div>
                    <div class="form-group col-xs-12 col-md-3">
                        <label>Просроч. проц.</label>
                        <input type="text" name="exp_pc" class="form-control money"/>
                    </div>
                    <div class="form-group col-xs-12 col-md-2">
                        <label>Пеня</label>
                        <input type="text" name="fine" class="form-control money"/>
                    </div>
                    <div class="form-group col-xs-12 col-md-4">
                        <label>Общая сумма</label>
                        <input type="text" name="total" class="form-control money"/>
                    </div>
                    <div class="form-group col-xs-12 col-md-3">
                        <input type="checkbox" name="closed" class="checkbox" id="peacepayClosedCb"/>
                        <label for="peacepayClosedCb">Закрыт</label>
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
<!--ФОРМА ДОБАВЛЕНИЯ НОВОГО ДОГОВОРА-->
<div class="modal fade" id="addRepaymentModal" tabindex="-1" role="dialog" aria-labelledby="addRepaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="addRepaymentModalLabel">Добавление договора</h4>
            </div>
            {!!Form::open(['route'=>'repayments.create','method'=>'post'])!!}
            <input type="hidden" name="loan_id" value="{{$loan->id}}"/>
            <input type="hidden" name="repayment_type_id"/>
            <div class="modal-body">
                <div class="row">
                    @if($loan->canUseDiscount((isset($exDopnikData))?$exDopnikData:null))
                    @if(!is_null($loan->claim->promocode) && $loan->claim->promocode->isAvailable(true,$loan->claim_id))
                    <div class="form-group col-xs-12 col-md-12">
                        <span>Скидка на проценты по промокоду с номером {{$loan->claim->promocode->number}}</span>
                    </div>
                    @elseif($percents['pc']==2 || ($repsNum>0 && $reqMoneyDet->percent == 2) || (isset($exDopnikData) && is_array($exDopnikData) && array_key_exists('percent',$exDopnikData) && $exDopnikData['percent']==2) || Auth::user()->isAdmin())
                    <div class="form-group col-xs-12 col-md-2">
                        <label>Скидка</label>
                        <select name="discount" class="form-control repayment-discount">
                            <option value="0">0</option>
                            <option value="20">20%</option>
                            @if(
                            ($loan->created_at->gte(new Carbon('2016-06-29')) && $loan->created_at->lt(new Carbon('2016-07-01'))) ||
                            ($loan->created_at->gte(new Carbon('2016-07-27')) && $loan->created_at->lt(new Carbon('2016-07-28'))) ||
                            ($loan->created_at->gte(new Carbon('2016-08-08')) && $loan->created_at->lte(new Carbon('2016-08-10')))
                            )
                            <option value="25">25%</option>
                            @endif
                        </select>
                    </div>
                    @endif
                    @endif
                    @if($loan->uki && $loan->isUkiActive())
                    <div class="form-group col-xs-12 col-md-12">
                        <span>Комиссия НБКИ - {{number_format(config('options.uki_money')/100,2,'.','')}} руб.</span>
                    </div>
                    @endif
                    @if(isset($exDopnikData) && !is_null($exDopnikData))
<!--                    <div class="form-group col-xs-12">
                        <div class="well">
                            <p>Процент допника: {{$exDopnikData['percent']}}</p>
                            <p>Можно ли использовать скидку: {{$exDopnikData['can_use_discount']}}</p>
                            <p>Дата: {{$exDopnikData['date']}}</p>
                        </div>
                    </div>-->
                    @endif
                    @if(Auth::user()->isAdmin())
                    <div class="form-group col-xs-12">
                        <label>Отнять промокод если очень нужно</label>
                        <input name="promocode_anyway" type="checkbox" />
                    </div>
                    <div class="form-group col-xs-12">
                        <label>Включить скидку</label>
                        <input name="discount_anyway" type="checkbox" />
                    </div>
                    <div class='form-group col-xs-12'>
                        <label>Специалист</label>
                        <input type='hidden' name='user_id' />
                        <input type='text' name='user_id_autocomplete' class='form-control' data-autocomplete='users' />
                    </div>
                    <div class='form-group col-xs-12'>
                        <label>Подразделение</label>
                        <input type='hidden' name='subdivision_id' />
                        <input type='text' name='subdivision_id_autocomplete' class='form-control' data-autocomplete='subdivisions' />
                    </div>
                    @endif
                    @if(isset($exDopnikData))
                    <div class="form-group col-xs-12">
                        <label>Дата создания</label>
                        <input type="date" name="create_date"  class="form-control"/>
                    </div>
                    @endif
                    @if(!isset($lastActiveRep) || !$lastActiveRep->repaymentType->isSUZ())
                    <div class="form-group col-xs-12 col-md-12" id="addRepaymentModalInfo"></div>
                    @if(Auth::user()->isAdmin())
                    <div class="form-group col-xs-12">
                        <table class="table table-condensed table-bordered">
                            <tr><td style="width: 35%">ОД</td><td id="addRepaymentModalDebt_od">{{\App\StrUtils::kopToRub($reqMoneyDet->od)}}</td></tr>
                            <tr><td>Пц</td><td id="addRepaymentModalDebt_pc">{{\App\StrUtils::kopToRub($reqMoneyDet->pc)}}</td></tr>
                            <tr><td>ППц</td><td id="addRepaymentModalDebt_exp_pc">{{\App\StrUtils::kopToRub($reqMoneyDet->exp_pc)}}</td></tr>
                            <tr><td>Пеня</td><td id="addRepaymentModalDebt_fine">{{\App\StrUtils::kopToRub($reqMoneyDet->fine)}}</td></tr>
                            <tr><td>Гп</td><td id="addRepaymentModalDebt_tax">{{\App\StrUtils::kopToRub($reqMoneyDet->tax)}}</td></tr>
                        </table>
                    </div>
                    @endif
                    <div class="form-group col-xs-12 col-md-12" id="dopCommissionInfo"></div>
                    @else
                    <div class="form-group col-xs-12 col-md-12"></div>
                    @endif
                    <div class="form-group col-xs-12 col-md-4">
                        <label>Сумма взноса (руб.)</label>
                        <input type="text" name="paid_money" class="form-control repayment-paid-money money"/>
                    </div>
                    <div class="form-group col-xs-12 col-md-3">
                        <label>Срок (дни)</label>
                        <input type="number" name="time" class="form-control repayment-time" min="1" value="1"/>
                    </div>
                    <div class="form-group col-xs-12 col-md-3">
                        <label>Кол-во месяцев</label>
                        <input type="number" name="months" class="form-control repayment-months" min="1" value="1"/>
                    </div>
                    <div class="form-group col-xs-12 col-md-4">
                        <label>Дата окончания</label>
                        <input type="date" name="end_date" disabled  class="form-control"/>
                    </div>
                    <div class="form-group col-xs-12 col-md-12">
                        <label>Комментарий</label>
                        <textarea name="comment" class="form-control repayment-comment"></textarea>
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
<div class="modal fade" id="loanEditorModal" tabindex="-1" role="dialog" aria-labelledby="loanEditorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="loanEditorModalLabel">Добавление приходника</h4>
            </div>
            {!!Form::open(['route'=>'loans.update','method'=>'post'])!!}
            <input type="hidden" name="id" value="{{$loan->id}}"/>
            <input type="hidden" name="claim_id"/>
            <div class="modal-body">
                <div class="row">
                    <div class="form-group col-xs-12 col-md-12">
                        <label>Дата</label>
                        <input type="date" name="created_at" class="form-control"/>
                    </div>
                    <div class="form-group col-xs-12 col-md-12">
                        <label>Остаток пени (руб.)</label>
                        <input type="text" name="fine" class="form-control money"/>
                    </div>
                    <div class="form-group col-xs-12 col-md-12">
                        <label>Дата последнего платежа</label>
                        <input type="date" name="last_payday" class="form-control"/>
                    </div>
                    <div class="form-group col-xs-12 col-md-12">
                        <input type="checkbox" name="closed" class="checkbox" id="loanClosedCb"/>
                        <label for="loanClosedCb">Закрыт</label>
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
<div class="modal fade" id="repaymentCommentModal" tabindex="-1" role="dialog" aria-labelledby="repaymentCommentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="repaymentCommentModalLabel">Комментарий</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12 col-md-12 repayment-comment">

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="addSuzScheduleModal" tabindex="-1" role="dialog" aria-labelledby="addSuzScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="addSuzScheduleModalLabel">Создание графика платежей для СУЗ</h4>
            </div>
            <div class="modal-body">
                <table id="addSuzScheduleTable" class="table">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Сумма</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input name="date" type="date" class="form-control"/></td>
                            <td><input name="total" class="form-control money"/></td>
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
            <div class='modal-footer'>
                <?php $suzData = (isset($lastActiveRep))?$lastActiveRep->getData():null; ?>
                {!! Form::open(['url'=>url('repayments/suzschedule/add'),'id'=>'addSuzScheduleForm']) !!}
                {!! Form::hidden('suzschedule',(isset($suzData->pays))?json_encode($suzData->pays):'') !!}
                {!! Form::hidden('loan_id',$loan->id) !!}
                @if(isset($lastActiveRep) && $lastActiveRep->repaymentType->isSUZ())
                <div class="row">
                    <div class="form-group col-xs-12">
                        <label><input name="change_suz" type="checkbox"/> Поменять суммы в СУЗ</label>
                    </div>
                    
                    @if($lastActiveRep->isArhivUbitki())
                    <div class='form-group col-xs-12 col-md-6 col-lg-4'>
                        <label>Сумма Госпошлины</label>
                        <input type="text" disabled name="tax" class="form-control money" value="{{$suzData->print_tax}}"/>
                    </div>
                    <div class='form-group col-xs-12 col-md-6 col-lg-4'>
                        <label>Сумма Пени,штрафов</label>
                        <input type="text" disabled name="fine" class="form-control money" value="{{$suzData->print_fine}}"/>
                    </div>
                    <div class='form-group col-xs-12 col-md-6 col-lg-4'>
                        <label>Сумма Пр. процентов</label>
                        <input type="text" disabled name="exp_pc" class="form-control money" value="{{$suzData->print_exp_pc}}"/>
                    </div>
                    <div class='form-group col-xs-12 col-md-6 col-lg-4'>
                        <label>Сумма Процентов</label>
                        <input type="text" disabled name="pc" class="form-control money" value="{{$suzData->print_pc}}"/>
                    </div>
                    <div class='form-group col-xs-12 col-md-6 col-lg-4'>
                        <label>Сумма ОД</label>
                        <input type="text" disabled name="od" class="form-control money" value="{{$suzData->print_od}}"/>
                    </div>
                    @else
                    <div class='form-group col-xs-12 col-md-6 col-lg-4'>
                        <label>Сумма Госпошлины</label>
                        <input type="text" disabled name="tax" class="form-control money" value="{{$lastActiveRep->tax}}"/>
                    </div>
                    <div class='form-group col-xs-12 col-md-6 col-lg-4'>
                        <label>Сумма Пени,штрафов</label>
                        <input type="text" disabled name="fine" class="form-control money" value="{{$lastActiveRep->fine}}"/>
                    </div>
                    <div class='form-group col-xs-12 col-md-6 col-lg-4'>
                        <label>Сумма Пр. процентов</label>
                        <input type="text" disabled name="exp_pc" class="form-control money" value="{{$lastActiveRep->exp_pc}}"/>
                    </div>
                    <div class='form-group col-xs-12 col-md-6 col-lg-4'>
                        <label>Сумма Процентов</label>
                        <input type="text" disabled name="pc" class="form-control money" value="{{$lastActiveRep->pc}}"/>
                    </div>
                    <div class='form-group col-xs-12 col-md-6 col-lg-4'>
                        <label>Сумма ОД</label>
                        <input type="text" disabled name="od" class="form-control money" value="{{$lastActiveRep->od}}"/>
                    </div>
                    @endif
                </div>
                @endif
                <hr>
                <button class='btn btn-primary' type='submit'>Сохранить</button>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>