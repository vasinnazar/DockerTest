<?php
use App\OrderType;
use App\Order;
use Carbon\Carbon;
use App\MySoap;
?>
<table class="table table-borderless table-condensed">
    <thead>
        <tr>
            <td colspan="6" class="contract-header-row">График платежей</td>
        </tr>
        <tr>
            <th>Дата</th><th>Просроч. проценты</th><th>Пеня</th><th>Общая задолжность</th><th></th>
        </tr>
    </thead>
    <tbody>
        <?php $peacePays = ($i == $repsNum - 1) ? $reqMoneyDet->peace_pays : $repayments[$i]->peacePays;
        $curPayMoney = 0;
        $closestMonth = false; ?>
        @foreach($peacePays as $pay)
        <?php
        if (Carbon::now()->subDay()->gt(new Carbon($pay->end_date)) && $pay->total > 0) {
            $curPayMoney += $pay->total;
            $payTrClass = 'bg-danger';
        } else {
            $payTrClass = '';
        }
        if (Carbon::now()->lt(new Carbon($pay->end_date)) && !$closestMonth) {
            $closestMonth = true;
            $curPayMoney += $pay->total;
        }
        ?>
        <tr class='{{$payTrClass}}'>
            <td>{{with(new Carbon($pay['end_date']))->format('d.m.Y')}}</td>
            <td>{{$pay['exp_pc']/100}} руб.</td>
            <td>{{$pay['fine']/100}} руб.</td>
            <td>{{$pay['total']/100}}  руб.</td>
            <td>
                @if(count($pay->orders)>0)
                <button class="btn btn-default btn-xs show-contract-orders-btn">
                    <span class="glyphicon glyphicon-chevron-down"></span>
                    <span style="display: none" class="glyphicon glyphicon-chevron-up"></span>
                </button>
                @endif
                @if(Auth::user()->isAdmin())
                <a href="#" onclick="$.summaryCtrl.editPeacePay({{$pay['id']}}); return false;" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a>
                @endif
            </td>
        </tr>
        @if(count($pay->orders)>0)
        <tr class="contract-orders-row" style="display: none">
            <td colspan="12">
                @foreach($pay->orders as $order)
                @if($order->peace_pay_id==$pay->id)
                {{$order->orderType->name}} № {{$order->number}} от {{with(new Carbon($order->created_at))->format('d.m.Y')}} ({{$order->getPurposeName()}}) 
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
                @endif
                @endforeach
            </td>
        </tr>
        @endif
        @endforeach
    </tbody>
</table>