@extends('help.helpmenu')
@section('title')Документы@stop
@section('subcontent')
<style>
    .help-steps li{
        margin: 10px 0;
    }
    .help-steps img{
        max-height: 300px;
        max-width: 300px;
    }
    .docs-links{
        list-style: none;
        font-size: 18px;
    }
</style>
<h1>Документы</h1>
<h2>Правила</h2>
<ul class="docs-links">
    <li><a href="{{ asset('/files/pravila.pdf') }}" target="_blank">Правила предоставления потребительских займов</a></li>
    <li><a href="{{ asset('/files/pravila_sfp.pdf') }}" target="_blank">Правила предоставления и обслуживания потребительских займов в рамках микрофинансовой линии VISA и MASTERCARD Worldwide «Срочная финансовая помощь»</a></li>
</ul>
<ul class="docs-links">
    <li>
        <a href="http://rnko.ru/cardholders/microfin/prostodengi/Documents/%d0%94%d0%be%d0%b3%d0%be%d0%b2%d0%be%d1%80%20%d0%be%20%d0%ba%d0%be%d0%bc%d0%bf%d0%bb%d0%b5%d0%ba%d1%81%d0%bd%d0%be%d0%bc%20%d0%be%d0%b1%d1%81%d0%bb%d1%83%d0%b6%d0%b8%d0%b2%d0%b0%d0%bd%d0%b8%d0%b8_%d0%9f%d1%80%d0%be%d1%81%d1%82%d0%be%d0%94%d0%95%d0%9d%d0%ac%d0%93%d0%98_14_03_2016.pdf" target="_blank">
            Договор о комплексном обслуживании Клиента (ПростоДЕНЬГИ) 14.03.2016
        </a>
    </li>
    <li>
        <a href="http://rnko.ru/cardholders/microfin/prostodengi/Documents/%d0%a2%d0%b0%d1%80%d0%b8%d1%84%d0%bd%d1%8b%d0%b9%20%d0%bf%d0%bb%d0%b0%d0%bd%20%e2%84%96%20%d0%9c%d0%a4%d0%9e-4_14_03_2016.pdf" target="_blank">
            Тарифный план № МФО-4 14.03.2016
        </a>
    </li>
</ul>
<h2>Заявления СФП</h2>
<ul class="docs-links">
    <li><a href="{{ asset('/files/sfp/sfp1.pdf') }}" target="_blank">Анкета для формирования заявки на оспаривание операции</a></li>
    <li><a href="{{ asset('/files/sfp/sfp2.pdf') }}" target="_blank">возврат перевода, соверш. через интернет, мобильную связь</a></li>
    <li><a href="{{ asset('/files/sfp/sfp3.pdf') }}" target="_blank">о переводе остатка электронных денежных средств</a></li>
    <li><a href="{{ asset('/files/sfp/sfp4.pdf') }}" target="_blank">о предоставлении информации об операциях</a></li>
    <li><a href="{{ asset('/files/sfp/sfp5.pdf') }}" target="_blank">о предоставлении подтверждения владельца персонифицированного электронного средства платежа</a></li>
    <li><a href="{{ asset('/files/sfp/sfp6.pdf') }}" target="_blank">о предоставлении подтверждения об остатке денежных средств</a></li>
    <li><a href="{{ asset('/files/sfp/sfp7.pdf') }}" target="_blank">о предоставлении подтверждения совершения операции</a></li>
    <li><a href="{{ asset('/files/sfp/sfp8.pdf') }}" target="_blank">о разблокировке</a></li>
    <li><a href="{{ asset('/files/sfp/sfp10.pdf') }}" target="_blank">об изменении реквизитов перевода, совершенного с использованием интернета или мобильных платежей</a></li>
    <li><a href="{{ asset('/files/sfp/sfp11.pdf') }}" target="_blank">по операции, учтенной в Электронном кошельке</a></li>
</ul>
@stop