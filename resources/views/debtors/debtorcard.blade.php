@extends('app')
@section('title') Карточка должника @stop
@section('css')
<link rel="stylesheet" href="{{asset('css/debtors.css')}}"/>
@stop
@section('content')
@if(isset($debtor) && !is_null($debtor))
<div class='hidden debtor-data'>
    {!! Form::hidden('customer_id_1c',$debtor->customer_id_1c) !!}
    {!! Form::hidden('loan_id_1c',$debtor->loan_id_1c) !!}
    {!! Form::hidden('debtor_id',$debtor->id) !!}
    {!! Form::hidden('passport_series',$data[0]['series']) !!}
    {!! Form::hidden('passport_number',$data[0]['number']) !!}
    {!! Form::hidden('user_infinity_extension', auth()->user()->infinity_extension) !!}
    <input type="hidden" name="overall_sum_today" id="overall_sum_today" value="">
    <input type="hidden" name="overall_sum_onday" id="overall_sum_onday" value="">
</div>
@endif
<div class="row" style="padding-bottom: 15px;">
    <div class="col-xs-12" style="text-align: center;">
        @if ($debtor->is_bigmoney == 1)
        <span style="color: red; font-size: 120%;">Займ "Большие деньги"</span><br>
        @elseif ($debtor->is_pledge == 1)
        <span style="color: red; font-size: 120%;">Залоговый займ</span><br>
        @elseif ($debtor->is_pos == 1)
        <span style="color: red; font-size: 120%;">Товарный займ</span><br>
        @endif
        @if ($debtroles['is_chief'] && $data[0]['recommend_created_at'] == null)
        <input id="add_recommend" data-toggle="modal" data-target="#debtorRecommend" type="button" class="btn btn-primary" value="Добавить рекомендацию" />
        @elseif ($debtroles['is_chief'] && $data[0]['recommend_created_at'] != null)
        <div class="alert alert-warning">
            <div style="position: absolute; right: 30px;">
                <a href="#" class="btn btn-default btn-xs pull-right recommend-ctrl" data-action="remove"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></a>
                <a href="#" data-toggle="modal" data-target="#debtorRecommendEdit" class="btn btn-default btn-xs pull-right"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></a>
                @if ($data[0]['recommend_completed'] == 1)
                <a href="#" class="btn btn-primary btn-xs pull-right recommend-ctrl" data-action="complete" disabled><span class="glyphicon glyphicon-ok" aria-hidden="true"></span></a>
                @else
                <a href="#" class="btn btn-default btn-xs pull-right recommend-ctrl" data-action="complete"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span></a>
                @endif
            </div>
            <h4>
                Рекомендация от {{date('d.m.Y', strtotime($data[0]['recommend_created_at']))}}
            </h4>
            {{$data[0]['recommend_text']}}
            <span class="pull-right"><i>{{$recommend_user_name}}</i></span>
        </div>
        @elseif (!$debtroles['is_chief'] && $data[0]['recommend_created_at'] != null && ($data[0]['recommend_completed'] == 0 || $data[0]['recommend_completed'] == null))
        <div class="alert alert-warning">
            <div style="position: absolute; right: 30px;">
                <a href="#" class="btn btn-default btn-xs pull-right recommend-ctrl" data-action="complete"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span></a>
            </div>
            <h4>Рекомендация от {{date('d.m.Y', strtotime($data[0]['recommend_created_at']))}}</h4>
            {{$data[0]['recommend_text']}}
            <span class="pull-right"><i>{{$recommend_user_name}}</i></span>
        </div>
        @endif
    </div>
</div>
@if ($credit_vacation_data)
<div class="alert alert-danger" role="alert">
    Кредитные каникулы! C <strong>{{date('d.m.Y', strtotime($credit_vacation_data->recalculateDate))}}</strong> по <strong>{{(!is_null($credit_vacation_data->confirmationDate)) ? date('d.m.Y', strtotime($credit_vacation_data->confirmationDate)) : 'не определено' }}</strong>
</div>
@endif
@if (!is_null($blockProlongation))
<div class="alert alert-danger" role="alert">
    Достигнута договоренность о закрытии договора {{date('d.m.Y', strtotime($blockProlongation->block_till_date))}} г.
