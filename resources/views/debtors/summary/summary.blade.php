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
        @include('debtors.summary.summaryCustomerData')
        <br>
        <div class="row">
            <div class="col-xs-12 col-md-12">
                <?php
                $repsNum = count($repayments);
                $loanDays = ($repsNum == 0) ?
                        with(new Carbon($loan->created_at))->setTime(0, 0, 0)->diffInDays(Carbon::now()) :
                        with(new Carbon($loan->created_at))->setTime(0, 0, 0)->diffInDays(with(new Carbon($repayments[0]->created_at))->setTime(0, 0, 0));
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
                <table id="contractsTable" class="table table-borderless table-condensed contracts-table">
                    <tbody>
                        <tr class="contract-header-row">
                            <td colspan="11">Кредитный договор №{{$loan->id_1c}} &laquo;{{$loantype->name}}&raquo; (дней пользования: {{$loanDays}}) @if($loanDays > $loan->time)( дней просрочки: {{$loanDays-$loan->time}} )@endif @if($loan->closed) <span class="label label-success">Закрыт</span> @endif</td>
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
@section('scripts')
<script src="{{ asset('js/dashboard/photosController.js') }}"></script>
<script src="{{ asset('js/loan/summaryController.js?14') }}"></script>
<script>
                                (function () {
                                        $.summaryCtrl.init();
                                })(jQuery);
</script>
@stop