<?php

use App\OrderType;
use App\Order;
use Carbon\Carbon;
use App\MySoap;
?>
<div class="row">
    <h1 class="col-xs-12">
        {{$loan->claim->passport->fio}}
        @if($loan->claim->about_client->postclient)
        <small><label class="label label-success">Постоянный клиент</label></small>
        @endif
        @if(Auth::user()->isAdmin())
        № контрагента: {{$loan->claim->customer->id_1c}}
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
            <img data-main="{{$p->is_main}}" src="{{$p->src}}" alt="{{$p->id}}" class="photo-preview" onclick="$.photosCtrl.makeMain(this); return false;" />
            <!--<img data-main="{{$p->is_main}}" class="photo-preview" src="{{url($p->path)}}" alt="{{$p->id}}" />-->
            @endforeach
        </div>
        @endif
    </div>
    <div class="col-xs-12 col-md-10">
        <div>
            <?php $activeCard = $loan->claim->customer->getActiveCard(); ?>
            @if(!is_null($activeCard))
            <p>Номер карты: {{$activeCard->card_number}}</p>
            @endif
        </div>
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
                            <td>@if(!$item->closed) Открыт @else Закрыт @endif</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>