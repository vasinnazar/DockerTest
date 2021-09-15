@extends('app')
@section('title') Рабочий стол @stop
@section('css')
<link href="{{asset('/js/libs/bootstrap-fileinput/css/fileinput.min.css')}}" media="all" rel="stylesheet" type="text/css" />
<style>
    .animated {
        -webkit-animation-duration: 3s;
        animation-duration: 3s;
        -webkit-animation-fill-mode: both;
        animation-fill-mode: both;
        animation-iteration-count: infinite;
    }
    @-webkit-keyframes flash {
        0%, 50%, 100% {opacity: 1;}
        25%, 75% {opacity: 0;}
    }
    @keyframes flash {
        0%, 50%, 100% {opacity: 1;}
        25%, 75% {opacity: 0;}
    }
    .flash {
        -webkit-animation-name: flash;
        animation-name: flash;
    }
</style>
@stop
@section('menu')
<!--<li>
    <a class="" href="#searchClaimModal" data-toggle="modal">
        <span class="glyphicon glyphicon-search"></span> Поиск заявки
    </a>
</li>-->
@stop
@section('content')
<button class="btn btn-default" data-toggle="modal" data-target="#searchClaimModal"><span class="glyphicon glyphicon-search"></span> Поиск</button>
<button class="btn btn-default" id="clearClaimsFilterBtn" disabled>Очистить фильтр</button>
<button class="btn btn-default" onclick="$.dashboardCtrl.repeatLastSearch();
        return false;" id="repeatLastClaimSearchBtn">Повторить последний запрос</button>
@if(Auth::user()->isAdmin())
<button type='button' class="btn btn-default" onclick="$.dashboardCtrl.showCardFrame();" id="openKoronaFrameBtn">Открыть Золотую корону</button>
@endif
<h2 style="color: red">
    Телефоны тех. поддержки:
    8-905-906-5854<br>
    <span style='font-size: 20px'>Внимание! Участились случаи звонков от мошенников сегодня! Никому не сообщаем никаких данных по остаткам средств и т.д. по телефону. Все вопросы через руководителя!</span><br>
    <!--8-905-906-0765-->
    <!--<br><span>По сверке РНКО звоните по номеру: 8-905-906-0765!</span>-->
    <!--8-905-906-5854-->
    <!--8-905-906-0851-->
</h2>
<table class="table table-striped table-condensed compact" cellspacing='0' id="myTable" >
    <thead>
        <tr border="1px">
            <th style="width: 30px;">№</th>	
            <th style="width: 40px;">Дата</th>
            <th>Фамилия Имя Отчество</th>
            <th style="width: 20px;"><span class="glyphicon glyphicon-earphone"></span></th>
            <th style="width: 30px;">Сумма</th>
            <th style="width: 30px;">Срок</th>
            <th style="width: 60px;">Редакт.</th>
            <th style="width: 20px;">Фото</th>
            <th style="width: 100px !important;">Отправка документов</th>
            <th style="width: 30px;">Промокод</th>
            <th style="width: 55px;">Решение</th>
            <th style="width: 120px;">Сформ. договор</th>
            <th style="width: 20px;">Печать</th>
            <th style="width: 20px;">Перевести деньги</th>
            @if(Auth::user()->isAdmin())
            <th style="width: 60px;">Удалить</th>
            <th style="width: 120px;">Пользователь/Подразделение</th>
            @else
            <th style="width: 60px;">Заявка на удаление</th>
            @if(Auth::user()->isCC())
            <th style="width: 120px;">Пользователь/Подразделение</th>
            @endif
            @endif
        </tr>
    </thead>
</table>

