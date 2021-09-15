<?php

use App\OrderType;
use App\Order;
use Carbon\Carbon;
use App\MySoap;
?>
<div class="row">
    <h1 class="col-xs-12">
        {{$passport->fio}}
        @if($about_client->postclient)
        <small><label class="label label-success">Постоянный клиент</label></small>
        @endif
        @if(Auth::user()->isAdmin())
        № контрагента: {{$customer->id_1c}} 
        Заявка №{{$claim->id_1c}}
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
        <ul class="nav nav-tabs">
            <li class="active"><a href="#passportSummary" data-toggle="tab">Паспортные данные</a></li>
            <li><a href="#phonesSummary" data-toggle="tab">Телефоны</a></li>
            <li><a href="#workSummary" data-toggle="tab">Данные о работе</a></li>
            <li><a href="#personalSummary" data-toggle="tab">Личные данные</a></li>
        </ul>
        <div class="tab-content" id="summaryTabContent">
            <div class="tab-pane active" id="passportSummary">
                <p>Серия: {{$passport->series}} Номер: {{$passport->number}}</p>
                <p>Место рождения: {{$passport->birth_city}}</p>
                <p>Кем выдан: {{$passport->issued}}</p>
                <p>Код подразделения: {{$passport->subdivision_code}}</p>
                <p>Дата выдачи: {{with(new Carbon($passport->issued_date))->format('d.m.Y')}}</p>
            </div>
            <div class="tab-pane" id="phonesSummary">
                <p>Телефон: {{$customer->telephone}}</p>
                @if(!is_null($about_client))
                <p>
                    Домашний телефон:
                    {{$about_client->telephonehome}}
                </p>
                <p>
                    Телефон родственников:
                    {{$about_client->telephonerodstv}}
                </p>
                <p>
                    Другой телефон:
                    {{$about_client->anothertelephone}}
                </p>
                @endif
            </div>
            <div class="tab-pane" id="workSummary">
                @if(!is_null($about_client))
                <p>
                    Должность: {{$about_client->dolznost}}
                </p>
                <p>
                    Вид труда: 
                    @if($about_client->vidtruda)
                    Официально
                    @else
                    Не официально
                    @endif
                </p>
                <p>
                    Организация: {{$about_client->organizacia}}
                </p>
                <p>
                    ИНН организации: {{$about_client->innorganizacia}}
                </p>
                <p>
                    ФИО руководителя: {{$about_client->fiorukovoditel}}
                </p>
                <p>
                    Адрес организации: {{$about_client->adresorganiz}}
                </p>
                <p>
                    Телефон организации: {{$about_client->telephoneorganiz}}
                </p>
                <p>
                    Доход: {{$about_client->dohod}}
                </p>
                <p>
                    Доп. доход: {{$about_client->dopdohod}}
                </p>
                <p>
                    Стаж: {{$about_client->stazlet}}
                </p>
                @endif
            </div>
            <div class="tab-pane" id="personalSummary">
                @if(!is_null($about_client))
                <p>
                    Пол: 
                    @if($about_client->sex)
                    Мужской
                    @else
                    Женский
                    @endif
                </p>
                @endif
                <p>Дата рождения: {{with(new Carbon($passport->birth_date))->format('d.m.Y')}}</p>
                <p>
                    Юридический адрес:
                    {{$passport->address_region}}, 
                    @if($passport->address_district!="")
                    {{$passport->address_district}}, 
                    @endif
                    {{$passport->address_city}}, 
                    {{$passport->address_street}}, 
                    {{$passport->address_house}} 
                    @if($passport->address_building!="")
                    , {{$passport->address_building}}
                    @endif
                    @if($passport->address_apartment!="")
                    , {{$passport->address_apartment}}
                    @endif
                </p>
                <p>
                    Фактический адрес:
                    {{$passport->fact_address_region}}, 
                    @if($passport->fact_address_district!="")
                    {{$passport->fact_address_district}}, 
                    @endif
                    {{$passport->fact_address_city}}, 
                    {{$passport->fact_address_street}}, 
                    {{$passport->fact_address_house}} 
                    @if($passport->fact_address_building!="")
                    , {{$passport->fact_address_building}}
                    @endif
                    @if($passport->fact_address_apartment!="")
                    , {{$passport->fact_address_apartment}}
                    @endif
                </p>
                @if(!is_null($about_client))
                <p>
                    Автомобиль: 
                    @if($about_client->avto==0)
                    Нет
                    @elseif($about_client->avto==1)
                    Отечественный
                    @elseif($about_client->avto==2)
                    Иномарка
                    @endif
                </p>
                <p>
                    Жилищные условия:
                    @if(!is_null($liveCondition))
                    {{$liveCondition->name}}
                    @endif
                </p>
                <p>
                    Дети:
                    {{$about_client->deti}}
                </p>
                <p>
                    ФИО супруги:
                    {{$about_client->fiosuprugi}}
                </p>
                <p>
                    Предыдущее ФИО:
                    {{$about_client->fioizmena}}
                </p>
                <p>
                    Образование:
                    @if(!is_null($educationLevel))
                    {{$educationLevel->name}}
                    @endif
                </p>
                <p>
                    Семейное положение:
                    @if(!is_null($maritalType))
                    {{$maritalType->name}}
                    @endif
                </p>
                <p>
                    @if($about_client->pensioner)
                    <label class="label label-default">Пенсионер</label>
                    @endif
                    @if($about_client->armia)
                    <label class="label label-default">Служил в армии</label>
                    @endif
                    @if($about_client->poruchitelstvo)
                    <label class="label label-default">Является поручителем</label>
                    @endif
                    @if($about_client->zarplatcard)
                    <label class="label label-default">Зарплатная карта</label>
                    @endif
                    @if($about_client->postclient)
                    <label class="label label-default">Постоянный клиент</label>
                    @endif
                </p>
                <p>
                    Кредит:
                    {{$about_client->credit}}
                </p>
                @endif
            </div>
        </div>
    </div>
</div>