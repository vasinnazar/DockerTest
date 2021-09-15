@extends('app')
@section('title') Обзор @stop
@section('content')
<?php
use App\OrderType;
use App\Order;
use Carbon\Carbon;
use App\MySoap;
use App\StrUtils;
?>
<div class="row">
    <div class="col-xs-12">
        @include('loans.summaryCustomerData')
        <br>
        <div class="row">
            <div class="col-xs-12 col-md-12">
                @if(!is_null($loan->claim->promocode))
                <h1><small>Промокод, добавленный к заявке:</small> {{$loan->claim->promocode->number}} @if(!$loan->canCloseWithPromocode() && $loan->claim->subdivision->is_terminal==0) <span class="label label-danger">Промокод не может быть применен</span>@endif </h1>
                @endif
                @if(!is_null($loan->promocode))
                <h1><small>Промокод, выданный при оформлении кредитного договора:</small> {{$loan->promocode->number}}</h1>
                @endif
                @if($loan->cc_call)
                <h1>Был звонок в КЦ о задержке оплаты</h1>
                @endif
                @if($loan->uki)
                <h1>Улучшение Кредитной Истории</h1>
                @endif
                @if(isset($debtor) && !is_null($debtor))
                <hr>
                <p>Ответственные за взыскание задолженности:</p>
                
                @if(array_key_exists('debtor_fio',$debtor) && $debtor['debtor_fio']!='')                
                <h4>{{$debtor['debtor_fio']}} - <b>{{$debtor['debtor_tel']}}</b> </h4>
                @endif
                
                @if(array_key_exists('debtor_fio_head',$debtor) && $debtor['debtor_fio_head']!='')
                <h4>{{$debtor['debtor_fio_head']}} - <b>{{$debtor['debtor_tel_head']}}</b> </h4>
                @endif
                
                <hr>
                @endif
                <?php
                $repsNum = count($repayments);
                $loanDays = ($repsNum == 0) ?
                        with(new Carbon($loan->created_at))->setTime(0, 0, 0)->diffInDays(Carbon::now()) :
                        $loan->created_at->setTime(0, 0, 0)->diffInDays($repayments[0]->created_at->setTime(0, 0, 0));
                if ($loanDays > $loan->time) {
                    $loanTrClass = 'bg-danger';
                } else {
                    $loanTrClass = '';
                }
                $lastActiveRep = null;
                $lastRep = null;
                if($repsNum>0){
                    $lastRep = $repayments[$repsNum-1];
                    if($lastRep->repaymentType->isSuzStock()){
                        $lastActiveRep = $repayments[$repsNum-2];
                    } else {
                        $lastActiveRep = $lastRep;
                    }
                }
                ?>
                @if($loan->canCloseWithPromocode($reqMoneyDet))
                <input type="hidden" value="{{$loan->claim->promocode_id}}" name="promocode_id"/>
                @else
                <input type="hidden" name="promocode_id"/>
                @endif
                <input type="hidden" value="{{config('options.promocode_discount')}}" name="promocode_discount"/>
                <input type="hidden" value="{{$loan->cc_call}}" name="cc_call"/>
                @if(is_array($ngbezdolgov))
                <div id="ngBezDolgov">
                @foreach($ngbezdolgov as $ndk=>$ndv)
                <input type='hidden' value="{{$ndv}}" name="ngm_{{$ndk}}"/>
                @endforeach
                </div>
                @endif
                @if(isset($spisan))
                <input type='hidden' name='spisan' value='1' />
                @endif
                <div id="reqMoneyDetails" style="display: none">
                    @if($repsNum>0 && $lastActiveRep->repaymentType->isSUZ())
                    <input type="hidden" value="{{$lastActiveRep->pc}}" name="reqPc"/>
                    <input type="hidden" value="{{$lastActiveRep->exp_pc}}" name="reqExpPc"/>
                    <input type="hidden" value="{{$lastActiveRep->fine}}" name="reqFine"/>
                    <input type="hidden" value="{{$lastActiveRep->od}}" name="reqOD"/>
                    <input type="hidden" value="{{$lastActiveRep->money}}" name="reqMoney"/>
                    @else
                    <input type="hidden" value="{{$reqMoneyDet->pc}}" name="reqPc"/>
                    <input type="hidden" value="{{$reqMoneyDet->exp_pc}}" name="reqExpPc"/>
                    <input type="hidden" value="{{$reqMoneyDet->fine}}" name="reqFine"/>
                    <input type="hidden" value="{{$reqMoneyDet->od}}" name="reqOD"/>
                    <input type="hidden" value="{{$reqMoneyDet->uki}}" name="reqUki"/>
                    <input type="hidden" value="{{$reqMoneyDet->money}}" name="reqMoney"/>
                    @endif
                </div>
                @if(isset($exDopnikData) && !is_null($exDopnikData))
                <div id='exDopnikData'>
                    <input type='hidden' name='dopnik_create_date' value="{{$exDopnikData['dopnik_create_date']}}"  />
                    <input type='hidden' name='date' value="{{$exDopnikData['date']}}"  />
                    <input type='hidden' name='percent' value="{{$exDopnikData['percent']}}"  />
                    <input type='hidden' name='can_use_discount' value="{{$exDopnikData['can_use_discount']}}"  />
                </div>
                @endif
                <table id="contractsTable" class="table table-borderless table-condensed contracts-table">
                    <tbody>
                        <tr class="contract-header-row">
                            <td colspan="11">Кредитный договор №{{$loan->id_1c}} &laquo;{{$loan->loantype->name}}&raquo; (дней пользования: {{$loanDays}}) @if($loanDays > $loan->time)( дней просрочки: {{$loanDays-$loan->time}} )@endif @if($loan->closed) <span class="label label-success">Закрыт</span> @endif</td>
                        </tr>
                        <tr>
                            <th>Тип</th>
                            <th>Дата начала</th>
                            <th>Дата окончания</th>
                            <th>Сумма займа</th>
                            <th>Срок</th>
                            <th>Проценты</th>
                            <th>Просроченные проценты</th>
                            <th>Пеня</th>
                            <th>Госпошлина</th>
                            <th>Общая задолженность</th>
                            <th></th>
                        </tr>
                        <tr class="{{$loanTrClass}} contract-row">
                            @if($repsNum==0)
                            <td>{{$reqMoneyDet->percent}}%, пр.{{$reqMoneyDet->exp_percent}}%, пеня {{$percents['fine_pc']}}%</td>
                            @else
                            <td>{{$percents['pc']}}%, пр.{{$percents['exp_pc']}}%, пеня {{$percents['fine_pc']}}%</td>
                            @endif
                            <td class="highlighted contract-date">{{with(new Carbon($loan->created_at))->format('d.m.Y')}}</td>
                            <td class="highlighted contract-end-date">{{with(new Carbon($loan->created_at))->addDays($loan->time)->format('d.m.Y')}}</td>
                            <td class="highlighted contract-money">@if($repsNum==0){{$reqMoneyDet->od/100}}@else{{$loan->money}}@endif руб.</td>
                            <td class="highlighted contract-time">{{$loan->time}} д.</td>
                            <td class="contract-pc">@if($repsNum==0){{$reqMoneyDet->pc / 100}} руб. @else {{$repayments[0]->was_pc/100}} руб. @endif</td>
                            <td class="contract-exp-pc">@if($repsNum==0){{$reqMoneyDet->exp_pc / 100}} руб. @else {{$repayments[0]->was_exp_pc/100}} руб. @endif</td>
                            <td class="contract-fine">@if($repsNum==0){{$reqMoneyDet->fine / 100}} руб. @else {{$repayments[0]->was_fine/100}} руб. @endif</td>
                            <td class="contract-tax">0 руб.</td>
                            <td class="highlighted contract-req-money">
                                @if($repsNum==0){{$reqMoneyDet->money / 100}} руб.
                                @else {{($repayments[0]->was_od+$repayments[0]->was_pc+$repayments[0]->was_exp_pc+$repayments[0]->was_fine)/100}} руб.
                                @endif
                            </td>
                            <?php $unusedOrders = $loan->getUnusedOrders(); ?>
                            <td>
                                <button onclick="$.summaryCtrl.editLoan({{$loan->id}}); return false;" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-pencil"></span></button>
                                <a href="{{url('contracts/pdf/'.$loan->loantype->contract_form_id.'/'.$loan->claim_id)}}" target="_blank" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-print"></span></a>
                                @if(is_null($loan->claimed_for_remove) && $loan->created_at->isToday() && !$loan->in_cash)
                                <button class="btn btn-xs btn-default remove-claim-btn" title="Подать заявку на удаление" onclick="$.uReqsCtrl.claimForRemove({{$loan->id}},{{App\MySoap::ITEM_LOAN}}); return false;"><span class="glyphicon glyphicon-exclamation-sign"></span></button>
                                @elseif(!is_null($loan->claimed_for_remove))
                                <button disabled class="btn btn-xs btn-danger" title="Подать заявку на удаление"><span class="glyphicon glyphicon-exclamation-sign"></span></button>
                                @else
                                <button disabled class="btn btn-xs btn-default" title="Подать заявку на удаление"><span class="glyphicon glyphicon-exclamation-sign"></span></button>
                                @endif                                
                                <button class="btn btn-default btn-xs show-contract-orders-btn">
                                    <span class="glyphicon glyphicon-chevron-down"></span>
                                    <span style="display: none" class="glyphicon glyphicon-chevron-up"></span>
                                </button>
                            </td>
                        </tr>
                        <tr style="display: none" class="contract-orders-row">
                            <td colspan="11">
                                <ol class="list-group">
                                    @foreach($loan->orders as $order)
                                    @if(is_null($order->repayment_id))
                                    <li class="list-group-item">
                                        {{$order->orderType->name}} № {{$order->number}} от {{with(new Carbon($order->created_at))->format('d.m.Y')}} 
                                        ({{$order->getPurposeName()}})
                                        на сумму {{$order->money/100}} руб.
                                        <div class="btn-group">
                                            <a href="{{url('orders/pdf/'.$order->id)}}" target="_blank" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-print"></span></a>
                                            @if(Auth::user()->isAdmin())
                                            <button class="btn btn-default btn-sm" onclick="$.ordersCtrl.editOrder({{$order->id}}); return false;"><span class="glyphicon glyphicon-pencil"></span></button>
                                            @if(with(new Carbon($order->created_at))->setTime(0,0,0)->ne(Carbon::now()->setTime(0,0,0)))
                                            <button class="btn btn-sm" disabled><span class="glyphicon glyphicon-remove"></span></button>
                                            @else
                                            <a href="{{url('orders/remove/'.$order->id)}}" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-remove"></span></a>
                                            @endif
                                            @endif
                                        </div>
                                    </li>
                                    @endif
                                    @endforeach
                                </ol>
                            </td>
                        </tr>
                        @for($i=0; $i<$repsNum; $i++)
                        <?php
                        if ($i < $repsNum - 1) {
                            $repDays = with(new Carbon($repayments[$i]->created_at))->setTime(0, 0, 0)->diffInDays(with(new Carbon($repayments[$i + 1]->created_at))->setTime(0, 0, 0));
                        } else if (!$repayments[$i]->repaymentType->isClosing()) {
                            $repDays = with(new Carbon($repayments[$i]->created_at))->setTime(0, 0, 0)->diffInDays(Carbon::now());
                        }
                        ?>
                        <tr class="contract-header-row">
                            <td colspan="11">
                                {{$repayments[$i]->repaymentType->name}} №{{$repayments[$i]->id_1c}}
                                @if(isset($repDays))
                                (дней пользования: {{$repDays}})
                                @endif 
                                @if(isset($repDays) && $repDays>$repayments[$i]->time && !$repayments[$i]->repaymentType->isClosing())
                                ( дней просрочки: {{$repDays-$repayments[$i]->time}} )
                                @endif
                                @if($repayments[$i]->repaymentType->isSUZ())
                                <small>Ответственный: {{$repayments[$i]->comment}}</small>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Тип</th>
                            <th>Дата начала</th>
                            <th>Дата окончания</th>
                            <th>Сумма займа</th>
                            <th>Срок</th>
                            <th>Проценты</th>
                            <th>Просроченные проценты</th>
                            <th>Пеня</th>
                            <th>Госпошлина</th>
                            <th>Общая задолженность</th>
                            <th></th>
                        </tr>
                        <?php
                        if (!$repayments[$i]->repaymentType->isClosing() && ((with(new Carbon($repayments[$i]->created_at))->setTime(0, 0, 0)->diffInDays(Carbon::now()) > $repayments[$i]->time) || ($i < $repsNum - 1 && with(new Carbon($repayments[$i]->created_at))->setTime(0, 0, 0)->diffInDays(with(new Carbon($repayments[$i + 1]->created_at))->setTime(0, 0, 0)) > $repayments[$i]->time) || (!$repayments[$i]->isActive()))) {
                            $repTrClass = 'bg-danger';
                        } else if ($i == $repsNum - 1) {
                            $repTrClass = 'bg-success';
                        } else {
                            $repTrClass = '';
                        }
                        ?>
                        <tr class="{{$repTrClass}} contract-row">
                            <td class="contract-name">
                                {{$repayments[$i]->repaymentType->name}}
                                <button class="btn btn-default btn-xs" onclick="$.summaryCtrl.showComment({{$repayments[$i]->id}}); return false;" title="Комментарий"><span class="glyphicon glyphicon-info-sign"></span></button>
                            </td>
                            <td class="contract-date highlighted">
                                @if($repayments[$i]->repaymentType->isDopnik() && $repayments[$i]->created_at->gte(new Carbon(config('options.new_rules_day'))))
                                {{with(new Carbon($repayments[$i]->created_at))->addDay()->format('d.m.Y')}}
                                @else
                                {{with(new Carbon($repayments[$i]->created_at))->format('d.m.Y')}}
                                @endif
                            </td>
                            <td class="contract-end-date highlighted">
                                @if($repayments[$i]->repaymentType->isPeace())
                                {{$repayments[$i]->getEndDate()->format('d.m.Y')}}
                                @elseif($repayments[$i]->repaymentType->isDopnik())
                                {{with(new Carbon($repayments[$i]->created_at))->setTime(0,0,0)->addDays($repayments[$i]->time)->format('d.m.Y')}}
                                @elseif($repayments[$i]->repaymentType->isClaim())
                                @if($repayments[$i]->repaymentType->isDopCommission())
                                {{with(new Carbon($repayments[$i]->created_at))->setTime(0,0,0)->addDays($repayments[$i]->time)->format('d.m.Y')}}
                                @else
                                {{with(new Carbon($repayments[$i]->created_at))->setTime(0,0,0)->addDays($repayments[$i]->time)->format('d.m.Y')}}
                                @endif
                                @elseif($repayments[$i]->repaymentType->isSUZ())
                                
                                @else
                                {{with(new Carbon($repayments[$i]->created_at))->setTime(0,0,0)->addDays($repayments[$i]->time+1)->format('d.m.Y')}}
                                @endif
                            </td>
                            @if($repayments[$i]->repaymentType->isSuzStock())
                            <td class="contract-od highlighted">{{$repayments[$i]->od/100}} руб.</td>
                            <td class="contract-time highlighted">{{$repayments[$i]->time}} д.</td>
                            <td class="contract-pc">{{$repayments[$i]->pc/100}} руб.</td>
                            <td class="contract-exp-pc">{{$repayments[$i]->exp_pc/100}} руб.</td>
                            <td class="contract-fine">{{$repayments[$i]->fine/100}} руб.</td>
                            <td class="contract-tax">{{$repayments[$i]->tax/100}} руб.</td>
                            @if(isset($spisan))
                            <td class="contract-req-money highlighted">{{\App\StrUtils::kopToRub($spisan)}} руб.</td>
                            @else
                            <td class="contract-req-money highlighted">{{($repayments[$i]->req_money)/100}} руб.</td>
                            @endif
                            @elseif($repayments[$i]->repaymentType->isSUZ())
                            <td class="contract-od highlighted">{{$repayments[$i]->od/100}} руб.</td>
                            <td class="contract-time highlighted"></td>
                            <td class="contract-pc">{{$repayments[$i]->pc/100}} руб.</td>
                            <td class="contract-exp-pc">{{$repayments[$i]->exp_pc/100}} руб.</td>
                            <td class="contract-fine">{{$repayments[$i]->fine/100}} руб.</td>
                            <td class="contract-tax">{{$repayments[$i]->tax/100}} руб.</td>
                            @if(isset($spisan))
                            <td class="contract-req-money highlighted">{{\App\StrUtils::kopToRub($spisan)}} руб.</td>
                            @else
                            <td class="contract-req-money highlighted">{{\App\StrUtils::kopToRub($repayments[$i]->req_money)}} руб.</td>
                            @endif
                            @elseif($repayments[$i]==$lastActiveRep)
                            <td class="contract-od highlighted">{{$reqMoneyDet->od / 100}}руб.</td>
                            <td class="contract-time highlighted">{{$repayments[$i]->time}} д.</td>
                            <td class="contract-pc">{{$reqMoneyDet->pc / 100}} руб.</td>
                            <td class="contract-exp-pc">{{$reqMoneyDet->exp_pc / 100}} руб.</td>
                            <td class="contract-fine">{{$reqMoneyDet->fine / 100}} руб.</td>
                            <td class="contract-tax">{{$reqMoneyDet->tax / 100}} руб.</td>
                            <td class="contract-req-money highlighted">{{$reqMoneyDet->money / 100}} руб.</td>
                            @else
                            <td class="contract-od highlighted">{{$repayments[$i]->od/100}} руб.</td>
                            <td class="contract-time highlighted">{{$repayments[$i]->time}} д.</td>
                            <td class="contract-pc">{{$repayments[$i]->pc/100}}({{($repayments[$i]->pc - round($repayments[$i]->pc*($repayments[$i]->discount/100)))/100}}) руб.</td>
                            <td class="contract-exp-pc">{{$repayments[$i]->exp_pc/100}} руб.</td>
                            <td class="contract-fine">{{$repayments[$i]->fine/100}} руб.</td>
                            <td class="contract-tax">0 руб.</td>
                            <td class="contract-req-money highlighted">{{$repayments[$i]->req_money/100}} руб.</td>
                            @endif
                            <td class="contract-actions">
                                @if(Auth::user()->isAdmin())
                                <button onclick="$.summaryCtrl.editRepayment({{$repayments[$i]->id}}, @if($repayments[$i]->repaymentType->isPeace()) 1 @else 0 @endif); return false;" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-pencil"></span></button>
                                @endif
                                @if($repayments[$i]->repaymentType->isClaim() || $repayments[$i]->repaymentType->isDopnik() || $repayments[$i]->repaymentType->isPeace() || $repayments[$i]->repaymentType->isSuzStock() || ($repayments[$i]->repaymentType->isSUZ() && $repayments[$i]->isArhivUbitki()))
                                @if($repayments[$i]->repaymentType->isPeace() && $repayments[$i]->created_at->lt(Carbon::now()->subDay()) && !Auth::user()->isAdmin())
                                <button onclick='$.app.openErrorModal("Внимание!","Печать мировых, созданных не сегодня - недоступна."); return false;' class="btn btn-xs btn-default"><span class="glyphicon glyphicon-print"></span></button>
                                @else
                                <a href="{{url('contracts/pdf/'.$repayments[$i]->getPrintFormId().'/'.$loan->claim_id.'/'.$repayments[$i]->id)}}" target="_blank" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-print"></span></a>
                                @endif
                                @endif
