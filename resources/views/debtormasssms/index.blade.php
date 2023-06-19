@extends('app')
@section('title')
    Массовая рассылка
@stop
@section('css')
    <style>
        .debtors-frame {
            height: 250px;
            overflow-y: scroll;
        }

        .debtors-table-frame {
            border: 1px solid #ccc;
            padding: 10px;
        }
    </style>
@stop
@section('content')
    <div class='row'>
        <div class="col-xs-12">
            <button id="smsFilter" type="button" class="btn btn-default" data-toggle="modal"
                    data-target="#debtorMassSmsFilterModal"><span class='glyphicon glyphicon-search'></span> Фильтр
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
                        <input type="button" id="sendMassEmail" class="btn btn-primary" value="Отправить" disabled/>
                    </td>
                    <td style="padding-left: 20px; padding-right: 17px;">
                        <button id="smsTpls" class="btn btn-primary" data-toggle="modal" data-target="#debtorMassSMS">
                            Шаблон SMS
                        </button>
                    </td>
                    <td style="padding-left: 20px; padding-right: 17px;">
                        <input type="button" id="sendMassSms" class="btn btn-primary" value="Отправить" disabled/>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="row" id="smsInfoBlock" style="margin-top: 15px; display: none;">
        <div class="col-xs-12">
            <div id="smsInfo" class="alert alert-info" role="alert">Отправка СМС начата. Ожидайте...</div>
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
    <div class="modal fade" id="debtorMassSmsFilterModal" tabindex="-1" role="dialog"
         aria-labelledby="debtorTransferFilterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="debtorTransferFilterModalLabel">Фильтр должников</h4>
                </div>
                <form id="massSmsFormFilter">
                    <input type="hidden" name="sms_tpl_id">
                    <input type="hidden" name="sms_tpl_date" value="{{ date('d.m.Y', time()) }}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-xs-12">
                                <table class='table table-borderless' id='debtorMassSmsFilter'>
                                    @foreach($debtorTransferFilterFields as $dtff)
                                        <tr>
                                            <td>{{$dtff['label']}}</td>
                                            <td>
                                                <select class='form-control'
                                                        name='{{($dtff['name'] == 'users@login') ? 'search_field_users@id_condition' : 'search_field_'.$dtff['name'].'_condition'}}'>
                                                    <option value='='>=</option>
                                                    <option value="<"><</option>
                                                    <option value="<="><=</option>
                                                    <option value=">">></option>
                                                    <option value=">=">>=</option>
                                                    <option value="<>">не равно</option>
                                                    <option value="like" {{($dtff['name'] == 'passports@fact_address_region' || $dtff['name'] == 'passports@fact_address_district') ? 'selected' : ''}}>
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
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        {!!Form::button('Очистить фильтр',['class'=>'btn btn-default','type'=>'button', 'id'=>'debtorMassSmsClearFilterBtn'])!!}
                        {!!Form::button('Найти',['class'=>'btn btn-primary','type'=>'button', 'id'=>'debtorMassSmsFilterButton'])!!}
                    </div>
                </form>
            </div>
        </div>
    </div>
    @include('elements.debtorsMassSmsModal')
@stop
@section('scripts')
    <script src="{{asset('js/debtors/debtorsController.js?1')}}"></script>
    <script>
        $(document).ready(function () {
            $.debtorsCtrl.init();
            $.debtorsCtrl.initDebtorMassSmsTable();
            $.debtorsCtrl.changeDebtorMassSmsFilter();

            $(document).on('change', '#formSendSMS input[type="radio"]', function () {
                $('input[name="sms_tpl_id"]').val($(this).val());
            });

            $(document).on('change', '#formSendSMS', function () {
                if ($('#old_user_id').val() != '' && $('input[name="sms_tpl_id"]').val() != '') {
                    $('#sendMassSms').prop('disabled', false);
                } else {
                    $('#sendMassSms').prop('disabled', true);
                }
            });
            $(document).on('change', 'input[name="sms_date"]', function () {
                var arVal = $(this).val().split('-');
                $('.sms_text').each(function () {
                    $(this).text($(this).text().replace(/\d{1,2}\.\d{1,2}\.\d{4}/, arVal[2] + '.' + arVal[1] + '.' + arVal[0]));
                });
                $('input[name="sms_tpl_date"]').val(arVal[2] + '.' + arVal[1] + '.' + arVal[0]);
            });
            $(document).on('click', '#sendMassSms', function () {
                $(this).prop('disabled', true);
                $('#smsFilter').prop('disabled', true);
                $('#smsTpls').prop('disabled', true);

                $('#smsInfoBlock').show();

                console.log($('#debtormasssmsTable').dataTable().api().rows().ids().toArray());
                $.ajax({
                    type: "POST",
                    url: "/ajax/debtors/masssms/send",
                    data: {
                        smsId : $('input[name="sms_tpl_id"]').val(),
                        smsDate : $('input[name="sms_tpl_date"]').val(),
                        responsibleUserId : $('#old_user_id').val(),
                        debtorsIds : $('#debtormasssmsTable').dataTable().api().rows().ids().toArray()
                    },
                    dataType: "json",
                    success: function (data) {
                        if (data.error == 'success') {
                            $('#smsInfo').attr('class', 'alert alert-success');
                            $('#smsInfo').text('СМС отправлены. Кол-во: ' + data.cnt);
                        } else {

                            $('#smsInfo').attr('class', 'alert alert-danger');
                            $('#smsInfo').text('Ошибка: ' + data);
                        }
                    },
                    error : function () {
                        $('#smsInfo').attr('class', 'alert alert-danger');
                        $('#smsInfo').text('Ошибка: Не удалось отправить смс');
                    }
                });
            });
        });
    </script>
@stop
