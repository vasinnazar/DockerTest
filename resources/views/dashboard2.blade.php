@extends('app')
@section('title') Рабочий стол @stop
@section('css')
<link href="{{asset('/js/libs/bootstrap-fileinput/css/fileinput.min.css')}}" media="all" rel="stylesheet" type="text/css" />
<link href="{{asset('/js/libs/viewer/viewer.min.css')}}" media="all" rel="stylesheet" type="text/css" />
@stop
@section('menu')
@stop
@section('content')
<?php

use Carbon\Carbon;
use App\Utils\HtmlHelper;
use App\Http\Controllers\HomeController;
use App\Claim;
?>
<a class="btn btn-default" href="#searchClaimModal" data-toggle="modal" data-target="#searchClaimModal"><span class="glyphicon glyphicon-search"></span> Поиск</a>
@if(strstr(Request::path(),'?')!==false)
<a href='{{url('home')}}' class="btn btn-default" id="clearClaimsFilterBtn">Очистить фильтр</a>
@else
<button class="btn btn-default" id="clearClaimsFilterBtn" disabled>Очистить фильтр</button>
@endif
<table class="table table-striped table-condensed compact" cellspacing='0' id="myTable" >
    <thead>
        <tr border="1px">
            <th>№</th>	
            <th>Дата</th>
            <th>Фамилия Имя Отчество</th>
            <th>Телефон</th>
            <th>Сумма</th>
            <th>Срок</th>
            <th style="width: 60px;">Редакт.</th>
            <th style="width: 20px;">Фото</th>
            <th style="width: 30px;">Отправка документов</th>
            <th style="width: 30px;">Промокод</th>
            <th style="width: 80px;">Решение</th>
            <th style="width: 140px;">Сформ. договор</th>
            <th>Печать</th>
            <th style="width: 20px;">Перевести деньги</th>
            @if(Auth::user()->isAdmin())
            <th style="width: 60px;">Удалить</th>
            <th style="width: 120px;">Пользователь/Подразделение</th>
            @else
            <th style="width: 60px;">Заявка на удаление</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach($claims as $claim)
        <tr>
            <td>{{$claim->claim_id}}</td>
            <td>{{with(new Carbon($claim->created_at))->format('d.m.Y')}}</td>
            <td>{{$claim->fio}}</td>
            <td>
                @if(!is_null($claim->telephone) && strlen($claim->telephone)>=7)
                +{{substr($claim->telephone, 0, 4)}}***{{substr($claim->telephone, 7)}}
                @else
                -
                @endif
            </td>
            <td>{{$claim->summa}}</td>
            <td>{{$claim->srok}}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    @if(in_array($claim->claimstatus, HomeController::EDITABLE_STATUSES) || Auth::user()->isAdmin())
                    <a href="{{url('claims/edit/' . $claim->claim_id)}}" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-pencil"></span></a>
                    @else
                    <button disabled class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span></button>
                    @endif
                </div>
            </td>
            <td>
                @if(in_array($claim->claimstatus, HomeController::EDITABLE_STATUSES) || Auth::user()->isAdmin())
                <!--<a href="{{url('photos/add/' . $claim->claim_id)}}" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-picture"></span></a>-->
                <button class="btn btn-default" onclick="$.photosCtrl.openAddPhotoModal({{$claim->claim_id}},{{$claim->customer}}); return false;"><span class="glyphicon glyphicon-picture"></span></button>
                @else
                <button disabled class="btn btn-default"><span class="glyphicon glyphicon-picture"></span></button>
                @endif
            </td>
            <td>
                @if(in_array($claim->claimstatus, HomeController::EDITABLE_STATUSES) || Auth::user()->isAdmin())
                <a href="{{url('claims/status/' . $claim->claim_id.'/1')}}" class="btn btn-sm  @if($claim->claimstatus == Claim::STATUS_ONEDIT) btn-primary @else btn-default @endif" onclick="$.app.blockScreen(true);">
                    Отправить
                </a>
                @else
                <span class="label label-success">Отправлено</span>
                @endif
            </td>
            <td>
                @if(!is_null($claim->claim_id_1c) && $claim->enrolled == 0)
                @if(is_null($claim->promocode_id))
                <button onclick="$.dashboardCtrl.openAddPromocodeModal('{{$claim->claim_id_1c}}','{{$claim->claim_id}}');" class="btn btn-default btn-sm">
                    <span class="glyphicon glyphicon-plus"></span>
                </button>
                @else
                <button disabled class="btn btn-default btn-sm"><span class="glyphicon glyphicon-plus"></span></button>
                @endif
                @else
                <button disabled class="btn btn-default btn-sm"><span class="glyphicon glyphicon-plus"></span></button>
                @endif
                @if(Auth::user()->isAdmin())
                {!!HtmlHelper::OpenDropDown('')!!}
                {!!HtmlHelper::DropDownItem("Добавить промокод, вручную только в арм", ['onclick' => '$.dashboardCtrl.openManualAddPromocodeModal(' . $claim->claim_id . '); return false;', "href" => '#'])!!}
                {!!HtmlHelper::CloseDropDown()!!}
                @endif
            </td>
            <td>
                <!--СТАТУС==================================================================================================================-->
                <?php echo HtmlHelper::StatusLabel($claim->claimstatus) ?>
                @if(!is_null($claim->seb_phone))
                <br><small title="Телефон специалиста СЭБ"><span class="glyphicon glyphicon-earphone"></span>{{$claim->seb_phone}}</small>
                @endif
            </td>
            <td>
                <!--КНОПКА ФОРМИРОВАНИЯ КРЕДИТНИКА==================================================================================================================-->
                @if(in_array($claim->claimstatus, [Claim::STATUS_ACCEPTED, Claim::STATUS_CREDITSTORY]) && is_null($claim->loan))
                <button class="btn btn-default btn-sm" onclick="$.dashboardCtrl.beforeLoanCreate({{$claim->claim_id}},{{$claim->customer}});">Сформировать</button>
                @elseif(!is_null($claim->loan))
                <div class="btn-group btn-group-sm">
                    <button disabled class="btn btn-default">Сформирован</button>
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                        <span class="caret"></span>
                        <span class="sr-only">Меню с переключением</span>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        @if(Auth::user()->isAdmin())
                        @if($claim->enrolled)
                        <li><a href="' . url('loans/clearenroll/' . $claim->loan) . '">Снять пометку о зачислении</a></li>
                        @else
                        <li><a href="' . url('loans/edit/' . $claim->loan) . '">Редактировать</a></li>
                        @endif
                        @endif
                        <li><a href="#" onclick="$.dashboardCtrl.showPromocode(' . $claim->loan . ');
                                                    return false;">Посмотреть промокод</a></li>
                    </ul>
                </div>
                @else
                <button disabled class="btn btn-default btn-sm">Сформировать</button>
                @endif
            </td>
            <td>
                <button class="btn btn-default btn-sm" onclick="$.dashboardCtrl.showPrintDocsModal({{$claim->claim_id}}); return false;"><span class="glyphicon glyphicon-print"></span></button>
            </td>
            <td>
                <!--КНОПКА ЗАЧИСЛЕНИЯ==================================================================================================================-->
                @if(is_null($claim->loan))
                <button disabled class="btn btn-default btn-sm"><span class="glyphicon glyphicon-export"></span></button>
                @else
                @if($claim->enrolled)
                @if($claim->is_terminal)
                @if($claim->on_balance)
                <span class="label label-success">Зачислено на баланс</span>
                @else
                @if(Claim::hasSignedContract($claim->claim_id))
                <a onclick="$.app.blockScreen(true);" class="btn btn-default btn-sm" href="{{url('loans/sendtobalance/' . $claim->loan)}}" title="Перевести сумму на баланс клиента">
                    <span class="glyphicon glyphicon-export"></span> На баланс
                </a>
                @else
                <?php echo HtmlHelper::Label('Нет договора', ['class' => 'label-danger']) ?>
                @endif
                @endif
                @else
                <?php echo HtmlHelper::Label((($claim->in_cash) ? 'РКО сформирован' : 'Зачислено'), ['class' => 'label-success']) ?>
                @endif
                @else
                <?php echo HtmlHelper::Buttton(url('loans/enroll/' . $claim->loan), ['onclick' => '$.app.blockScreen(true);', 'size' => 'sm', 'glyph' => 'export']) ?>
                @endif
                @endif
            </td>
            <td>
                <!--КНОПКА УДАЛЕНИЯ ИЛИ ПОМЕТКИ НА УДАЛЕНИЕ==================================================================================================================-->
                @if(Auth::user()->isAdmin())
                @if(!$claim->enrolled)
                <?php
                $ddItems = [
                    HtmlHelper::DropDownItem('Заявку', ['href' => url('claims/mark4remove/' . $claim->claim_id)])
                ];
                if (!is_null($claim->loan)) {
                    $ddItems[] = HtmlHelper::DropDownItem('Займ', ['href' => url('loans/remove/' . $claim->loan)]);
                }
                echo HtmlHelper::DropDown('Удалить', $ddItems);
                ?>
                @else
                {!! HtmlHelper::DropDown('Удалить', [
                HtmlHelper::DropDownItem('Заявку (только с сайта)', ['href' => url('claims/mark4remove/' . $claim->claim_id), 'title' => 'Только если уже удалено в 1С!']),
                HtmlHelper::DropDownItem('Займ (только с сайта)', ['href' => url('loans/remove2/' . $claim->loan), 'title' => 'Только если уже удалено в 1С!'])
                ])!!}
                @endif
                @else
                <div class="btn-group btn-group-sm remove-dropdown">
                    <button type="button" class="btn @if(!is_null($claim->claim_claimed_for_remove) || !is_null($claim->loan_claimed_for_remove)) btn-danger @else btn-default @endif dropdown-toggle" 
                            data-toggle="dropdown"><span class="glyphicon glyphicon-exclamation-sign"></span> <span class="caret"></span></button>
                    <ul class="dropdown-menu dropdown-menu-right" role="menu">
                        @if(is_null($claim->claim_claimed_for_remove))
                        <li><a href="#" title="Подать заявку" onclick="$.uReqsCtrl.claimForRemove({{$claim->claim_id}},{{\App\MySoap::ITEM_CLAIM}}); return false;">Заявка</a></li>
                        @else
                        <li><a class="bg-danger" title="Заявка подана"><span class="glyphicon glyphicon-exclamation-sign"></span> Заявка</a></li>
                        @endif
                        @if(!is_null($claim->loan))
                        @if(is_null($claim->loan_claimed_for_remove))
                        <li><a href="#" title="Подать заявку" onclick="$.uReqsCtrl.claimForRemove({{$claim->loan}},{{\App\MySoap::ITEM_LOAN}}); return false;">Кредитный договор</a></li>
                        @else
                        <li><a class="bg-danger" title="Заявка подана"><span class="glyphicon glyphicon-exclamation-sign"></span> Кредитный договор</a></li>
                        @endif
                        @endif
                        @if(with(new Carbon($claim->order_created_at))->setTime(0,0,0)->eq(Carbon::now()->setTime(0,0,0)))
                        <li><a href="#" title="Подать расходник" onclick="$.uReqsCtrl.claimForRemove({{$claim->order_id}},{{\App\MySoap::ITEM_RKO}}); return false;">РКО</a></li>
                        @endif
                    </ul>
                </div>
                @endif
            </td>
            @if(Auth::user()->isAdmin())
            <td>
                <small style="line-height:1; display:inline-block">{{$claim->user_name}}<br><span style="font-size:xx-small">{{$claim->subdiv_name}}</span></small>
            </td>
            @endif
        </tr>
        @endforeach
    </tbody>
</table>
{!! $claims->render() !!}

@include('elements.printDocsModal')
@include('elements.createLoanModal')
@include('elements.searchClaim2Modal')
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
@stop

@section('scripts')
<script src="{{asset('js/libs/bootstrap-fileinput/js/plugins/canvas-to-blob.min.js')}}" type="text/javascript"></script>
<script src="{{asset('js/libs/bootstrap-fileinput/js/fileinput.min.js')}}"></script>
<script src="{{asset('js/libs/bootstrap-fileinput/js/fileinput_locale_ru.js')}}"></script>
<script src="{{asset('js/libs/viewer/viewer.min.js')}}" type="text/javascript"></script>
<script src="{{ URL::asset('js/dashboard/photosController.js') }}"></script>
<script src="{{ URL::asset('js/usersRequestsController.js') }}"></script>
<script src="{{ URL::asset('js/dashboard/dashboardController.js') }}"></script>
<!--<script>
(function () {
    $.dashboardCtrl.init();
})(jQuery);
</script>-->
@stop