@include('elements.printDocsModal')
<div class="modal fade" id="createLoanModal" tabindex="-1" role="dialog" aria-labelledby="createLoanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="createLoanModalLabel">Формирование кредитного договора и РКО</h4>
            </div>
            {!! Form::open(['route'=>'loans.create']) !!}
            {!! Form::hidden('claim_id') !!}
            {!! Form::hidden('customer_id') !!}
            <div class="modal-body">
                <div id="createLoanFormHolder">
                    <div class="row">
                        <div class="col-xs-12">
                            <label>Вид займа</label>
                            {!! Form::select('loantype_id',[],null,['class'=>'form-control']) !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 text-danger">
                            Максимальная одобренная сумма займа: <span id="maxMoneyHolder" style="font-size:16px; font-weight: bold"></span> р.
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            Одобренный процент по договору: <span id="claimSpecialPercent" style="font-size:16px; font-weight: bold"></span> %
                        </div>
                    </div>
                    <div class="row hidden" id='terminalPromocodeWrapper'>
                        <div class="col-xs-12 text-danger">
                            Промокод добавленный с терминала: -<span id="terminalPromocodeHolder" style="font-size:16px; font-weight: bold"></span>р.
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <label>Сумма</label>
                            <input name="money" type="number" class="form-control" step="1000" min="1000"/>
                        </div>
                        <div class="col-xs-6">
                            <label>Срок <span class="loan-end-date-holder"></span></label>
                            <input name="time" type="number" class="form-control" step="1" min="1"/>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-xs-6">
                            <div class="form-group">
                                {!! Form::checkbox('promocode',1,null,['class'=>'checkbox','id'=>'promocodeCheckbox']) !!}
                                <label class="control-label" for="promocodeCheckbox">Получить промокод</label>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-xs-6">
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-default active" for="createLoanModalInCash">
                                    Наличными
                                    {!! Form::radio('in_cash','1',true,['id'=>'createLoanModalInCash']) !!}
                                </label>
                                <label class="btn btn-default" for="createLoanModalOnCard">
                                    На карту
                                    {!! Form::radio('in_cash','0',false,['id'=>'createLoanModalOnCard']) !!}
                                </label>
                            </div>
                        </div>
                        <div class="col-xs-6 hidden" id="cardInputHolder">
                            {!! Form::hidden('card_id') !!}
                            <label>Номер карты</label>
                            <div class="input-group">
                                {!! Form::text('card_number',null,[
                                'placeholder'=>'2700XXXXXXXXX',
                                'pattern'=>'\d{13}', 'class'=>'form-control',
                                'minlength'=>'13','maxlength'=>'13'
                                ])!!}
                                <div class="input-group-btn">
                                    <button id="createNewCardBtn" class="btn btn-default"><span class="glyphicon glyphicon-plus"></span></button>
                                </div>
                            </div>
                            <label>Секретное слово</label>
                            {!! Form::text('secret_word',null,['class'=>'form-control']) !!}
                        </div>
                    </div>
                </div>
                <div id="cardApproveHolder" class="hidden">
                    <div class="btn-group" data-toggle="buttons">
                        <label class="btn btn-default" for="createLoanModalSMS">
                            Да
                            {!! Form::radio('gotSMS','1',false,['id'=>'createLoanModalSMS']) !!}
                        </label>
                        <label class="btn btn-default active" for="createLoanModalNoSMS">
                            Нет
                            {!! Form::radio('gotSMS','0',true,['id'=>'createLoanModalNoSMS','checked'=>'checked']) !!}
                        </label>
                    </div>
                    <label for="gotSMS" >Клиенту пришло SMS сообщение</label>
                </div>
            </div>
            <div class="modal-footer">
                {!! Form::submit('Далее',['class'=>'btn btn-primary']) !!}
                <!--<button type="button" class="btn btn-primary">Далее</button>-->
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
<div class="modal fade" id="searchClaimModal" tabindex="-1" role="dialog" aria-labelledby="searchClaimModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="searchClaimModalLabel">Поиск заявки</h4>
            </div>
            @include('elements.searchClaimModalBody')
            <div class="modal-footer">
                {!!Form::button('<span class="glyphicon glyphicon-search"></span> Поиск',
                ['class'=>'btn btn-primary', 'onclick'=>'$.dashboardCtrl.searchClaims(); return false;'])!!}
            </div>
        </div>
    </div>
</div>
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="promocodeModal" aria-hidden="true" id="promocodeModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel">Промокод</h4>
            </div>
            <div class="modal-body">

            </div>
        </div>
    </div>
</div>
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="addPromocodeModal" aria-hidden="true" id="addPromocodeModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel">Добавить промокод</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="control-label">Номер промокода</label>
                    <input type="hidden" name="claim_id_1c"/>
                    <input type="hidden" name="claim_id"/>
                    <input class="form-control" name="promocode_number"/>
                </div>
                <div id="addPromocodeModalResult" class="alert fade in"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="addPromocodeBtn">Добавить</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
@include('elements.claimForRemoveModal')
@include('elements.addPhotosModal')
@include('elements.preSendClaimModal')
@include('elements.commentModal')
@include('elements.showTelephoneModal')
@stop

@section('scripts')
<script src="{{asset('js/libs/webcamjs/webcam.js')}}"></script>
<script src="{{asset('js/libs/bootstrap-fileinput/js/plugins/canvas-to-blob.min.js')}}" type="text/javascript"></script>
<script src="{{asset('js/libs/bootstrap-fileinput/js/fileinput.min.js')}}"></script>
<script src="{{asset('js/libs/bootstrap-fileinput/js/fileinput_locale_ru.js')}}"></script>
<script src="{{asset('js/libs/viewer/viewer.min.js')}}" type="text/javascript"></script>
<script src="{{ URL::asset('js/dashboard/photosController.js?8') }}"></script>
<script src="{{ URL::asset('js/usersRequestsController.js') }}"></script>
<script src="{{ URL::asset('js/dashboard/dashboardController.js?10') }}"></script>
<script>
    (function () {
        $.dashboardCtrl.init();
    })(jQuery);
</script>
@stop