<!--                                печатать заявление о возврате только если предыдущий док был тоже допом 
                                с комиссией и его дата окончания была больше текущего дока-->
                                @if($repayments[$i]->created_at->gte(new Carbon('2017-01-30')))
                                @if(($repayments[$i]->repaymentType->isDopCommission() || $repayments[$i]->repaymentType->isClosing()) && ((($i-1)>=0 && $repayments[$i-1]->repaymentType->isDopCommission() && $repayments[$i]->created_at->lt($repayments[$i-1]->getEndDate())) || Auth::user()->isAdmin()))
                                <a href="{{url('contracts/pdf/'.$repayments[$i]->getDopCommissionCashbackPrintFormId().'/'.$loan->claim_id.'/'.$repayments[$i]->id)}}" title="Заявление о возврате" target="_blank" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-print"></span></a>
                                @endif
                                @endif
                                @if($repayments[$i]->canBeClaimedForRemove())
                                <button class="btn btn-xs btn-default remove-claim-btn" title="Подать заявку на удаление" onclick="$.uReqsCtrl.claimForRemove({{$repayments[$i]->id}},{{$repayments[$i]->repaymentType->getMySoapItemID()}}); return false;"><span class="glyphicon glyphicon-exclamation-sign"></span></button>
                                @elseif(!is_null($repayments[$i]->claimed_for_remove))
                                <button disabled class="btn btn-xs btn-danger" title="Подать заявку на удаление"><span class="glyphicon glyphicon-exclamation-sign"></span></button>
                                @else
                                <button disabled class="btn btn-xs btn-default" title="Подать заявку на удаление"><span class="glyphicon glyphicon-exclamation-sign"></span></button>
                                @endif
                                <button class="btn btn-default btn-xs show-contract-orders-btn">
                                    <span class="glyphicon glyphicon-chevron-down"></span>
                                    <span style="display: none" class="glyphicon glyphicon-chevron-up"></span>
                                </button>
                            </td>
                        </tr>
                        <tr style="display: none" class="contract-orders-row">
                            <td colspan="12">
                                <ol class="list-group">
                                    @foreach($repayments[$i]->orders as $order)
                                    @if(is_null($order->peace_pay_id))
                                    <li class="list-group-item">
                                        {{$order->orderType->name}} № {{$order->number}} от {{with(new Carbon($order->created_at))->format('d.m.Y')}} ({{$order->getPurposeName()}})
                                        на сумму {{$order->money/100}} 
                                        @if($order->purpose==Order::P_PC)({{$repayments[$i]->discount}}%)@endif 
                                        руб.
                                        <div class="btn-group">
                                            <a href="{{url('orders/pdf/'.$order->id)}}" target="_blank" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-print"></span></a>
                                            @if(Auth::user()->isAdmin())
                                            <button class="btn btn-default btn-sm" onclick="$.ordersCtrl.editOrder({{$order->id}}); return false;"><span class="glyphicon glyphicon-pencil"></span></button>
                                            @if(with(new Carbon($order->created_at))->setTime(0,0,0)->ne(Carbon::now()->setTime(0,0,0)))
                                            <button class="btn btn-sm btn-default" disabled><span class="glyphicon glyphicon-remove"></span></button>
                                            @else
                                            <a href="{{url('orders/remove/'.$order->id)}}" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-remove"></span></a>
                                            @endif
                                            @endif
                                            @if($order->canBeClaimedForRemove())
                                            <button onclick="$.uReqsCtrl.claimForRemove({{$order->id}},@if($order->orderType->plus) {{MySoap::ITEM_PKO}} @else {{MySoap::ITEM_RKO}}@endif); return false;" 
                                                    class="btn btn-default btn-sm">
                                        <span class="glyphicon glyphicon-exclamation-sign"></span>
                                            </button>
                                            @elseif(!is_null($order->claimed_for_remove))
                                            <button disabled class="btn btn-danger btn-sm" title="Было запрошено удаление"><span class="glyphicon glyphicon-exclamation-sign"></span></button>
                                            @else
                                            <button disabled class="btn btn-default btn-sm"><span class="glyphicon glyphicon-exclamation-sign"></span></button>
                                            @endif  
                                        </div>
                                    </li>
                                    @endif
                                    @endforeach
                                </ol>
                                @if($repayments[$i]->repaymentType->isPeace())
                                @if($repayments[$i]->isActive())
                                @include('loans.summaryPeaceRow')
                                @else
                                <div class="alert alert-danger">
                                    <h1>
                                        Внимание! Соглашение просрочено и теряет свою силу. Проценты и пеня начисляются!
                                    </h1>
                                </div>
                                @endif
                                @endif
                            </td>
                        </tr>
                        @endfor
                        @if(is_array($ngbezdolgov))
                        <tr>
                            <td colspan="11" class="contract-header-row">
                                @if($akcsuzst46)
                                Акция СУЗ Статья 46
                                @else
                                Внимание на клиента действует акция "В новый год без долгов" при ЕДИНОВРЕМЕННОМ погашении задолженности
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Сумма без акции</td>
                            <td></td>
                            <td></td>
                            <td class="striked">{{StrUtils::kopToRub($reqMoneyDet->od)}}</td>
                            <td></td>
                            <td class="striked">{{StrUtils::kopToRub($reqMoneyDet->pc)}}</td>
                            <td class="striked">{{StrUtils::kopToRub($reqMoneyDet->exp_pc)}}</td>
                            <td class="striked">{{StrUtils::kopToRub($reqMoneyDet->fine)}}</td>
                            <td class="striked">{{StrUtils::kopToRub($reqMoneyDet->tax)}}</td>
                            <td class="striked">{{StrUtils::kopToRub($reqMoneyDet->money)}}</td>                            
                            <td></td>                            
                        </tr>
                        <tr>
                            <td>Сумма по акции</td>
                            <td></td>
                            <td></td>
                            <td><?php echo (array_key_exists('od', $ngbezdolgov))?StrUtils::kopToRub($ngbezdolgov['od']):'' ?></td>
                            <td></td>
                            <td><?php echo (array_key_exists('pc', $ngbezdolgov))?StrUtils::kopToRub($ngbezdolgov['pc']):'' ?></td>
                            <td><?php echo (array_key_exists('exp_pc', $ngbezdolgov))?StrUtils::kopToRub($ngbezdolgov['exp_pc']):'' ?></td>
                            <td><?php echo (array_key_exists('fine', $ngbezdolgov))?StrUtils::kopToRub($ngbezdolgov['fine']):'' ?></td>
                            <td><?php echo (array_key_exists('tax', $ngbezdolgov))?StrUtils::kopToRub($ngbezdolgov['tax']):'' ?></td>
                            <td><?php echo (array_key_exists('total', $ngbezdolgov))?StrUtils::kopToRub($ngbezdolgov['total']):'' ?></td>
                            <td></td>
                        </tr>
                        @endif
                    </tbody>
                </table>
                
                <div id="loanRepaymentsTools" style="text-align: center">
                    @if(Carbon::now()->subDays(106)->gte($loan->created_at) && $loan->created_at->gte(new Carbon('2016-03-29')))
                    @if((isset($lastActiveRep) && ($lastActiveRep->repaymentType->isDopnik() || $lastActiveRep->repaymentType->isClaim())) || !isset($lastActiveRep))
                    <h3 class='alert alert-danger centered'>Внимание! Прошло 106 дней со дня заключения договора.</h3>
                    @endif
                    @elseif($reqMoneyDet->odx4)
                        <h3 class="alert alert-danger">Внимание! Превышена максимальная сумма процентов. Создание дополнительного договора невозможно.</h3>
                    @endif
                    @if($repsNum>0 && $lastRep->repaymentType->isDopnik() && $lastRep->time>=1 && $lastRep->time<=3)
                        <h3 class="alert alert-danger">Внимание! Последний документ - дополнительное соглашение прошлым днем! Необходимо сделать либо закрытие, либо дополнительное соглашение.</h3>
                    @endif
                    @if(!($repsNum>0 && $lastRep->repaymentType->isClosing()))
                    <button class="btn btn-default add-order-btn" data-toggle="modal" data-target="#addOrderModal" title="Добавляет приходник к последнему договору">
                        <span class="glyphicon glyphicon-plus"></span> Добавить ПКО
                    </button>
                    @foreach($rtypes as $rt)
                    <button class="btn add-repayment-btn <?php echo ($rt->isExDopnik())?'btn-success':'btn-default' ?>" data-rtype-id="{{$rt->id}}" 
                            data-loan-id="{{$loan->id}}" data-min-money="{{$reqMoneyDet->all_pc}}" data-rtype-def-time="{{$rt->default_time}}"
                            data-rtype-text-id="{{$rt->text_id}}" data-rtype-freeze-days="{{$rt->freeze_days}}" data-rtype-type="{{$rt->getType()}}">
                        <span class="glyphicon glyphicon-plus"></span> {{$rt->name}}
                        @if($rt->isClosing() && is_array($ngbezdolgov))
                        (Акция)
                        @endif
                    </button>
                    @endforeach
                    @endif
                    @if(Auth::user()->isAdmin() && $repsNum>0)
                    @if(with(new Carbon($lastRep->created_at))->setTime(0,0,0)->ne(Carbon::now()->setTime(0,0,0)))
                    <button class="btn btn-default" disabled>
                        <span class="glyphicon glyphicon-remove"></span> Удалить последний
                    </button>
                    @else
                    <a href="{{url('repayments/remove/'.$lastRep->id)}}" class="btn btn-default" onclick="$.app.blockScreen(true);">
                        <span class="glyphicon glyphicon-remove"></span> Удалить последний
                    </a>
                    @endif
                    @endif
                    @if(((in_array(Auth::user()->id,[268,330]) && is_array($ngbezdolgov)) || in_array(Auth::user()->id,[817,516]) || Auth::user()->isAdmin()) && isset($lastActiveRep) && $lastActiveRep->repaymentType->isSUZ())
                    <button class='btn btn-default' type='button' data-toggle="modal" data-target='#addSuzScheduleModal'>Добавить график в СУЗ</button>
                    @endif
                </div>
                <br>
                <br>
            </div>
        </div>
    </div>
</div>
@include('loans.summaryModals')
@include('elements.editOrderModal')
@include('elements.claimForRemoveModal')
@stop
@section('scripts')
<script src="{{asset('js/usersRequestsController.js')}}"></script>
<script src="{{ asset('js/dashboard/photosController.js') }}"></script>
<script src="{{ asset('js/loan/summaryController.js?14') }}"></script>
<script src="{{ asset('js/orders/ordersController.js') }}"></script>
<script>
                                (function () {
                                $.ordersCtrl.init();
                                        $.summaryCtrl.init();
                                })(jQuery);
</script>
@stop