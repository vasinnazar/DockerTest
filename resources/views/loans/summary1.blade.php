@extends('app')
@section('title') Обзор @stop
@section('content')
<?php

use App\OrderType;
use App\Order;
use Carbon\Carbon;
use App\MySoap;
use App\StrUtils;
//use Storage;
?>
<div class="row">
    <div class="col-xs-12">
        <div class="row">
            <h1 class="col-xs-12">
                {{$loan->claim->passport->fio}}
                @if($loan->claim->about_client->postclient)
                <small><label class="label label-success">Постоянный клиент</label></small>
                @endif
                @if(Auth::user()->isAdmin())
                <a class="btn btn-default" href="{{url('claims/edit/'.$loan->claim_id)}}">К заявке (№{{$loan->claim->id_1c}})</a>
                
                @endif
            </h1>
            <div class="col-xs-12 col-md-2 photo-gallery">
                @if(!is_null($photos))
                <div class="main-photo">
                    <img />
                    <button class="btn open-btn" style="position: absolute; top: 3px; right: 3px;" onclick="$.photosCtrl.openPhoto(this)"><span class="glyphicon glyphicon-eye-open"></span></button>
                </div>
                <div class="previews">
                    @foreach($photos as $p)
                    <img data-main="{{$p->is_main}}" src="data:image/'{{pathinfo(url($p->path), PATHINFO_EXTENSION)}};base64,{{base64_encode(Storage::get($p->path))}}" alt="{{$p->id}}" class="photo-preview" onclick="$.photosCtrl.makeMain(this); return false;" />
                    <!--<img data-main="{{$p->is_main}}" class="photo-preview" src="{{url($p->path)}}" alt="{{$p->id}}" />-->
                    @endforeach
                </div>
                @endif
            </div>
            <div class="col-xs-12 col-md-10">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#passportSummary" data-toggle="tab">Паспортные данные</a></li>
                    <li><a href="#phonesSummary" data-toggle="tab">Телефоны</a></li>
                    <li><a href="#workSummary" data-toggle="tab">Данные о работе</a></li>
                    <li><a href="#personalSummary" data-toggle="tab">Личные данные</a></li>
                    <li><a href="#claimsSummary" data-toggle="tab">Заявки</a></li>
                    <li><a href="#loansSummary" data-toggle="tab">Займы</a></li>
                </ul>
                <div class="tab-content" id="summaryTabContent">
                    <div class="tab-pane active" id="passportSummary">
                        <p>Серия: {{$loan->claim->passport->series}} Номер: {{$loan->claim->passport->number}}</p>
                        <p>Место рождения: {{$loan->claim->passport->birth_city}}</p>
                        <p>Кем выдан: {{$loan->claim->passport->issued}}</p>
                        <p>Код подразделения: {{$loan->claim->passport->subdivision_code}}</p>
                        <p>Дата выдачи: {{with(new Carbon($loan->claim->passport->issued_date))->format('d.m.Y')}}</p>
                    </div>
                    <div class="tab-pane" id="phonesSummary">
                        <p>Телефон: {{$loan->claim->customer->telephone}}</p>
                        @if(!is_null($loan->claim->about_client))
                        <p>
                            Домашний телефон:
                            {{$loan->claim->about_client->telephonehome}}
                        </p>
                        <p>
                            Телефон родственников:
                            {{$loan->claim->about_client->telephonerodstv}}
                        </p>
                        <p>
                            Другой телефон:
                            {{$loan->claim->about_client->anothertelephone}}
                        </p>
                        <p>
                            Степень родства:
                            @if(!is_null($loan->claim->relationDegree))
                            {{$loan->claim->about_client->relationDegree->name}}
                            @endif
                        </p>
                        @endif
                    </div>
                    <div class="tab-pane" id="workSummary">
                        @if(!is_null($loan->claim->about_client))
                        <p>
                            Должность: {{$loan->claim->about_client->dolznost}}
                        </p>
                        <p>
                            Вид труда: 
                            @if($loan->claim->about_client->vidtruda)
                            Официально
                            @else
                            Не официально
                            @endif
                        </p>
                        <p>
                            Организация: {{$loan->claim->about_client->organizacia}}
                        </p>
                        <p>
                            ИНН организации: {{$loan->claim->about_client->innorganizacia}}
                        </p>
                        <p>
                            ФИО руководителя: {{$loan->claim->about_client->fiorukovoditel}}
                        </p>
                        <p>
                            Адрес организации: {{$loan->claim->about_client->adresorganiz}}
                        </p>
                        <p>
                            Телефон организации: {{$loan->claim->about_client->telephoneorganiz}}
                        </p>
                        <p>
                            Доход: {{$loan->claim->about_client->dohod}}
                        </p>
                        <p>
                            Доп. доход: {{$loan->claim->about_client->dopdohod}}
                        </p>
                        <p>
                            Стаж: {{$loan->claim->about_client->stazlet}}
                        </p>
                        @endif
                    </div>
                    <div class="tab-pane" id="personalSummary">
                        @if(!is_null($loan->claim->about_client))
                        <p>
                            Пол: 
                            @if($loan->claim->about_client->sex)
                            Мужской
                            @else
                            Женский
                            @endif
                        </p>
                        @endif
                        <p>Дата рождения: {{with(new Carbon($loan->claim->passport->birth_date))->format('d.m.Y')}}</p>
                        <p>
                            Юридический адрес:
                            {{$loan->claim->passport->address_region}}, 
                            @if($loan->claim->passport->address_district!="")
                            {{$loan->claim->passport->address_district}}, 
                            @endif
                            {{$loan->claim->passport->address_city}}, 
                            {{$loan->claim->passport->address_street}}, 
                            {{$loan->claim->passport->address_house}} 
                            @if($loan->claim->passport->address_building!="")
                            , {{$loan->claim->passport->address_building}}
                            @endif
                            @if($loan->claim->passport->address_apartment!="")
                            , {{$loan->claim->passport->address_apartment}}
                            @endif
                        </p>
                        <p>
                            Фактический адрес:
                            {{$loan->claim->passport->fact_address_region}}, 
                            @if($loan->claim->passport->fact_address_district!="")
                            {{$loan->claim->passport->fact_address_district}}, 
                            @endif
                            {{$loan->claim->passport->fact_address_city}}, 
                            {{$loan->claim->passport->fact_address_street}}, 
                            {{$loan->claim->passport->fact_address_house}} 
                            @if($loan->claim->passport->fact_address_building!="")
                            , {{$loan->claim->passport->fact_address_building}}
                            @endif
                            @if($loan->claim->passport->fact_address_apartment!="")
                            , {{$loan->claim->passport->fact_address_apartment}}
                            @endif
                        </p>
                        @if(!is_null($loan->claim->about_client))
                        <p>
                            Автомобиль: 
                            @if($loan->claim->about_client->avto==0)
                            Нет
                            @elseif($loan->claim->about_client->avto==1)
                            Отечественный
                            @elseif($loan->claim->about_client->avto==2)
                            Иномарка
                            @endif
                        </p>
                        <p>
                            Жилищные условия:
                            @if(!is_null($loan->claim->about_client->liveCondition))
                            {{$loan->claim->about_client->liveCondition->name}}
                            @endif
                        </p>
                        <p>
                            Дети:
                            {{$loan->claim->about_client->deti}}
                        </p>
                        <p>
                            ФИО супруги:
                            {{$loan->claim->about_client->fiosuprugi}}
                        </p>
                        <p>
                            Предыдущее ФИО:
                            {{$loan->claim->about_client->fioizmena}}
                        </p>
                        <p>
                            Образование:
                            @if(!is_null($loan->claim->about_client->educationLevel))
                            {{$loan->claim->about_client->educationLevel->name}}
                            @endif
                        </p>
                        <p>
                            Семейное положение:
                            @if(!is_null($loan->claim->about_client->maritalType))
                            {{$loan->claim->about_client->maritalType->name}}
                            @endif
                        </p>
                        <p>
                            @if($loan->claim->about_client->pensioner)
                            <label class="label label-default">Пенсионер</label>
                            @endif
                            @if($loan->claim->about_client->armia)
                            <label class="label label-default">Служил в армии</label>
                            @endif
                            @if($loan->claim->about_client->poruchitelstvo)
                            <label class="label label-default">Является поручителем</label>
                            @endif
                            @if($loan->claim->about_client->zarplatcard)
                            <label class="label label-default">Зарплатная карта</label>
                            @endif
                            @if($loan->claim->about_client->postclient)
                            <label class="label label-default">Постоянный клиент</label>
                            @endif
                        </p>
                        <p>
                            Кредит:
                            {{$loan->claim->about_client->credit}}
                        </p>
                        @endif
                    </div>
                    <div class="tab-pane" id="claimsSummary">
                        <table id="claimsTable" class="table table-borderless table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Сумма</th>
                                    <th>Срок</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($claims as $item)
                                <tr>
                                    <td>{{with(new Carbon($item->created_at))->format('d.m.Y H:i:s')}}</td>
                                    <td>{{$item->summa}} руб.</td>
                                    <td>{{$item->srok}} д.</td>
                                    <td>{{\App\Claim::getStatusName($item->status)}}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="tab-pane" id="loansSummary">
                        <table id="claimsTable" class="table table-borderless table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Сумма</th>
                                    <th>Срок</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($loans as $item)
                                <tr>
                                    <td>{{with(new Carbon($item->created_at))->format('d.m.Y H:i:s')}}</td>
                                    <td>{{$item->money}} руб.</td>
                                    <td>{{$item->time}} д.</td>
                                    <td>@if($item->closed) Открыт @else Закрыт @endif</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-xs-12 col-md-12">
                @if(!is_null($loan->claim->promocode))
                <h1><small>Промокод, добавленный к заявке:</small> {{$loan->claim->promocode->number}}</h1>
                @endif
                @if(!is_null($loan->promocode))
                <h1><small>Промокод, выданный при оформлении кредитного договора:</small> {{$loan->promocode->number}}</h1>
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
                ?>
                <input type="hidden" value="{{$loan->claim->promocode_id}}" name="promocode_id"/>
                <input type="hidden" value="{{config('options.promocode_discount')}}" name="promocode_discount"/>
                <div id="reqMoneyDetails" style="display: none">
                    @if($repsNum>0 && $repayments[$repsNum-1]->repaymentType->isSUZ())
                    <input type="hidden" value="{{$repayments[$repsNum-1]->pc}}" name="reqPc"/>
                    <input type="hidden" value="{{$repayments[$repsNum-1]->exp_pc}}" name="reqExpPc"/>
                    <input type="hidden" value="{{$repayments[$repsNum-1]->fine}}" name="reqFine"/>
                    <input type="hidden" value="{{$repayments[$repsNum-1]->od}}" name="reqOD"/>
                    <input type="hidden" value="{{$repayments[$repsNum-1]->money}}" name="reqMoney"/>
                    @else
                    <input type="hidden" value="{{$reqMoneyDet->pc}}" name="reqPc"/>
                    <input type="hidden" value="{{$reqMoneyDet->exp_pc}}" name="reqExpPc"/>
                    <input type="hidden" value="{{$reqMoneyDet->fine}}" name="reqFine"/>
                    <input type="hidden" value="{{$reqMoneyDet->od}}" name="reqOD"/>
                    <input type="hidden" value="{{$reqMoneyDet->money}}" name="reqMoney"/>
                    @endif
                </div>
                <table id="contractsTable" class="table table-borderless table-condensed contracts-table">
                    <tbody>
                        <tr class="contract-header-row">
                            <td colspan="10">Кредитный договор "{{$loan->loantype->name}}" №{{$loan->id_1c}} (дней пользования: {{$loanDays}}) @if($loanDays > $loan->time)( дней просрочки: {{$loanDays-$loan->time}} )@endif @if($loan->closed) <span class="label label-success">Закрыт</span> @endif</td>
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
                            <th>Общая задолженность</th>
                            <th></th>
                        </tr>
                        <tr class="{{$loanTrClass}} contract-row">
                            <td>{{$percents['pc']}}%, пр.{{$percents['exp_pc']}}%, пеня {{$percents['fine_pc']}}%</td>
                            <td class="highlighted contract-date">{{with(new Carbon($loan->created_at))->format('d.m.Y')}}</td>
                            <td class="highlighted contract-end-date">{{$loan->getEndDate()->format('d.m.Y')}}</td>
                            <td class="highlighted contract-money">{{$loan->money}} руб.</td>
                            <td class="highlighted contract-time">{{$loan->time}} д.</td>
                            @if($repsNum==0)
                            <?php $loan_paysavings=$loan->getPaysavingsMoney(); ?>
                            <td class="contract-pc">{{$reqMoneyDet->pc / 100}} руб.@if($loan_paysavings["pc"]>0)(-{{$loan_paysavings['pc']}})@endif</td>
                            <td class="contract-exp-pc">{{$reqMoneyDet->exp_pc / 100}} руб.@if($loan_paysavings['exp_pc']>0)(-{{$loan_paysavings['exp_pc']}})@endif</td>
                            <td class="contract-fine">{{$reqMoneyDet->fine / 100}} руб.@if($loan_paysavings['fine']>0)(-{{$loan_paysavings['fine']}})@endif</td>
                            <td class="highlighted contract-req-money">{{$reqMoneyDet->money / 100}} руб.</td>
                            @else
                            <td class="contract-pc">{{$repayments[0]->was_pc/100}} ({{($repayments[0]->was_pc - $repayments[0]->was_pc*($repayments[0]->discount/100))/100}}) руб.</td>
                            <td class="contract-exp-pc">{{$repayments[0]->was_exp_pc/100}} руб.</td>
                            <td class="contract-fine">{{$repayments[0]->was_fine/100}} руб.</td>
                            <td class="highlighted contract-req-money">{{($repayments[0]->was_od+$repayments[0]->was_pc+$repayments[0]->was_exp_pc+$repayments[0]->was_fine)/100}} руб.</td>
                            @endif
                            <?php $unusedOrders = $loan->getUnusedOrders(); ?>
                            <td>
                                <button onclick="$.summaryCtrl.editLoan({{$loan->id}}); return false;" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-pencil"></span></button>
                                <a href="{{url('contracts/pdf/'.$loan->loantype->contract_form_id.'/'.$loan->claim_id)}}" target="_blank" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-print"></span></a>
                                @if(is_null($loan->claimed_for_remove) && $loan->created_at->setTime(0,0,0)->eq(Carbon::now()->setTime(0,0,0)) && !$loan->in_cash)
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
                            <td colspan="12">
                                <ol class="list-group">
                                    @foreach($loan->orders as $order)
                                    @if(is_null($order->repayment_id))
                                    <li class="list-group-item">
                                        {{$order->orderType->name}} № {{$order->number}} от {{with(new Carbon($order->created_at))->format('d.m.Y')}} 
                                        ({{(!is_null($order->purpose))?Order::getPurposeNames()[$order->purpose]:''}})
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
                        \PC::debug($i, 'i');
                        ?>
                        <tr class="contract-header-row">
                            <td colspan="10">
                                {{$repayments[$i]->repaymentType->name}} №{{$repayments[$i]->id_1c}}
                                @if(isset($repDays))
                                (дней пользования: {{$repDays}})
                                @endif 
                                @if(isset($repDays) && $repDays>$repayments[$i]->time && !$repayments[$i]->repaymentType->isClosing())
                                ( дней просрочки: {{$repDays-$repayments[$i]->time}} )
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
                            <th>Общая задолженность</th>
                            <th></th>
                        </tr>
                        <?php
                        if (!$repayments[$i]->repaymentType->isClosing() && ((with(new Carbon($repayments[$i]->created_at))->setTime(0, 0, 0)->diffInDays(Carbon::now()) > $repayments[$i]->time) || ($i < $repsNum - 1 && with(new Carbon($repayments[$i]->created_at))->setTime(0, 0, 0)->diffInDays(with(new Carbon($repayments[$i + 1]->created_at))->setTime(0, 0, 0)) > $repayments[$i]->time))) {
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
                                @if($i!=$repsNum-1)
                                {{with(new Carbon($repayments[$i + 1]->created_at))->format('d.m.Y')}}
                                @else
                                @if($repayments[$i]->repaymentType->isDopnik())
                                {{$repayments[$i]->getEndDate()->format('d.m.Y')}}
                                @elseif($repayments[$i]->repaymentType->isClaim())
                                {{$repayments[$i]->getEndDate()->subDay()->format('d.m.Y')}}
                                @else
                                {{$repayments[$i]->getEndDate()->addDay()->format('d.m.Y')}}
                                @endif
                                @endif
                            </td>
                            @if($repayments[$i]->repaymentType->isSUZ())
                            <td class="contract-od highlighted">{{$repayments[$i]->od/100}} руб.</td>
                            <td class="contract-time highlighted">{{$repayments[$i]->time}} д.</td>
                            <td class="contract-pc">{{$repayments[$i]->pc/100}} руб.</td>
                            <td class="contract-exp-pc">{{$repayments[$i]->exp_pc/100}} руб.</td>
                            <td class="contract-fine">{{$repayments[$i]->fine/100}} руб.</td>
                            <td class="contract-req-money highlighted">{{($repayments[$i]->pc+$repayments[$i]->od+$repayments[$i]->exp_pc+$repayments[$i]->fine+$repayments[$i]->tax)/100}} руб.</td>
                            @else
                            @if($i==$repsNum-1)
                            <?php $lastrep_paysavings=$repayments[$i]->getPaysavingsMoney(); ?>
                            <td class="contract-od highlighted">{{$reqMoneyDet->od / 100}} руб.</td>
                            <td class="contract-time highlighted">{{$repayments[$i]->time}} д.</td>
                            <td class="contract-pc">{{$reqMoneyDet->pc / 100}} руб. @if($lastrep_paysavings["pc"]>0)(-{{StrUtils::kopToRub($lastrep_paysavings["pc"])}})@endif</td>
                            <td class="contract-exp-pc">{{$reqMoneyDet->exp_pc / 100}} руб. @if($lastrep_paysavings["exp_pc"]>0)(-{{StrUtils::kopToRub($lastrep_paysavings["exp_pc"])}})@endif</td>
                            <td class="contract-fine">{{$reqMoneyDet->fine / 100}} руб.@if($lastrep_paysavings["fine"]>0)(-{{StrUtils::kopToRub($lastrep_paysavings["fine"])}})@endif</td>
                            <td class="contract-req-money highlighted">{{$reqMoneyDet->money / 100}} руб.</td>
                            @else
                            <td class="contract-od highlighted">{{$repayments[$i]->od/100}} руб.</td>
                            <td class="contract-time highlighted">{{$repayments[$i]->time}} д.</td>
                            <td class="contract-pc">{{$repayments[$i]->pc/100}}({{($repayments[$i]->pc - round($repayments[$i]->pc*($repayments[$i]->discount/100)))/100}}) руб.</td>
                            <td class="contract-exp-pc">{{$repayments[$i]->exp_pc/100}} руб.</td>
                            <td class="contract-fine">{{$repayments[$i]->fine/100}} руб.</td>
                            <td class="contract-req-money highlighted">{{$repayments[$i]->req_money/100}} руб.</td>
                            @endif
                            @endif
                            <td class="contract-actions">
                                <button onclick="$.summaryCtrl.editRepayment({{$repayments[$i]->id}}, @if($repayments[$i]->repaymentType->isPeace()) 1 @else 0 @endif); return false;" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-pencil"></span></button>
                                @if($repayments[$i]->repaymentType->isClaim() || $repayments[$i]->repaymentType->isDopnik() || $repayments[$i]->repaymentType->isPeace())
                                <a href="{{url('contracts/pdf/'.(($loan->claim->about_client->postclient || $loan->claim->about_client->pensioner)?$repayments[$i]->repaymentType->perm_contract_form_id:$repayments[$i]->repaymentType->contract_form_id).'/'.$loan->claim_id.'/'.$repayments[$i]->id)}}" target="_blank" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-print"></span></a>
                                @endif
                                @if(is_null($repayments[$i]->claimed_for_remove) && $repayments[$i]->created_at->setTime(0,0,0)->eq(Carbon::now()->setTime(0,0,0)))
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
                                        {{$order->orderType->name}} № {{$order->number}} от {{with(new Carbon($order->created_at))->format('d.m.Y')}} ({{Order::getPurposeNames()[$order->purpose]}})
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
                                            @if(is_null($order->claimed_for_remove) && with(new Carbon($order->created_at))->setTime(0,0,0)->eq(Carbon::now()->setTime(0,0,0)))
                                            <button onclick="$.uReqsCtrl.claimForRemove({{$order->id}},@if($order->order_plus) {{MySoap::ITEM_PKO}} @else {{MySoap::ITEM_RKO}}@endif); return false;" 
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
                                        <?php $peacePays = ($i == $repsNum - 1) ? $reqMoneyDet->peace_pays : $repayments[$i]->peacePays; $curPayMoney = 0; $closestMonth = false;?>
                                        @foreach($peacePays as $pay)
                                        <?php 
                                        if(Carbon::now()->gt(new Carbon($pay->end_date)) && $pay->total > 0){
                                            $curPayMoney += $pay->total;
                                            $payTrClass = 'bg-danger';
                                        } else {
                                            $payTrClass = '';
                                        }
                                        if(Carbon::now()->lt(new Carbon($pay->end_date)) && !$closestMonth){
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
                                                {{$order->orderType->name}} № {{$order->number}} от {{with(new Carbon($order->created_at))->format('d.m.Y')}} ({{Order::getPurposeNames()[$order->purpose]}}) 
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
                                @endif
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                </table>
                <div id="loanRepaymentsTools" style="text-align: center">
                    @if($repsNum==0 || ($repsNum>0 && !$repayments[$repsNum-1]->repaymentType->isClosing()))
                    @if($repsNum>0 && !(($repayments[$repsNum-1]->repaymentType->isClaim() && $repayments[$repsNum-1]->getOverdueDays()>0) ))
                    <button class="btn btn-default add-order-btn" data-toggle="modal" data-target="#addOrderModal" title="Добавляет приходник к последнему договору">
                        <span class="glyphicon glyphicon-plus"></span> Добавить ПКО
                    </button>
                    @endif
                    @foreach($rtypes as $rt)
                    <button class="btn btn-default add-repayment-btn" data-rtype-id="{{$rt->id}}" 
                            data-loan-id="{{$loan->id}}" data-min-money="{{$reqMoneyDet->all_pc}}" data-rtype-def-time="{{$rt->default_time}}"
                            data-rtype-text-id="{{$rt->text_id}}" data-rtype-freeze-days="{{$rt->freeze_days}}">
                        <span class="glyphicon glyphicon-plus"></span> {{$rt->name}}
                    </button>
                    @endforeach
                    @endif
                    @if(Auth::user()->isAdmin() && $repsNum>0)
                    @if(with(new Carbon($repayments[$repsNum-1]->created_at))->setTime(0,0,0)->ne(Carbon::now()->setTime(0,0,0)))
                    <button class="btn btn-default" disabled>
                        <span class="glyphicon glyphicon-remove"></span> Удалить последний
                    </button>
                    @else
                    <a href="{{url('repayments/remove/'.$repayments[$repsNum-1]->id)}}" class="btn btn-default" onclick="$.app.blockScreen(true);">
                        <span class="glyphicon glyphicon-remove"></span> Удалить последний
                    </a>
                    @endif
                    @endif
                </div>
                <br>
                <br>
            </div>
        </div>
    </div>
</div>
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
                    <!--                    <div class="form-group col-xs-12 col-md-4">
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
            <input type="hidden" name="repayment_id" value="{{($repsNum>0)?$repayments[$repsNum-1]->id:''}}"/>
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
                    @if(!is_null($loan->claim->promocode))
                    <span>Скидка на проценты по промокоду с номером {{$loan->claim->promocode->number}}</span>
                    @elseif($percents['pc']>=2 || ($repsNum>0 && $repayments[$repsNum-1]->repaymentType->percent >=2))
                    <div class="form-group col-xs-12 col-md-2">
                        <label>Скидка</label>
                        <select name="discount" class="form-control repayment-discount">
                            <option value="0">0</option>
                            <option value="20">20%</option>
                        </select>
                    </div>
                    @endif
                    <div class="form-group col-xs-12 col-md-12" id="addRepaymentModalInfo">

                    </div>
                    <div class="form-group col-xs-12 col-md-3">
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
@include('elements.editOrderModal')
@include('elements.claimForRemoveModal')
@stop
@section('scripts')
<script src="{{asset('js/usersRequestsController.js')}}"></script>
<script src="{{ URL::asset('js/dashboard/photosController.js') }}"></script>
<script src="{{ URL::asset('js/loan/summaryController.js') }}"></script>
<script src="{{ URL::asset('js/orders/ordersController.js') }}"></script>
<script>
                                (function () {
                                $.ordersCtrl.init();
                                        $.summaryCtrl.init();
                                })(jQuery);
</script>
@stop