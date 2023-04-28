@extends('app')
@section('title') Передача должников @stop
@section('css')
<style>
    .debtors-frame{
        height: 250px;
        overflow-y:scroll;
    }
    .debtors-table-frame{
        border: 1px solid #ccc;
        padding: 10px;
    }
</style>
@stop
@section('content')
<div class='row'>
    <div class="col-xs-12">
        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#debtorTransferFilterModal"><span class='glyphicon glyphicon-search'></span> Фильтр</button>
        <a href="/debtors/transfer/history" class="pull-right" style="margin-right: 20px;" target="_blank">История передач</a>
    </div>
</div>
<div class='row'>
    <div class="col-xs-12">
        <span class="pull-left">Выбрано: <span id="debtorsCounter">0</span></span>
        <table id="debtorTransferAction" class="pull-right">
            <tr>
                <td style="padding-right: 10px;">Новый пользователь:</td>
                <td>
                    <input type="text" name="users@login" class="form-control autocomplete" data-hidden-value-field="search_field_users@id" style="width: 350px;" />
                    <input id="new_user_id" name="search_field_users@id" type="hidden" />
                </td>
                <td style="padding-left: 20px; padding-right: 10px;">Номер акта БД: </td>
                <td>
                    <input type="text" name="act_number" class="form-control" style="width: 350px;" />
                </td>
                <td style="padding-left: 20px; padding-right: 10px;">База: </td>
                <td>
                    <input class='form-control autocomplete' name='debtors@base' data-hidden-value-field="search_field_debtors@base"/>
                    <input name="search_field_debtors@base" type="hidden">
                </td>
                <td style="padding-left: 20px; padding-right: 17px;">
                    <input type="button" id="printResponsibleUser" class="btn btn-primary" value="Печать" disabled />
                </td>
                <td style="padding-left: 20px; padding-right: 17px;">
                    <input type="button" id="changeResponsibleUser" class="btn btn-primary" value="Изменить" disabled />
                </td>
            </tr>
        </table>
    </div>
</div>
<div class="row">
    <div class="col-xs-12">
        <table class="table table-condensed table-striped table-bordered" id="debtortransferTable">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" id="allDebtorsCheckToggler"/>
                    </th>
                    <th>
                        
                    </th>
                    <th>Должник</th>
                    <th>Сумма</th>
                    <th>База</th>
                    <th>Город</th>
                    <th>Дата</th>
                    <th>Дней просрочки</th>
                    <th>Ответственный</th>
                    <th>Пред. ответственный</th>
                    <th>Группа долга</th>
                    <th>Подразделение</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
<div class="modal fade" id="debtorTransferFilterModal" tabindex="-1" role="dialog" aria-labelledby="debtorTransferFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="debtorTransferFilterModalLabel">Фильтр должников</h4>
            </div>
            {!!Form::open()!!}
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <table class='table table-borderless' id='debtorTransferFilter'>
                            @foreach($debtorTransferFilterFields as $dtff)
                            <tr>
                                <td>{{$dtff['label']}}</td>
                                <td>
                                    <select class='form-control' name='{{($dtff['name'] == 'users@login') ? 'search_field_users@id_condition' : 'search_field_'.$dtff['name'].'_condition'}}'>
                                        <option value='='>=</option>
                                        <option value="<"><</option>
                                        <option value="<="><=</option>
                                        <option value=">">></option>
                                        <option value=">=">>=</option>
                                        <option value="<>">не равно</option>
                                        <option value="like" {{($dtff['name'] == 'passports@fact_address_region' || $dtff['name'] == 'passports@fact_address_district') ? 'selected' : ''}}>подобно</option>
                                    </select>
                                </td>
                                <td>
                                    @if(array_key_exists('hidden_value_field',$dtff))
                                    <input name='{{$dtff['name']}}' type='{{$dtff['input_type']}}' class='form-control autocomplete' data-hidden-value-field='{{'search_field_'.$dtff['hidden_value_field']}}'/>
                                    <input id="{{$dtff['field_id']}}" name='{{'search_field_'.$dtff['hidden_value_field']}}' type='hidden' />
                                    @else
                                    <input name='{{'search_field_'.$dtff['name']}}' type='{{$dtff['input_type']}}' class='form-control'/>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
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
                <div class="pull-left" style="text-align: left;">
                <span><input type="checkbox" name="search_field_debtors@is_lead" value="1">&nbsp;Онлайн</span>
                <br>
                <span><input type="checkbox" name="search_field_debtors@is_bigmoney" value="1">&nbsp;Большие деньги</span>
                <br>
                <span><input type="checkbox" name="search_field_debtors@is_pledge" value="1">&nbsp;Залоговые займы</span>
                <br>
                <span><input type="checkbox" name="search_field_debtors@is_pos" value="1">&nbsp;Товарные займы</span>
                </div>
                {!!Form::button('Очистить фильтр',['class'=>'btn btn-default','type'=>'button', 'id'=>'debtorTransferClearFilterBtn'])!!}
                {!!Form::button('Найти',['class'=>'btn btn-primary','type'=>'button', 'id'=>'debtorTransferFilterButton'])!!}
            </div>
            {!!Form::close()!!}
        </div>
    </div>
</div>
@stop
@section('scripts')
<script src="{{asset('js/debtors/debtorsController.js?1')}}"></script>
<script>
$(document).ready(function () {
    $.debtorsCtrl.init();
    $.debtorsCtrl.initDebtorTransferTable();
    $.debtorsCtrl.changeDebtorTransferFilter();
});
</script>
@stop