</div>
@endif
<div class='debtor-card'>
    <div class='row'>
        <div class='col-xs-12 col-sm-6 col-lg-4'>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <u>{{$data[0]['fio']}}</u>
                    @if(auth()->user()->id==5)
                    <a target="_blank" href="{{url('customers/edit/'.$data[0]['customer_id'].'/'.$data[0]['passport_id'])}}" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-edit"></span></a>
                    @endif
                </div>
                <div class="panel-body">
                    <?php $pBgColor = '#28A93B'; ?>
                    <div class='row'>
                        <div class='col-xs-12 col-sm-6 col-lg-4'>
                            <div class="photo-gallery text-center">
                                <!--input type="button" id="photoLoad" class="btn btn-default" data-claim="{{$data[0]['claim_id']}}" value="Загрузить фото" disabled /-->
                                <a href="http://photo.fterra.ru/photos?customer_external_id={{$debtor->customer_id_1c}}" class="btn btn-default" target="_blank">Открыть фото</a>
                                <br>
                                <a href="http://photo.fterra.ru/photos?customer_external_id={{$debtor->customer_id_1c}}&types[]=7" style="margin-top: 5px;" class="btn btn-default" target="_blank">Мессенджер-фото</a>
                            </div>
                            @if (isset($regions_timezone[$data[0]['fact_address_region']]))
                            <?php
                            $region_time = date("H:i", strtotime($regions_timezone[$data[0]['fact_address_region']] . ' hour'));
                            $arRegionTime = explode(':', $region_time);
                            $weekday = date('N', time());
                            $hour = $arRegionTime[0];
                            if ($hour[0] == '0') {
                                $hour = substr($hour, 1);
                            }
                            if ($weekday == 6 || $weekday == 7) {
                                $pBgColor = ($hour < 9 || $hour >= 20) ? '#DE5454' : '#28A93B';
                            } else {
                                $pBgColor = ($hour < 8 || $hour >= 22) ? '#DE5454' : '#28A93B';
                            }
                            ?>
                            <div class="text-center" style="margin-top: 15px;">
                                <span style="color: #fff;"><p style="margin: 5px; background-color: {{ $pBgColor }};">Время по адресу проживания<br>
                                
                                {{ $region_time }}</p>
                                </span>
                                @if ($pBgColor == '#DE5454')
                                <br>
                                <button class="btn btn-primary" id="callAllow">Разрешить звонок</button>
                                @endif
                            </div>
                            @else
                            <?php
                            $weekday = date('N', time());
                            $hour = date("H", time());
                            if ($hour[0] == '0') {
                                $hour = substr($hour, 1);
                            }
                            if ($weekday == 6 || $weekday == 7) {
                                $pBgColor = ($hour < 9 || $hour >= 20) ? '#DE5454' : '#28A93B';
                            } else {
                                $pBgColor = ($hour < 8 || $hour >= 22) ? '#DE5454' : '#28A93B';
                            }
                            ?>
                            <div class="text-center" style="margin-top: 15px;">
                                <span style="color: #fff;"><p style="margin: 5px; background-color: {{ $pBgColor }};">Время по адресу проживания<br>
                                
                                {{ date('H:i', time()) }}</p>
                                </span>
                                @if ($pBgColor == '#DE5454')
                                <br>
                                <button class="btn btn-primary" id="callAllow">Разрешить звонок</button>
                                @endif
                            </div>
                            @endif
                            <div class="text-center" style="margin-top: 15px;">
                                <span>
                                    <a href="{{ config('options.archive') }}passport_series={{ $debtor->passport_series }}&passport_number={{ $debtor->passport_number }}&loan_external_id={{ $debtor->loan_id_1c }}"
                                       style="color: #fff; text-decoration: none;">
                                        <p class="el_archive" style="margin: 5px; background-color: {{ $pBgColor }};">
                                            Электронный архив
                                        </p>
                                    </a>
                                </span>
                                @if($debtor->printCourtOrder() && auth()->user()->isChiefSpecialist())
                                <span>
                                    <a href="{{route('debtor.courtorder',$debtor->id)}}"
                                       style="color: #fff; text-decoration: none;">
                                        <p class="el_archive" style="margin: 5px; background-color: #d9534f;">
                                            Печать заявления о выдаче судебного приказа
                                        </p>
                                    </a>
                                </span>
                                @endif
                            </div>
                        </div>
                        <input type="hidden" id="canCall" name="canCall" value="{{ ($pBgColor == '#28A93B') ? 1 : 0 }}">
                        
                        <div class='col-xs-12 col-sm-6 col-lg-8 text-center'>
                            <div class="btn-group btn-group-sm btn-group-vertical">
                                <a href="{{url('debtors/debtorcard/createPdf/' . $contractforms['anketa'] . '/'.$debtor->id.'/0')}}" target="_blank" class="btn btn-default">Анкета</a>
                                <!--button disabled class='btn btn-default'>Связанные лица</button-->
                                <button class='btn btn-default' disabled>Прочие контакты</button>
                            </div>
                            <div class='btn-group btn-group-sm btn-group-vertical'>
                                <a class='btn btn-default' href='{{url('debtors/logs/'.$debtor->id)}}'>История изменений</a>
                                <a id="debtor_history_button" class='btn btn-default' href='{{url('debtors/history/'.$debtor->id)}}' target="_blank" disabled>История заемщика</a>
                                @if (isset($debtroles['personal_notice']))
                                <a id="planDepartureLink" data-action="{{($isPlannedDeparture) ? 'remove' : 'add'}}" data-link="{{url('ajax/debtors/changePlanDeparture/' . $debtor->id . '/')}}" class='btn {{($isPlannedDeparture) ? 'btn-danger' : 'btn-default'}}' href='#'>{{($isPlannedDeparture) ? 'Выезд запланирован' : 'Запланировать выезд'}}</a>
                                @endif
                            </div>
                        </div>
                        @if (!isset($debtroles['personal_notice']))
                        <div class='col-xs-12 col-sm-6 col-lg-8 text-center' style="padding-top: 4px;">
                            <div class='btn-group btn-group-sm btn-group-vertical'>
                                <a href="#" data-toggle="modal" data-target="#debtorSendedNotices" class="btn btn-default">Отправленные уведомления</a>
                            </div>
                        </div>
                        @endif
                        <div class='col-xs-12 col-sm-6 col-lg-8 text-center' style="padding-top: 4px;">
                            <div class='btn-group btn-group-sm btn-group-vertical'>
                                <a href="#" data-toggle="modal" data-target="#debtorSmsSent" class="btn btn-default">Отправленные SMS</a>
                            </div>
                        </div>
                        @if ($debtroles['is_chief'])
                        <div class='col-xs-12 col-sm-6 col-lg-8 text-center pull-right' style="padding-top: 15px;">
                            <div class="btn-group btn-group-sm btn-group-vertical" style="width: 100%;">
                                <a class='btn change-personal-data {{$data[0]['non_interaction'] == 1 ? 'btn-danger' : 'btn-default'}}' data-action="{{$data[0]['non_interaction'] == 0 ? 'on' : 'off'}}_non_interaction" href='#' data-link="{{url('ajax/debtors/changePersonalData/' . $debtor->id . '/')}}">Отказ от взаимодействия (по форме)</a>
                            </div>
                        </div>
                        <div class='col-xs-12 col-sm-6 col-lg-8 text-center pull-right' style="padding-top: 2px;">
                            <div class="btn-group btn-group-sm btn-group-vertical" style="width: 100%;">
                                <a class='btn change-personal-data {{$data[0]['non_interaction_nf'] == 1 ? 'btn-danger' : 'btn-default'}}' data-action="{{$data[0]['non_interaction_nf'] == 0 ? 'on' : 'off'}}_non_interaction_nf" href='#' data-link="{{url('ajax/debtors/changePersonalData/' . $debtor->id . '/')}}">Отказ от взаимодействия (не по форме)</a>
                            </div>
                        </div>
                        <div class='col-xs-12 col-sm-6 col-lg-8 text-center pull-right' style="padding-top: 2px;">
                            <div class="btn-group btn-group-sm btn-group-vertical" style="width: 100%;">
                                <a class='btn change-personal-data {{$data[0]['by_agent'] == 1 ? 'btn-danger' : 'btn-default'}}' data-action="{{$data[0]['by_agent'] == 0 ? 'on' : 'off'}}_by_agent" href='#' data-link="{{url('ajax/debtors/changePersonalData/' . $debtor->id . '/')}}">Взаимодействие через представителя</a>
                            </div>
                        </div>
                        <div class='col-xs-12 col-sm-6 col-lg-8 text-center pull-right' style="padding-top: 2px;">
                            <div class="btn-group btn-group-sm btn-group-vertical" style="width: 100%;">
                                <a class='btn change-personal-data {{$data[0]['recall_personal_data'] == 1 ? 'btn-danger' : 'btn-default'}}' data-action="{{$data[0]['recall_personal_data'] == 0 ? 'on' : 'off'}}_recall_personal_data" href='#' data-link="{{url('ajax/debtors/changePersonalData/' . $debtor->id . '/')}}">Отзыв персональных данных</a>
                            </div>
                        </div>
                        @else
                        @if ($data[0]['non_interaction'] == 1)
                        <div class='col-xs-12 col-sm-6 col-lg-8 text-center pull-right' style="padding-top: 2px;">
                            <div class="btn-group btn-group-sm btn-group-vertical" style="width: 100%;">
                                <button class='btn btn-danger'>Отказ от взаимодействия (по форме)</button>
                            </div>
                        </div>
                        @endif
                        @if ($data[0]['non_interaction_nf'] == 1)
                        <div class='col-xs-12 col-sm-6 col-lg-8 text-center pull-right' style="padding-top: 2px;">
                            <div class="btn-group btn-group-sm btn-group-vertical" style="width: 100%;">
                                <button class='btn btn-danger'>Отказ от взаимодействия (не по форме)</button>
                            </div>
                        </div>
                        @endif
                        @if ($data[0]['by_agent'] == 1)
                        <div class='col-xs-12 col-sm-6 col-lg-8 text-center pull-right' style="padding-top: 2px;">
                            <div class="btn-group btn-group-sm btn-group-vertical" style="width: 100%;">
                                <button class='btn btn-danger'>Взаимодействие через представителя</button>
                            </div>
                        </div>
                        @endif
                        @if ($data[0]['recall_personal_data'] == 1)
                        <div class='col-xs-12 col-sm-6 col-lg-8 text-center pull-right' style="padding-top: 2px;">
                            <div class="btn-group btn-group-sm btn-group-vertical" style="width: 100%;">
                                <button class='btn btn-danger'>Отзыв персональных данных</button>
                            </div>
                        </div>
                        @endif
                        @endif
                        <div class='col-xs-12 col-sm-6 col-lg-8 text-center pull-right' style="padding-top: 2px;">
                            <div class="btn-group btn-group-sm btn-group-vertical" style="width: 100%;">
                                <button data-toggle="modal" data-target="#debtorTherdPeopleAgreementInfo" class='btn {{($third_people_agreement) ? 'btn-primary' : 'btn-danger'}}'>{{($third_people_agreement) ? 'Согласие на взаимодействие с 3-ми лицами подписано' : 'Согласие на взаимодействие с 3-ми лицами не подписано'}}</button>
                            </div>
                        </div>
                        @if ($data[0]['date_restruct_agreement'])
                        <div class='col-xs-12 col-sm-6 col-lg-8 text-center pull-right' style="padding-top: 2px;">
                            <div class="btn-group btn-group-sm btn-group-vertical" style="width: 100%;">
                                <button class='btn btn-success'>Реструктуризация от {{$data[0]['date_restruct_agreement']}}</button>
                            </div>
                        </div>
                        @endif
                        
                        @if (isset($dataHasPeaceClaim['result']) && $dataHasPeaceClaim['result'])
                        <div class='col-xs-12 col-sm-6 col-lg-8 text-center pull-right' style="padding-top: 15px;">
                            <div class="btn-group btn-group-sm btn-group-vertical" style="width: 100%;">
                        @if ($dataHasPeaceClaim['has_peace'])
                                <a href="javascript:void(0);" class="btn btn-danger">Мировое соглашение</a>
                        @else
                                <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#newDebtorPeace">Мировое соглашение</a>
                        @endif
                            </div>
                        </div>
                        
                        <div class='col-xs-12 col-sm-6 col-lg-8 text-center pull-right' style="padding-top: 2px;">
                            <div class="btn-group btn-group-sm btn-group-vertical" style="width: 100%;">
                        @if ($dataHasPeaceClaim['has_claim'] || $dataHasPeaceClaim['has_peace'])
                                <a href="javascript:void(0);" class="btn btn-danger">Соглашение о приостановке процентов</a>
                        @else
                                <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#newDebtorClaim">Соглашение о приостановке процентов</a>
                        @endif
                            </div>
                        </div>
                        @endif
                    </div>
                    <br>
                    <table class="table table-condensed debtor-card-data">
                        <tr class='active'>
                            <td colspan="3" class='text-center'>
                                <span>Паспорт:</span> <span>серия <strong>{{$data[0]['series']}}</strong> номер <strong>{{$data[0]['number']}}</strong></span>
                            </td>
                        </tr>
                        <tr>
                            <td>А. регистрации:</td>
                            <td style='width: 60px;'>
                                @if (isset($debtroles['remote_notice']))
                                @if ($debtor->qty_delays < 61 && str_contains($debtor->loan_id_1c, 'ККЗ'))
                                <a href="{{url('debtors/debtorcard/createPdf/' . $contractforms['notice_remote_cc'] . '/'.$debtor->id.'/0/0')}}" target="_blank" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-envelope" aria-hidden="true"></span></a>
                                @else
                                @if ($debtor->qty_delays < 71)
                                @if ($debtor->is_bigmoney == 1)
                                <a href="{{url('debtors/debtorcard/createPdf/' . $contractforms['notice_remote_big_money'] . '/'.$debtor->id.'/0/0')}}" target="_blank" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-envelope" aria-hidden="true"></span></a>
                                @else
                                <a href="{{url('debtors/debtorcard/createPdf/' . $contractforms['notice_remote'] . '/'.$debtor->id.'/0/0')}}" target="_blank" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-envelope" aria-hidden="true"></span></a>
                                @endif
                                @else
                                <a href="{{url('debtors/debtorcard/createPdf/' . $contractforms['requirement_personal'] . '/'.$debtor->id.'/' . date('Y-m-d', time()) . '/0')}}" target="_blank" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-envelope" aria-hidden="true"></span></a>
                                @endif
                                @endif
                                @elseif (isset($debtroles['personal_notice']) && !$hasSentNoticePersonal)
                                <a href="#" data-toggle="modal" data-target="#debtorNoticePersonal" class="btn btn-default btn-xs printaddr notice_personal" data-typeaddr="0"><span class="glyphicon glyphicon-envelope" aria-hidden="true"></span></a>
                                @endif
                            </td>
                            <td id="debtor-passport_address-clip">{{$data[0]['passport_address']}}</td>
                            <td>
                                @if (isset($data[0]['passport_address']) && mb_strlen($data[0]['passport_address']))
                                <button class="btn btn-default btn-xs btn-clipboard" data-clipboard-target="#debtor-passport_address-clip">
                                    <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span>
                                </button>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>А. проживания:</td>
                            <td>
                                @if (isset($debtroles['remote_notice']))
                                @if ($debtor->qty_delays < 61 && str_contains($debtor->loan_id_1c, 'ККЗ'))
                                <a href="{{url('debtors/debtorcard/createPdf/' . $contractforms['notice_remote_cc'] . '/'.$debtor->id.'/0/0')}}" target="_blank" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-envelope" aria-hidden="true"></span></a>
                                @else
                                @if ($debtor->qty_delays < 71)
                                @if ($debtor->is_bigmoney == 1)
                                <a href="{{url('debtors/debtorcard/createPdf/' . $contractforms['notice_remote_big_money'] . '/'.$debtor->id.'/0/1')}}" target="_blank" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-envelope" aria-hidden="true"></span></a>
                                @else
                                <a href="{{url('debtors/debtorcard/createPdf/' . $contractforms['notice_remote'] . '/'.$debtor->id.'/0/1')}}" target="_blank" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-envelope" aria-hidden="true"></span></a>
                                @endif
                                @else
                                <a href="{{url('debtors/debtorcard/createPdf/' . $contractforms['requirement_personal'] . '/'.$debtor->id.'/' . date('Y-m-d', time()) . '/1')}}" target="_blank" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-envelope" aria-hidden="true"></span></a>
                                @endif
                                @endif
                                @elseif (isset($debtroles['personal_notice']) && !$hasSentNoticePersonal)
                                <a href="#" data-toggle="modal" data-target="#debtorNoticePersonal" class="btn btn-default btn-xs printaddr notice_personal" data-typeaddr="1"><span class="glyphicon glyphicon-envelope" aria-hidden="true"></span></a>
                                @endif
                            </td>
                            <td id="debtor-real_address-clip">{{$data[0]['real_address']}}</td>
                            <td>
                                @if (isset($data[0]['real_address']) && mb_strlen($data[0]['real_address']))
                                <button class="btn btn-default btn-xs btn-clipboard" data-clipboard-target="#debtor-real_address-clip">
                                    <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span>
                                </button>
                                @endif
                            </td>
                        </tr>
                        <tr style="background-color: #5CCDC9;">
                            <td>Т. моб.:</td>
                            <td>
                                @if(isset($debtor) && !($debtor->non_interaction || $debtor->non_interaction_nf || $debtor->by_agent) || $debtroles['is_chief'])
                                    @if (isset($data[0]['telephone']) && mb_strlen($data[0]['telephone']) && $debtor->base != 'Архив ЗД')
                                        <button type="button" class="btn btn-default btn-xs" data-toggle="modal"
                                                data-target="#debtorSMS" data-phone="{{$data[0]['telephone']}}">
                                            <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                        </button>
                                        @if(!empty(auth()->user()->infinity_extension))
                                            <button type="button" class="btn btn-default btn-xs phone-call-btn"
                                                    data-phone="{{$data[0]['telephone']}}">
                                                <span class="glyphicon glyphicon-earphone"></span>
                                            </button>
                                        @endif


                                        <a href="whatsapp://send?phone={{$data[0]['telephone']}}"
                                           class="btn btn-default btn-xs" target="_blank">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"
                                                 fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
                                                <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
                                            </svg>
                                        </a>
                                        <a href="https://t.me/+{{$data[0]['telephone']}}" class="btn btn-default btn-xs"
                                           target="_blank">
                                            Т
                                        </a>
                                    @endif
                                @endif
                            </td>
                            <td id="debtor-phone-clip">{{$data[0]['telephone']}}</td>
                            <td>
                                <button class="btn btn-default btn-xs btn-clipboard" data-clipboard-target="#debtor-phone-clip">
                                    <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>Т. домашний:</td>
                            <td>
                                @if (isset($data[0]['telephonehome']) && mb_strlen($data[0]['telephonehome']))
                                <!--button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#debtorSMS" data-phone="{{$data[0]['telephonehome']}}">
                                    <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                </button-->
                                @if(!empty(auth()->user()->infinity_extension))
                                <button type="button" class="btn btn-default btn-xs phone-call-btn" data-phone="{{$data[0]['telephonehome']}}">
                                    <span class="glyphicon glyphicon-earphone"></span>
                                </button>
                                @endif
                                @endif
                            </td>
                            <td id="debtor-telephonehome-clip">{{$data[0]['telephonehome']}}</td>
                            <td>
                                @if (isset($data[0]['telephonehome']) && mb_strlen($data[0]['telephonehome']))
                                <button class="btn btn-default btn-xs btn-clipboard" data-clipboard-target="#debtor-telephonehome-clip">
                                    <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span>
                                </button>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Т. организации:</td>
                            <td>
                                @if (isset($data[0]['telephoneorganiz']) && mb_strlen($data[0]['telephoneorganiz']))
                                <!--button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#debtorSMS" data-phone="{{$data[0]['telephoneorganiz']}}">
                                    <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                </button-->
                                @if(!empty(auth()->user()->infinity_extension))
                                <button type="button" class="btn btn-default btn-xs phone-call-btn" data-phone="{{$data[0]['telephoneorganiz']}}">
                                    <span class="glyphicon glyphicon-earphone"></span>
                                </button>
                                @endif
                                @endif
                            </td>
                            <td id="debtor-telephoneorganiz-clip">{{$data[0]['telephoneorganiz']}}</td>
                            <td>
                                @if (isset($data[0]['telephoneorganiz']) && mb_strlen($data[0]['telephoneorganiz']))
                                <button class="btn btn-default btn-xs btn-clipboard" data-clipboard-target="#debtor-telephoneorganiz-clip">
                                    <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @if (strtotime($data[0]['loans_created_at']) > 1544720399)
                        <tr>
                            <td>Т. родств.:</td>
                            <td>
                                @if (isset($data[0]['telephonerodstv']) && mb_strlen($data[0]['telephonerodstv']))
                                <!--button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#debtorSMS" data-phone="{{$data[0]['telephonerodstv']}}">
                                    <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                </button-->
                                @if(!empty(auth()->user()->infinity_extension))
                                <button type="button" class="btn btn-default btn-xs phone-call-btn" data-phone="{{$data[0]['telephonerodstv']}}">
                                    <span class="glyphicon glyphicon-earphone"></span>
                                </button>
                                @endif
                                @endif
                            </td>
                            <td id="debtor-telephonerodstv-clip">{{$data[0]['telephonerodstv']}}</td>
                            <td>
                                @if (isset($data[0]['telephonerodstv']) && mb_strlen($data[0]['telephonerodstv']))
                                <button class="btn btn-default btn-xs btn-clipboard" data-clipboard-target="#debtor-telephonerodstv-clip">
                                    <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td>Т. доп.:</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Т. др.:</td>
                            <td>
                                @if (isset($data[0]['anothertelephone']) && mb_strlen($data[0]['anothertelephone']))
                                <!--button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#debtorSMS" data-phone="{{$data[0]['anothertelephone']}}">
                                    <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                </button-->
                                @if(!empty(auth()->user()->infinity_extension))
                                <button type="button" class="btn btn-default btn-xs phone-call-btn" data-phone="{{$data[0]['anothertelephone']}}">
                                    <span class="glyphicon glyphicon-earphone"></span>
                                </button>
                                @endif
                                @endif
                            </td>
                            <td id="debtor-anothertelephone-clip">{{$data[0]['anothertelephone']}}</td>
                            <td>
                                @if (isset($data[0]['anothertelephone']) && mb_strlen($data[0]['anothertelephone']))
                                <button class="btn btn-default btn-xs btn-clipboard" data-clipboard-target="#debtor-anothertelephone-clip">
                                    <span class="glyphicon glyphicon-log-in" aria-hidden="true"></span>
                                </button>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Т. уточненный:</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Т. моб. старый:</td>
                            <td></td>
                            <td></td>
                            <td></td>s
                        </tr>
                        @if (str_contains($data[0]['email'], '@'))
                            <tr>
                                <td>E-mail:</td>
                                <td>
                                    <input type="hidden" name="debtor_id" id="debtCardId" value="{{$debtor['id']}}">
                                    <button onclick="($.debtorsCtrl.emailMessagesList({{auth()->user()->id}}))" target="_blank" class="btn btn-default btn-xs">
                                        <span class="glyphicon glyphicon-envelope" aria-hidden="true"></span>
                                    </button>
                                </td>
                                <td>
                                    <a data-toggle="modal" data-target="#debtorEmailText">{{$data[0]['email']}}</a>
                                </td>
                                <td></td>
                            </tr>
                        @endif
                        <tr>
                            <td>Ответственный:</td>
                            <td></td>
                            <?php
                            if ($data[0]['not_responsible_user_open'] == true) {
                                $color_td_style = ' style="color: #A60000;"';
                            } else {
                                $color_td_style = '';
                            }
                            ?>
                            <td<?= $color_td_style; ?>>
                                {{$data[0]['responsible_user_fio']}} ({{(isset($debtroles['resp_user_remote']) && $debtroles['resp_user_remote']) ? 'Отдел удаленного взыскания' : ''}}{{(isset($debtroles['resp_user_personal']) && $debtroles['resp_user_personal'] && strpos($data[0]['responsible_user_fio'], 'Котельникова') === false) ? 'Отдел личного взыскания' : ''}})
                                @if (auth()->user()->hasRole('debtors_remote') && $data[0]['not_responsible_user_open'])
                                <br><a class="btn btn-primary" href="/debtors/setSelfResponsible/{{$debtor->id}}">Закрепить за собой</a>
                                @endif
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Структурное<br>подразделение:</td>
                            <td></td>
                            <td>{{$data[0]['str_podr_name']}}</td>
                            <td></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class='col-xs-12 col-sm-6 col-lg-8'>
            <div class="panel panel-default debtor-comments-table">
                <div class="panel-heading">
                    Комментарии
                </div>
                <table class="table table-condensed">
                    <thead>
                        <tr>
                            <th>Дата план</th>
                            <th>Дата факт</th>
                            <th>Тип мероприятия</th>
                            <th>Результат</th>
                            <th style="width: 350px;">Отчет</th>
                            <th>Ответственный</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="debtor-comments-block">
                        @if (is_array($dataevents) && count($dataevents))
                        <?php
                        $curDay = $dataevents[0]['day'];
                        $row_color = 'ffffff';
                        $isMasterUser = \App\DebtorUsersRef::isMasterUserWithSlaves(Auth::user()->id);
                        ?>
                        @foreach ($dataevents as $event)
                        <?php
                        if ($curDay != $event['day'] || $row_color == 'ffaaa7' || $row_color == '6C8CD5') {
                            if ($row_color == 'ffffff') {
                                $row_color = 'dddddd';
                            } else {
                                $row_color = 'ffffff';
                            }

                            $curDay = $event['day'];
                        }
                        if (strpos($event['user_id_1c'], 'Кондратенко И') !== false || strpos($event['user_id_1c'], 'Рифель Ю. О.') !== false) {
                            $row_color = 'ffaaa7';
                        }
                        if ($event['event_type_id'] == 16) {
                            $row_color = '6C8CD5';
                        }
                        ?>
                        <tr style="background-color: #{{$row_color}}">
                            <td>{{($event['date'] == '0000-00-00 00:00:00' || is_null($event['date'])) ? '' : date('d.m.Y H:i:s', strtotime($event['date']))}}</td>
                            <td>{{date('d.m.Y', strtotime($event['de_created_at']))}}</td>
                            <td>
                                @if(array_key_exists($event['event_type_id'],$debtdata['event_types']))
                                {{$debtdata['event_types'][$event['event_type_id']]}}
                                @endif
                            </td>
                            <td>
                                @if(array_key_exists($event['event_result_id'],$debtdata['event_results']))
                                {{$debtdata['event_results'][$event['event_result_id']]}}
                                @endif
                            </td>
                            <td>{{$event['report']}}</td>
                            <td>{{$event['login']}}</td>
                            <td>
                                <input type="checkbox" name="eventDone[]" value="{{$event['id']}}" {{($event['completed'] == 1) ? 'checked' : ''}}/>
                                @if($isMasterUser)
                                <button type="button" name="debtor_event_edit" class="btn btn-default btn-xs" onclick="$.debtorsCtrl.openDebtorEvent({{$event['id']}});"><span class="glyphicon glyphicon-pencil"></span></button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr>
                            <td colspan="7">Мероприятия еще не созданы</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{$data[0]['fio']}}
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <div class="col-xs-12 col-sm-6 col-lg-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Данные о займе и задолженности
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-lg-12" style="margin-bottom: 15px;" id="multi_loan_block">
                            <p style="text-align: center; color: blue; font-weight: bold;">Получение информации...</p>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-xs-12 col-lg-5 debtor-loan-data'>
                            <ul class='list-group'>
                                <li class='list-group-item'>
                                    <small>Подразделение (заявка):</small> {{$data[0]['address']}}
                                </li>
                                @if (isset($data[0]['loan_subdivision_address']))
                                <li class='list-group-item'>
                                    <small>Подразделение (договор):</small> {{$data[0]['loan_subdivision_address']}}
                                </li>
                                @endif
                                <li class='list-group-item'>
                                    <small>Тип займа: {{(isset($data[0]['repl_in_cash']) && $data[0]['repl_in_cash']) ? 'наличные' : 'на карту'}}</small><br>
                                    @if(isset($data[0]['is_terminal']) && $data[0]['is_terminal'] == 1)
                                    2.2
                                    @elseif($data[0]['loantype_special_pc']==1)
                                    {{$data[0]['loan_special_percent']}}
                                    @else
                                    <b>({{$loan_first_percent}})</b> {{$loan_percents->pc}}
                                    @endif
                                    %,
                                    пр. {{$loan_percents->exp_pc}}%, 
                                    пеня {{$loan_percents->fine}}%
                                </li>
                                <li class='list-group-item'>
                                    <small>Договор:</small><br> 
                                    {{$data[0]['loan_id_1c']}} от {{$data[0]['loans_created_at']}} | (сумма займа: {{number_format($data[0]['d_money'], 2, '.', '')}} руб.)
                                    @if(isset($data[0]['repl_in_cash']) && !$data[0]['repl_in_cash'] && $data[0]['has_tranche'])
                                    <!-- (транш №{!!str_pad($data[0]['loan_tranche_number'], 3, '0', STR_PAD_LEFT)!!} к договору № {{$data[0]['loan_first_loan_id_1c']}} от {{with(new \Carbon\Carbon($data[0]['loan_first_loan_date']))->format('d.m.Y')}}) -->
                                    @endif
                                </li>
                                <li class='list-group-item'>
                                    <small>Срок:</small> {{$data[0]['time']}} {{ ($debtor->is_pos || $debtor->is_pledge || $debtor->is_bigmoney) ? 'мес.' : 'дн.' }}
                                </li>
                                <li class='list-group-item'>
                                    <small>Дата начала:</small> {{$data[0]['loan_date_start']}}
                                </li>
                                <li class='list-group-item'>
                                    <small>Дата окончания:</small> {{$data[0]['loan_date_end']}}
                                </li>
                                <li class='list-group-item'>
                                    <span>Просроченных дней:</span> <span class="debt-exp_days-ondate">{{$data[0]['qty_delays']}}</span>
                                </li>
                                <li class='list-group-item'>
                                    <span>База:</span> {{$data[0]['base']}} | Группа долга: {{$data[0]['debt_group_text']}}
                                </li>
                                @if ($data_pos)
                                <li class='list-group-item'>
                                    <span>Товары:</span><br>
                                @foreach ($data_pos as $good_item)
                                {{ $good_item['good_name'] }}, {{ $good_item['good_price'] }} руб, ({{ $good_item['good_qty'] }} шт.)<br>
                                @endforeach
                                </li>
                                @endif
                                
                                @if ($data_pledge)
                                <li class='list-group-item'>
                                    {!! $data_pledge !!}
                                </li>
                                @endif
                                
                                @if (count($insurances_data))
                                @foreach ($insurances_data as $insurance_data)
                                <li class='list-group-item'>
                                    <span style="color: red;">Страховка:</span> полис {{ $insurance_data['policy_number'] }}<br>
                                    {{ $insurance_data['insurance_name_company'] }}, {{ $insurance_data['name'] }}<br>
                                    сумма {{ number_format($insurance_data['money'] / 100, 2, '.', '') }}, срок {{ $insurance_data['time'] }} мес.
                                </li>
                                @endforeach
                                @endif
                            </ul>
                        </div>
                        <div class='col-xs-12 col-lg-7' id="calc-data-block">
                            @if ($data[0]['loantype_id_1c'] != 'ARM000047')
                            <table class='table table-condensed table-bordered debtor-debt-table'>
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Текущая</th>
                                        <th>
                                            На дату<br>
                                            <input class='form-control input-sm' name='debt_calc_date' type='date'/>                                            
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span>Общая:</span></td>
                                        <td style="text-align: center;"><b id="current-total-debt">{{number_format($data[0]['sum_indebt'] / 100, 2, '.', '')}}</b></td>
                                        <td style="text-align: center; font-weight: bold" class='debt-money-ondate'></td>
                                    </tr>
                                    <tr>
                                        <td><span>Основной долг:</span></td>
                                        <td style="text-align: center;">{{number_format($data[0]['d_od'] / 100, 2, '.', '')}}</td>
                                        <td style="text-align: center;" class='debt-od-ondate'></td>
                                    </tr>
                                    <tr>
                                        <td><span>Проценты:</span></td>
                                        <td style="text-align: center;">{{number_format($data[0]['d_pc'] / 100, 2, '.', '')}}</td>
                                        <td style="text-align: center;" class='debt-pc-ondate'></td>
                                    </tr>
                                    <tr>
                                        <td><span>Проценты пр.:</span></td>
                                        <td style="text-align: center;">{{number_format($data[0]['d_exp_pc'] / 100, 2, '.', '')}}</td>
                                        <td style="text-align: center;" class='debt-exp_pc-ondate'></td>
                                    </tr>
                                    <tr>
                                        <td><span>Пеня:</span></td>
                                        <td style="text-align: center;">{{number_format($data[0]['d_fine'] / 100, 2, '.', '')}}</td>
                                        <td style="text-align: center;" class='debt-fine-ondate'></td>
                                    </tr>
                                    <tr>
                                        <td><span>Переплата:</span></td>
                                        <td style="text-align: center;">{{number_format($data[0]['d_overpayments'] / 100, 2, '.', '')}}</td>
                                        <td style="text-align: center;" class='debt-overpayments-ondate'></td>
                                    </tr>
                                    <tr>
                                        <td><span>Изменение платежа:</span></td>
                                        <td colspan="2" style="text-align: center; vertical-align: middle;" class="debt-diffpay-ondate"></td>
                                    </tr>
                                    @if ($current_schedule)
                                    <tr>
                                        <td colspan="3">
                                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#schedule" style="font-size: 12px;">
                                                Текущий график платежей
                                            </button>
                                            @if ($create_schedule)
                                            <br/>
                                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createSchedule" style="font-size: 12px;">
                                                Начальный график платежей
                                            </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                            @else
                             
                            @endif
                            @if (isset($lastRepayment) && $lastRepayment)
                            <table class="table table-condensed table-bordered">
                                <tbody>
                                    <tr>
                                        {{$lastRepayment->name}} от {{$lastRepayment->created_at}}
                                    </tr>
                                </tbody>>
                            </table>
                            @endif
                            @if(Auth::user()->isAdmin())
                            <a href="{{url('adminpanel/tester/solvedebtornoclaim/'.$debtor->id)}}">Подгрузить заявку</a>
                            <br>
                            @endif
                            @if($enableRecurrentButton)
                            <a href="/debtor/recurrent/query?debtor_id={{$debtor->id}}&amount={{$data[0]['sum_indebt']}}" class="btn btn-primary" id="recurrentButton">Списать (безакцепт)</a>
                            @endif
                        </div>
                    </div>
                    @if (count($multi_loans) > 1)
                    <!--div class="row">
                        <div class="col-lg-12" style="margin-top: 15px;">
                            <p style="text-align: center;"><b>Общая задолженность на сегодня: <span> руб.</span></b></p>
                        </div>
                    </div-->
                    @endif
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    Платежи
                    @if(Auth::user()->id==5)
                    <button type='button' class='btn btn-default' onclick="$.debtorsCtrl.uploadOrders({{$debtor->id}});"><span class='glyphicon glyphicon-refresh'></span></button>
                    @endif
                </div>
                <table class="table table-bordered table-condensed debtor-payments-table">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Дата</th>
                            <th>Документ</th>
                            <th>Сумма<br>расход</th>
                            <th>Сумма<br>приход</th>
                            <th>Движение</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{--*/ $i = 1 /*--}}
                        <?php $day_sum = 0; $payment_date = ''; $cnt_payments = count($datapayments); ?>
                        @foreach ($datapayments as $k => $payment)
                        <?php
                        if ($payment->type != 3 && $payment->type != 0) {
                            if ($payment_date == date('d.m.Y', strtotime($payment->created_at))) {
                                $day_sum += $payment->money;
                                
                            } else {
                                $payment_date = date('d.m.Y', strtotime($payment->created_at));
                                ?>
                                <tr><td colspan="6" style="background: #84C9FF;">Сумма: {{ number_format($day_sum / 100, 2, '.', '') }} руб.</td></tr>
                                <?php
                                $day_sum = $payment->money;
                            }
                        }
                        ?>
                        <tr>
                            <td>{{$i}}</td>
                            <td>
                                {{ date('d.m.Y', strtotime($payment->created_at)) }}
                                <br>
                                {{ date('H:i', strtotime($payment->created_at)) }}
                            </td>
                            <td>{{($payment->type == 3) ? 'Списание на карту' : $payment->reason}} {{ ($payment->type == 3 && isset($data[0]['card_type_string'])) ? $data[0]['card_type_string'] : '' }}</td>
                            <td>{{(in_array($payment->type,[0,3])) ? number_format($payment->money / 100, 2, '.', '') . ' руб.' : ''}}</td>
                            <td>{{(in_array($payment->type,[5,18,19,20,21,22,30,36,37,47,48,49])) ? number_format($payment->money / 100, 2, '.', '') . ' руб.' : ''}}</td>
                            <td>
                                @if(array_key_exists($payment->purpose,$purposes)){{$purposes[$payment->purpose]}}@endif
                                @if($payment->type == 37)
                                Оплата пакета SMS
                                @endif
                                @if($payment->type == 49)
                                Личный юрист
                                @endif
                            </td>
                        </tr>
                        <?php
                        if ($i == $cnt_payments) {
                                ?>
                                <tr><td colspan="6" style="background: #84C9FF;">Сумма: {{ number_format($day_sum / 100, 2, '.', '') }} руб.</td></tr>
                                <?php
                        }
                        ?>
                        {{--*/ $i++ /*--}}
                        @endforeach
                        @if ($i == 1)
                        <tr>
                            <td>{{$i}}</td>
                            <td>{{date('d.m.Y', strtotime($data[0]['loan_date_start']))}}</td>
                            <td>Списание на карту</td>
                            <td>{{number_format($data[0]['d_money'], 2, '.', '')}}</td>
                            <td></td>
                            <td></td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @if(isset($debtor) && !($debtor->non_interaction || $debtor->non_interaction_nf || $debtor->by_agent) || $debtroles['is_chief'])
            
                <div class="col-xs-12 col-sm-6 col-lg-8">
                    <form action="/debtors/addevent" id="event-form" enctype="multipart/form-data" method="POST">
                        {{ csrf_field() }}
                        <input type="hidden" name="debtor_id" value="{{$debtor_id}}">
                        <input type="hidden" name="user_id" value="{{$current_user_id}}">
                        <div class="row">
                            <div class="col-xs-12 col-lg-6">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        Редактирование мероприятия
                                    </div>
                                    <div class="panel-body">
                                        <div class='form-horizontal'>
                                            <div class='form-group'>
                                                <label class='col-xs-12 col-sm-4 text-right'>Дата мероприятия:</label>
                                                <div class='col-xs-12 col-sm-8 form-inline'>
                                                    <input id="datetimepickerCreate" type="text" name="created_at"
                                                           value="{{date('d.m.Y H:i', time())}}" class="form-control"
                                                           readonly>
                                                </div>
                                            </div>
                                            <div class='form-group'>
                                                <label class='col-xs-12 col-sm-4 text-right'>Тип мероприятия:</label>
                                                <div class='col-xs-12 col-sm-8'>
                                                    <select name="event_type_id" class="form-control">
                                                        <option value=""></option>
                                                        @foreach ($debtdata['event_types'] as $k => $type)
                                                            <option value="{{$k}}">{{$type}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class='form-group'>
                                                <label class='col-xs-12 col-sm-4 text-right'>Причина просрочки:</label>
                                                <div class='col-xs-12 col-sm-8'>
                                                    <select name="overdue_reason_id" class="form-control">
                                                        <option value=""></option>
                                                        @foreach ($debtdata['overdue_reasons'] as $k => $type)
                                                            <option value="{{$k}}">{{$type}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class='form-group'>
                                                <label class='col-xs-12 col-sm-4 text-right'>Группа долга:</label>
                                                <div class='col-xs-12 col-sm-8'>
                                                        <?php
                                                        $sel_disabled = '';
                                                        $bool_sel_disabled = false;
                                                        if ($data[0]['base'] == 'Архив убытки' || $data[0]['base'] == 'Архив компании') {
                                                            $sel_disabled = ' disabled';
                                                            $bool_sel_disabled = true;
                                                        }
                                                        ?>
                                                    <select name="debt_group_id" class="form-control"{{$sel_disabled}}>
                                                        @if ($data[0]['d_debt_group_id'] == null && $bool_sel_disabled)
                                                            <option value="" selected disabled></option>
                                                        @else
                                                            <option value=""></option>
                                                                <?php foreach ($debtdata['debt_groups'] as $k => $type) {
                                                                $selected = '';
                                                                if ($bool_sel_disabled) {
                                                                    if ($k == $data[0]['d_debt_group_id']){
                                                                        $selected = ' selected';
                                                                    }
                                                                }
                                                                if (isset($debtroles['personal_notice']) && !in_array($k,
                                                                        [1, 2, 3, 5, 6, 19, 51])) {
                                                                    continue;
                                                                }
//                                                                ?>
                                                            <option value="{{ $k }}"{{$selected}}>{{$type}}</option>
                                                            <?php } ?>
                                                        @endif
                                                    </select>
                                                </div>
                                            </div>
                                            <div class='form-group'>
                                                <label class='col-xs-12 col-sm-4 text-right'>Результат:</label>
                                                <div class='col-xs-12 col-sm-8'>
                                                    <select name="event_result_id" class="form-control">
                                                        <option value=""></option>
                                                        @foreach ($debtdata['event_results'] as $k => $type)
                                                            <option value="{{$k}}">{{$type}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            @if ($debtroles['is_chief'])
                                                <div class='form-group' id='chief_event_field'>
                                                    <label class='col-xs-12 col-sm-4 text-right'>От имени:</label>
                                                    <div class='col-xs-12 col-sm-8 form-inline'>
                                                        <input name='users@login' type='text'
                                                               class='form-control autocomplete'
                                                               data-hidden-value-field='search_field_users@id'
                                                               style='width: 100%;'/>
                                                        <input id="chief_from_user_id" name='search_field_users@id'
                                                               type='hidden'/>
                                                    </div>
                                                </div>
                                            @endif
                                            <div class='form-group'>
                                                <label class='col-xs-12 col-sm-4 text-right'>Отчет о
                                                    мероприятии:</label>
                                                <div class='col-xs-12 col-sm-8'>
                                                    <textarea style="min-height: 150px;" name="report"
                                                              class="form-control"></textarea>
                                                </div>
                                            </div>
                                            <div class='form-group'>
                                                <div class='col-xs-12'>
                                                    <label class="btn btn-default btn-file pull-right">
                                                        Прикрепить фото <input name="messenger_photo" type="file"
                                                                               onchange="$('#upload-file-info').text(this.files[0].name)"
                                                                               style="display: none;">
                                                    </label>
                                                </div>
                                            </div>
                                            <div class='form-group'>
                                                <div class='col-xs-12'>
                                                    <span class='label label-info pull-right'
                                                          id="upload-file-info"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-12 col-lg-6">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        Планирование
                                    </div>
                                    <div class="panel-body form-horizontal">
                                        <div class="form-group">
                                            <label class='col-xs-12 col-sm-4 text-right'>Тип мероприятия:</label>
                                            <div class='col-xs-12 col-sm-8'>
                                                <select name="event_type_id_plan" class="form-control">
                                                    <option value=""></option>
                                                    @foreach ($debtdata['event_types'] as $k => $type)
                                                        <option value="{{$k}}">{{$type}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class='form-group'>
                                            <label class='col-xs-12 col-sm-4 text-right'>Дата мероприятия</label>
                                            <div class='col-xs-12 col-sm-8 form-inline'>
                                                <input id="datetimepickerPlan" type="text" name="date"
                                                       class="form-control">
                                            </div>
                                        </div>
                                        <!--div class="well">
                                            <strong>Запланированное мероприятие: </strong>&nbsp;&nbsp;<input type="checkbox" name="completed" value="1"> Выполнено
                                        </div-->
                                        <!--div class="text-center">
                                            <br>
                                            <button id="submit_event" type="submit" class="btn btn-primary btn-lg"><span class="glyphicon glyphicon-floppy-disk"></span> Сохранить</button>
                                        </div-->
                                    </div>
                                </div>
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        Договоренность о закрытии кредитного договора
                                    </div>
                                    <div class="panel-body form-horizontal">
                                        <div class='form-group'>
                                            <label class='col-xs-12 col-sm-4 text-right'>Дата договоренности</label>
                                            <div class='col-xs-12 col-sm-8 form-inline'>
                                                <input id="datetimepickerProlongationBlock" type="text"
                                                       name="dateProlongationBlock" class="form-control">
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <br>
                                            <button id="submit_event" type="submit" class="btn btn-primary btn-lg"><span
                                                        class="glyphicon glyphicon-floppy-disk"></span> Сохранить
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            
        @endif
    </div>

</div>
<button type='button' class='btn btn-success phone-call-btn btn-xs' id='selectionCallBtn' data-phone='' style='position: fixed; display: none;'>
    <span class='glyphicon glyphicon-earphone'></span> Позвонить на: <span class='phone-number'></span>
</button>
@include('elements.debtorsSmsModal')
@include('elements.debtorsNoticePersonal')
@include('debtors.editDebtorEventModal')
@include('elements.debtors.addRecommend')
@include('elements.debtors.editRecommend')
@include('elements.debtors.pdAgreementInfo')
@include('elements.debtors.sendedNotices')
@include('elements.debtors.smsSent')
@include('elements.debtors.addDebtorPeace')
@include('elements.debtors.addDebtorClaim')

@if ($third_people_agreement)
@include('elements.debtors.third_people_agreement')
@endif

@if ($current_schedule)
@include('elements.debtors.scheduleModal', ['current_schedule' => $current_schedule])
@endif
@if ($create_schedule)
@include('elements.debtors.scheduleCreateModal', ['create_schedule' => $create_schedule])
@endif
@stop
@section('scripts')
<script src="{{asset('js/debtors/debtorsController.js?12')}}"></script>
<script src="{{asset('js/libs/bootstrap-datetimepicker/js/bootstrap-datetimepicker.js')}}"></script>
<script src="{{asset('js/libs/clipboard/clipboard.min.js')}}"></script>
<script src="{{ URL::asset('js/dashboard/photosController.js') }}"></script>
@if ($debtor->base != 'Архив ЗД')
<script>
    $(document).ready(function () {
        $(document).on('click', '.phone-call-btn', function () {
            if ($('#canCall').val() == '1') {
                $('#selectionCallBtn').hide();
                $.app.blockScreen(true);
                $.get($.app.url + '/ajax/infinity', {telephone: $(this).attr('data-phone'), type: 'call'}).done(function (data) {
                    console.log(data);
                    if (data.result) {

                    } else {
                        $.app.openErrorModal('Ошибка', data.msg);
                    }
                }).always(function () {
                    $.app.blockScreen(false);
                });
            }
        });
    });
</script>
@endif
<script>
$(document).ready(function () {
    new Clipboard('.btn-clipboard');
    $.debtorsCtrl.init();
    @if ($data[0]['loantype_id_1c'] != 'ARM000047')
    $.debtorsCtrl.initDebtorsCard();
    @else
        $.post($.app.url + '/ajax/debtors/calc/creditcard', {
                loan_id_1c: $('input[name="loan_id_1c"]').val(),
                debt_calc_date_cc: '{{date("Y-m-d", time())}}'
            }).done(function(data) {
                $('#calc-data-block').html(data);
            });
        $(document).on('change', 'input[name="debt_calc_date_cc"]', function(){
            $.post($.app.url + '/ajax/debtors/calc/creditcard', {
                loan_id_1c: $('input[name="loan_id_1c"]').val(),
                debt_calc_date_cc: $('input[name="debt_calc_date_cc"]').val()
            }).done(function(data) {
                $('#calc-data-block').html(data);
            });
        });
    @endif
    $(document).on('click', '#callAllow', function(){
        $(this).prop('disabled', true);
        $('#canCall').val('1');
    });
    $('html').mouseup(function (event) {
        if ($('[name="user_infinity_extension"]').val() == '') {
            return;
        }
        var selection = window.getSelection();
        var $elem = $(selection.anchorNode.parentElement);
        var text = selection.toString().replace(/\D/g, '');
        var $btn = $('#selectionCallBtn');
        var pos = {left: 0, top: 0};
        pos.left = event.pageX + 20;
        pos.top = event.pageY - 20;
        if (text.length >= 5) {
            $btn.children('.phone-number').text(text);
            $btn.attr('data-phone', text);
            $btn.show();
            $btn.offset(pos);
        } else {
            $btn.hide();
        }
    });
    $(function () {
        $('#datetimepickerPlan').datetimepicker({
            locale: 'ru'
        });
        $('#datetimepickerCreate').datetimepicker({
            locale: 'ru'
        });
        $('#datetimepickerProlongationBlock').datetimepicker({
            locale: 'ru'
        });
    });
    $(document).on('submit', '#event-form', function () {
        $('#submit_event').attr('disabled', true);
    });
    
    $.post($.app.url + '/ajax/debtors/loans/upload', {debtor_id: $('input[name="debtor_id"]').val()}).done(function (data) {
        $('#debtor_history_button').removeAttr('disabled');
        console.log("loansUpload: " + data);
        
        $.post($.app.url + '/ajax/debtors/loans/getmultisum', {loan_id_1c: $('input[name="loan_id_1c"]').val(), customer_id_1c: $('input[name="customer_id_1c"]').val()}).done(function (data){
            if (data == '0') {
                $('#multi_loan_block').html('');
            } else {
                $('#multi_loan_block').html(data);
            }
        });
    });
    $('#phoneThirdPeople').mask('+7 (999) 999-9999');
});
$(document).on('click', '#recurrentButton', function(){
    $(this).attr('disabled', 'disabled');
});
$(document).on('change', 'input[name="eventDone[]"]', function () {
    var v = $(this).val();
    $.post($.app.url + '/ajax/debtors/eventcomplete', {eventDone: v}).done(function (data) {
        $.app.ajaxResult(data);
    });
});
$(document).on('click', 'button[data-target="#debtorSMS"]', function () {
    if ($('input').is('[name="sms_phone_number"]')) {
        $('input[name="sms_phone_number"]').val($(this).data('phone'));
    }
});
$(document).on('change', 'input[name="sms_date"]', function () {
    var arVal = $(this).val().split('-');
    $('.sms_text').each(function () {
        $(this).text($(this).text().replace(/\d{1,2}\.\d{1,2}\.\d{4}/, arVal[2] + '.' + arVal[1] + '.' + arVal[0]));
    });
});
$(document).on('change', 'input[name="sms_id"]', function () {
    $('#sendSMS').removeAttr('disabled');
});
$(document).on('change', 'input[name="date_demand"]', function () {
    $('#sendNoticePersonal').removeAttr('disabled');
});
$(document).on('click', '#sendSMS', function () {
    var sms = $('input[name="sms_id"]:checked').parent().next().text();
    $.app.blockScreen(true);
    $.post($.app.url + '/ajax/debtors/sendsmstodebtor', {phone: $('input[name="sms_phone_number"]').val(), sms_text: sms, debtor_id_1c: $('input[name="debtor_id_1c"]').val(), debt_group_id: "{{$data[0]['d_debt_group_id']}}"}).done(function (data) {
        $.app.blockScreen(false);
        $('#debtorSMS').modal('hide');
        if (data == '-1') {
            $.app.openErrorModal('Ошибка', 'Превышен лимит отправки SMS');
        } else {
            $.app.ajaxResult(data);
            setTimeout(function () {
                window.location.reload();
            }, 2000);
        }
    });
});
$(document).on('click', '#showSmsLink', function() {
    $('#smsConntent').hide();
    $('#smsLinkContent').show();
});
$(document).on('click', '#showSmsInfo', function() {
    $('#smsLinkContent').hide();
    $('#smsConntent').show();
});
$(document).on('click', '#showSmsProps', function() {
    $('#smsConntent').hide();
    $('#smsPropsContent').show();
});
$(document).on('change', '#enableThirdPeople', function() {
    if ($(this).is(':checked')) {
        $('#phoneThirdPeople').prop('disabled', false);
    } else {
        $('#phoneThirdPeople').prop('disabled', true);
    }
});
$(document).on('change', 'input[name="paymentLinkSumType"]', function() {
    if ($(this).val() == 2) {
        $('#paymentLinkSum').prop('disabled', false);
    } else {
        $('#paymentLinkSum').prop('disabled', true);
    }
});
$(document).on('click', '#sendSMSLink', function() {
    $.app.blockScreen(true);
    if ($('#enableThirdPeople').is(':checked')) {
        var sms_phone = $('#phoneThirdPeople').val();
    } else {
        var sms_phone = $('input[name="sms_phone_number"]').val();
    }
    
    var amount = 0;
    if ($('#paymentLinkSumType1').is(':checked')) {
        amount = $('#paymentLinkSumFull').val();
    }
    if ($('#paymentLinkSumType2').is(':checked')) {
        amount = $('#paymentLinkSum').val();
    }
    
    $.post($.app.url + '/ajax/debtors/sendsmstodebtor', {phone: sms_phone, sms_type: 'link', amount: amount, debtor_id_1c: $('input[name="debtor_id_1c"]').val(), debt_group_id: "{{$data[0]['d_debt_group_id']}}"}).done(function (data) {
        $.app.blockScreen(false);
        $('#debtorSMS').modal('hide');
        if (data == '-1') {
            $.app.openErrorModal('Ошибка', 'Превышен лимит отправки SMS');
        } else {
            $.app.ajaxResult(data);
            setTimeout(function () {
                window.location.reload();
            }, 2000);
        }
    });
});
$(document).on('click', '#sendSMSProps', function() {
    $.app.blockScreen(true);
    
    var sms_phone = $('input[name="sms_phone_number"]').val();
    
    $.post($.app.url + '/ajax/debtors/sendsmstodebtor', {phone: sms_phone, sms_type: 'props', debtor_id_1c: $('input[name="debtor_id_1c"]').val(), debt_group_id: "{{$data[0]['d_debt_group_id']}}"}).done(function (data) {
        $.app.blockScreen(false);
        $('#debtorSMS').modal('hide');
        if (data == '-1') {
            $.app.openErrorModal('Ошибка', 'Превышен лимит отправки SMS');
        } else {
            $.app.ajaxResult(data);
            setTimeout(function () {
                window.location.reload();
            }, 2000);
        }
    });
});
$(document).on('click', '#getMessengerText', function() {
    $(this).prop('disabled', true);
    $(this).text('Формирование ссылки...');
    
    if ($('#enableThirdPeople').is(':checked')) {
        var sms_phone = $('#phoneThirdPeople').val();
    } else {
        var sms_phone = $('input[name="sms_phone_number"]').val();
    }
    
    var amount = 0;
    if ($('#paymentLinkSumType1').is(':checked')) {
        amount = $('#paymentLinkSumFull').val();
    }
    if ($('#paymentLinkSumType2').is(':checked')) {
        amount = $('#paymentLinkSum').val();
    }
    
    $.post($.app.url + '/ajax/debtors/sendsmstodebtor', {phone: sms_phone, sms_type: 'msg', amount: amount, debtor_id_1c: $('input[name="debtor_id_1c"]').val(), debt_group_id: "{{$data[0]['d_debt_group_id']}}"}).done(function (data) {
        if (data == '-1') {
            $.app.openErrorModal('Ошибка', 'Превышен лимит отправки SMS');
        } else {
            $('#textForMessengerBlock').show();
            $('#textForMessenger').text(data);
        }
    });
});
$(document).on('click', '.notice_personal', function(){
    $('.notice_personal').hide();
});
$(document).on('click', '#sendNoticePersonal', function () {
    $('#closeNoticePersonal').click();
    if ($('select[name="doc_id"]').val() == '141') {
        window.open('{{url('debtors/debtorcard/createPdf/'.$contractforms['notice_personal'].'/'.$debtor->id.'/')}}' + '/' + $('input[name="date_demand"]').val() + '/' + $("input[name='address_type']").val());
    }
    if ($('select[name="doc_id"]').val() == '144') {
        window.open('{{url('debtors/debtorcard/createPdf/'.$contractforms['requirement_personal'].'/'.$debtor->id.'/')}}' + '/' + $('input[name="date_demand"]').val() + '/' + $("input[name='address_type']").val());
    }
    if ($('select[name="doc_id"]').val() == '145') {
        window.open('{{url('debtors/debtorcard/createPdf/'.$contractforms['requirement_personal_big_money'].'/'.$debtor->id.'/')}}' + '/' + $('input[name="date_demand"]').val() + '/' + $("input[name='address_type']").val());
    }
    if ($('select[name="doc_id"]').val() == '148') {
        window.open('{{url('debtors/debtorcard/createPdf/'.$contractforms['trebovanie_personal_cc_60'].'/'.$debtor->id.'/')}}' + '/' + $('input[name="date_demand"]').val() + '/' + $("input[name='address_type']").val());
    }
    if ($('select[name="doc_id"]').val() == '149') {
        window.open('{{url('debtors/debtorcard/createPdf/'.$contractforms['trebovanie_personal_cc'].'/'.$debtor->id.'/')}}' + '/' + $('input[name="date_demand"]').val() + '/' + $("input[name='address_type']").val());
    }
});
$(document).on('click', '#photoLoad', function () {
    $(this).attr('disabled', true);
    $(this).val('Загрузка фото...');
    var claim_id = $(this).data('claim');
    $.post($.app.url + '/ajax/debtors/loadphoto/' + claim_id).done(function (data) {
        $('.photo-gallery').html(data);
        $.photosCtrl.init();
    });
});
$(document).on('click', '.printaddr', function () {
    $("input[name='address_type']").val($(this).data('typeaddr'));
});
$(document).on('change keyup paste', '#recommend_text_save, #recommend_text_edit', function() {
    if ($(this).val().length > 0) {
        $('#saveDebtorRecommend').removeAttr('disabled');
        $('#editDebtorRecommend').removeAttr('disabled');
    } else {
        $('#saveDebtorRecommend').attr('disabled', true);
        $('#editDebtorRecommend').attr('disabled', true);
    }
});
$(document).on('click', '#saveDebtorRecommend, #editDebtorRecommend', function() {
    $(this).attr('disabled', true);
    var cAction = $(this).data('action');
    $.post($.app.url + '/ajax/debtors/changeRecommend', {action: cAction, debtor_id: $('input[name="debtor_id"]').val(), text: $('#recommend_text_' + cAction).val()}).done(function () {
        window.location.reload();
    });
});

$(document).on('click', '.recommend-ctrl', function() {
    var cAction = $(this).data('action');
    var alert_txt = '';
    if (cAction == 'remove') {
        alert_txt = 'удалить рекомендацию?';
    }
    if (cAction == 'complete') {
        alert_txt = 'завершить работу по рекомендации?';
    }
    
    if (confirm('Вы действительно хотите ' + alert_txt)) {
        $.post($.app.url + '/ajax/debtors/changeRecommend', {action: cAction, debtor_id: $('input[name="debtor_id"]').val(), text: $('#recommend_text').val()}).done(function () {
            window.location.reload();
        });
    }
});
</script>
@stop
