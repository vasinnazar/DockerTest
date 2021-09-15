@extends('app')
@section('title') Обзор @stop
@section('content')
<?php

use App\OrderType;
use App\Order;
use Carbon\Carbon;
use App\Utils\StrLib;
use App\Utils\HtmlHelper;
use App\Http\Controllers\HomeController;
use App\Claim;

//use Storage;
?>
<div class="row">
    <div class="col-xs-12">
        <div class="row">
            <h1 class="col-xs-12">
                Заявка от {{with(new Carbon($claim->created_at))->format('d.m.Y')}} на {{$claim->passport->fio}} <?php echo HtmlHelper::StatusLabel($claim->status) ?>
                @if($claim->about_client->postclient)
                <small><label class="label label-success">Постоянный клиент</label></small>
                @endif
            </h1>
            <div class="col-xs-12 col-md-2 photo-gallery">
                @if(!is_null($photos))
                <div class="main-photo">
                    <img />
                </div>
                <div class="previews">
                    @foreach($photos as $p)
                    <img data-main="{{$p->is_main}}" src="data:image/'{{pathinfo(url($p->path), PATHINFO_EXTENSION)}};base64,{{base64_encode(Storage::get($p->path))}}" 
                         alt="{{$p->id}}" class="photo-preview" onclick="$.photosCtrl.makeMain(this);
                                 return false;" />
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
                        <p>Серия: {{$claim->passport->series}} Номер: {{$claim->passport->number}}</p>
                        <p>Место рождения: {{$claim->passport->birth_city}}</p>
                        <p>Кем выдан: {{$claim->passport->issued}}</p>
                        <p>Код подразделения: {{$claim->passport->subdivision_code}}</p>
                        <p>Дата выдачи: {{with(new Carbon($claim->passport->issued_date))->format('d.m.Y')}}</p>
                    </div>
                    <div class="tab-pane" id="phonesSummary">
                        <p>Телефон: {{$claim->customer->telephone}}</p>
                        @if(!is_null($claim->about_client))
                        <p>
                            Домашний телефон:
                            {{$claim->about_client->telephonehome}}
                        </p>
                        <p>
                            Телефон родственников:
                            {{$claim->about_client->telephonerodstv}}
                        </p>
                        <p>
                            Другой телефон:
                            {{$claim->about_client->anothertelephone}}
                        </p>
                        <p>
                            Степень родства:
                            @if(!is_null($claim->relationDegree))
                            {{$claim->about_client->relationDegree->name}}
                            @endif
                        </p>
                        @endif
                    </div>
                    <div class="tab-pane" id="workSummary">
                        @if(!is_null($claim->about_client))
                        <p>
                            Должность: {{$claim->about_client->dolznost}}
                        </p>
                        <p>
                            Вид труда: 
                            @if($claim->about_client->vidtruda)
                            Официально
                            @else
                            Не официально
                            @endif
                        </p>
                        <p>
                            Организация: {{$claim->about_client->organizacia}}
                        </p>
                        <p>
                            ИНН организации: {{$claim->about_client->innorganizacia}}
                        </p>
                        <p>
                            ФИО руководителя: {{$claim->about_client->fiorukovoditel}}
                        </p>
                        <p>
                            Адрес организации: {{$claim->about_client->adresorganiz}}
                        </p>
                        <p>
                            Телефон организации: {{$claim->about_client->telephoneorganiz}}
                        </p>
                        <p>
                            Доход: {{$claim->about_client->dohod}}
                        </p>
                        <p>
                            Доп. доход: {{$claim->about_client->dopdohod}}
                        </p>
                        <p>
                            Стаж: {{$claim->about_client->stazlet}}
                        </p>
                        @endif
                    </div>
                    <div class="tab-pane" id="personalSummary">
                        @if(!is_null($claim->about_client))
                        <p>
                            Пол: 
                            @if($claim->about_client->sex)
                            Мужской
                            @else
                            Женский
                            @endif
                        </p>
                        @endif
                        <p>Дата рождения: {{with(new Carbon($claim->passport->birth_date))->format('d.m.Y')}}</p>
                        <p>
                            Юридический адрес:
                            {{$claim->passport->address_region}}, 
                            @if($claim->passport->address_district!="")
                            {{$claim->passport->address_district}}, 
                            @endif
                            {{$claim->passport->address_city}}, 
                            {{$claim->passport->address_street}}, 
                            {{$claim->passport->address_house}} 
                            @if($claim->passport->address_building!="")
                            , {{$claim->passport->address_building}}
                            @endif
                            @if($claim->passport->address_apartment!="")
                            , {{$claim->passport->address_apartment}}
                            @endif
                        </p>
                        <p>
                            Фактический адрес:
                            {{$claim->passport->fact_address_region}}, 
                            @if($claim->passport->fact_address_district!="")
                            {{$claim->passport->fact_address_district}}, 
                            @endif
                            {{$claim->passport->fact_address_city}}, 
                            {{$claim->passport->fact_address_street}}, 
                            {{$claim->passport->fact_address_house}} 
                            @if($claim->passport->fact_address_building!="")
                            , {{$claim->passport->fact_address_building}}
                            @endif
                            @if($claim->passport->fact_address_apartment!="")
                            , {{$claim->passport->fact_address_apartment}}
                            @endif
                        </p>
                        @if(!is_null($claim->about_client))
                        <p>
                            Автомобиль: 
                            @if($claim->about_client->avto==0)
                            Нет
                            @elseif($claim->about_client->avto==1)
                            Отечественный
                            @elseif($claim->about_client->avto==2)
                            Иномарка
                            @endif
                        </p>
                        <p>
                            Жилищные условия:
                            @if(!is_null($claim->about_client->liveCondition))
                            {{$claim->about_client->liveCondition->name}}
                            @endif
                        </p>
                        <p>
                            Дети:
                            {{$claim->about_client->deti}}
                        </p>
                        <p>
                            ФИО супруги:
                            {{$claim->about_client->fiosuprugi}}
                        </p>
                        <p>
                            Предыдущее ФИО:
                            {{$claim->about_client->fioizmena}}
                        </p>
                        <p>
                            Образование:
                            @if(!is_null($claim->about_client->educationLevel))
                            {{$claim->about_client->educationLevel->name}}
                            @endif
                        </p>
                        <p>
                            Семейное положение:
                            @if(!is_null($claim->about_client->maritalType))
                            {{$claim->about_client->maritalType->name}}
                            @endif
                        </p>
                        <p>
                            @if($claim->about_client->pensioner)
                            <label class="label label-default">Пенсионер</label>
                            @endif
                            @if($claim->about_client->armia)
                            <label class="label label-default">Служил в армии</label>
                            @endif
                            @if($claim->about_client->poruchitelstvo)
                            <label class="label label-default">Является поручителем</label>
                            @endif
                            @if($claim->about_client->zarplatcard)
                            <label class="label label-default">Зарплатная карта</label>
                            @endif
                            @if($claim->about_client->postclient)
                            <label class="label label-default">Постоянный клиент</label>
                            @endif
                        </p>
                        <p>
                            Кредит:
                            {{$claim->about_client->credit}}
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
                @if(!is_null($claim->promocode))
                <h1><small>Промокод, добавленный к заявке:</small> {{$claim->promocode->number}}</h1>
                @endif
            </div>
            <div class="col-xs-12 col-md-12">
                <a href="{{url('claims/edit/'.$claim->id)}}" class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span> Редактировать заявку</a>
                <a href="{{url('photos/add/'.$claim->id)}}" class="btn btn-default"><span class="glyphicon glyphicon-picture"></span> Добавить фотографии</a>
                <?php
                echo HtmlHelper::Buttton(null, ['glyph' => 'print', 'onclick' => '$.dashboardCtrl.showPrintDocsModal(' . $claim->id . '); return false;', 'text' => ' Печать']).' ';
                if ((is_null($loan) || !$loan->enrolled) && (in_array($claim->status, HomeController::EDITABLE_STATUSES) || Auth::user()->isAdmin())) {
                    echo HtmlHelper::Buttton(url('claims/status/' . $claim->id . '/1'), [
                                'onclick' => '$.app.blockScreen(true);', 'text' => 'Отправить',
                                'class' => (($claim->status == Claim::STATUS_ONEDIT) ? 'btn btn-primary' : 'btn btn-default').' '
                    ]);
                } else {
                    echo HtmlHelper::Buttton(null, ['text' => 'Отправлено','disabled'=>true]).' ';
                }
                if(!is_null($loan) && $loan->enrolled){
                    echo HtmlHelper::Buttton(url('loans/summary/'.$loan->id), ['text'=>'Перейти к кредитному договору']);
                }
                ?>
            </div>
        </div>
    </div>
</div>
@include('elements.printDocsModal')
@stop
@section('scripts')
<script src="{{ URL::asset('js/dashboard/photosController.js') }}"></script>
<script src="{{ URL::asset('js/dashboard/dashboardController.js') }}"></script>
@stop