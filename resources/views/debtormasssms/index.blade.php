@extends('app')
@section('title')
    Массовая рассылка
@stop
@section('content')
    <div class='row'>
        <div class="col-xs-12">
            <button id="massFilter" type="button" class="btn btn-default" data-toggle="modal"
                    data-target="#debtorMassFilterModal"><span class='glyphicon glyphicon-search'></span> Фильтр
            </button>
        </div>
    </div>

    <div class='row'>
        <div class="col-xs-12">
            <table id="debtorTransferAction" class="pull-right">
                <tr>
                    <td style="padding-left: 20px; padding-right: 17px;">
                        <button id="emailTpls" class="btn btn-primary" data-toggle="modal" data-target="#debtorMassEmail">
                            Шаблон Email
                        </button>
                    </td>
                    <td style="padding-left: 20px; padding-right: 17px;">
                        <button id="smsTpls" class="btn btn-primary" data-toggle="modal" data-target="#debtorMassSMS">
                            Шаблон SMS
                        </button>
                    </td>
                    <td style="padding-left: 20px; padding-right: 17px;">
                        <input type="button" id="sendMass" class="btn btn-primary" value="Отправить" disabled/>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="row" id="sendInfoBlock" style="margin-top: 15px; display: none;">
        <div class="col-xs-12">
            <div id="sendInfo" class="alert alert-info" role="alert">Отправка начата. Ожидайте...</div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <table class="table table-condensed table-striped table-bordered" id="debtormasssmsTable">
                <thead>
                <tr>
                    <th>

                    </th>
                    <th>Должник</th>
                    <th>Сумма</th>
                    <th>База</th>
                    <th>Город</th>
                    <th>Дата</th>
                    <th>Дней просрочки</th>
                    <th>Ответственный</th>
                    <th>Группа долга</th>
                    <th>Подразделение</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
    <div class="modal fade" id="debtorMassFilterModal" tabindex="-1" role="dialog"
         aria-labelledby="debtorTransferFilterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="debtorTransferFilterModalLabel">Фильтр должников</h4>
                </div>
                <form id="debtorMassSendFilter">
                    <input type="hidden" name="is_sms" value="">
                    <input type="hidden" name="template_id">
                    <input type="hidden" name="date_template_sms" value="{{ date('d.m.Y', time()) }}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-xs-12">
                                <table class='table table-borderless'>
                                    @foreach($debtorTransferFilterFields as $dtff)
                                        <tr>
                                            <td>{{$dtff['label']}}</td>
                                            <td>
                                                <select class='form-control'
                                                        name='{{($dtff['name'] == 'users@login')
                                                        ?
                                                        'search_field_users@id_condition'
                                                        :
                                                        'search_field_'.$dtff['name'].'_condition'}}'>
                                                    <option value='='>=</option>
                                                    <option value="<"><</option>
                                                    <option value="<="><=</option>
                                                    <option value=">">></option>
                                                    <option value=">=">>=</option>
                                                    <option value="<>">не равно</option>
                                                    <option value="like" {{
                                                            ($dtff['name'] == 'passports@fact_address_region' ||
                                                            $dtff['name'] == 'passports@fact_address_district')
                                                            ?
                                                            'selected'
                                                            : ''
                                                            }}>
                                                        подобно
                                                    </option>
                                                </select>
                                            </td>
                                            <td>
                                                @if(array_key_exists('hidden_value_field',$dtff))
                                                    <input name='{{$dtff['name']}}' type='{{$dtff['input_type']}}'
                                                           class='form-control autocomplete'
                                                           data-hidden-value-field='{{'search_field_'.$dtff['hidden_value_field']}}'/>
                                                    <input id="{{$dtff['field_id']}}"
                                                           name='{{'search_field_'.$dtff['hidden_value_field']}}'
                                                           type='hidden'/>
                                                @else
                                                    <input name='{{'search_field_'.$dtff['name']}}'
                                                           type='{{$dtff['input_type']}}' class='form-control'/>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td>Дата крепления</td>
                                        <td></td>
                                        <td><input name="fixation_date" type='date' class='form-control'/></td>
                                    </tr>
                                    <tr>
                                        <td>Дней просрочки, от</td>
                                        <td></td>
                                        <td><input name="overdue_from" type='text' class='form-control'/></td>
                                    </tr>
                                    <tr>
                                        <td>Дней просрочки, до</td>
                                        <td></td>
                                        <td><input name="overdue_till" type='text' class='form-control'/></td>
                                    </tr>
                                    <tr>
                                        <td>Сумма задолженности, от</td>
                                        <td></td>
                                        <td><input name="sum_from" id="sum_from" type="text" class="form-control" placeholder="0000.00"/></td>
                                    </tr>
                                    <tr>
                                        <td>Сумма задолженности, до</td>
                                        <td></td>
                                        <td><input name="sum_to" id="sum_to" type="text" class="form-control" placeholder="0000.00"/></td>
                                    </tr>
                                        <tr>
                                            <td>Наличие email</td>
                                            <td></td>
                                            <td><select name="has_email" class="form-control">
                                                    <option value="">Все</option>
                                                    @foreach($emailFilterFields as $fieldKey => $fieldName)
                                                        <option value="{{$fieldKey}}">{{$fieldName}}</option>
                                                    @endforeach
                                                </select></td>
                                        </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="pull-left" style="text-align: left;">
                            <span><input type='checkbox' name='search_field_debtors@kratnost' value='1'>&nbsp;Кратность</span>
                        </div>
                        {!!Form::button('Очистить фильтр',['class'=>'btn btn-default','type'=>'button', 'id'=>'debtorMassSmsClearFilterBtn'])!!}
                        {!!Form::button('Найти',['class'=>'btn btn-primary','type'=>'button', 'id'=>'debtorMassSendFilterButton'])!!}
                    </div>
                </form>
            </div>
        </div>
    </div>
    @include('elements.debtorsMassEmailModal')
    @include('elements.debtorsMassSmsModal')
@stop
@section('scripts')
    <script src="{{asset('js/debtors/debtorsController.js?1')}}"></script>
    <script src="{{asset('js/debtors/MassSend.js?1')}}"></script>
@stop
