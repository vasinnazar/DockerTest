@extends('reports.reports')
@section('title') Редактирование авансового отчета @stop
@section('css')
<style>
    .actions-column{
        min-width: 100px;
    }
</style>
@stop
@section('subcontent')
{!! Form::model($advRep,['url'=>'/reports/advancereports/update','id'=>'advRepForm']) !!}
{!! Form::hidden('id') !!}
{!! Form::hidden('data') !!}
<?php $advRepData = json_decode($advRep->data); ?>
{!! Form::hidden('other_data', (isset($advRepData->other))?json_encode($advRepData->other):'') !!}
{!! Form::hidden('payment_data', (isset($advRepData->payment))?json_encode($advRepData->payment):'') !!}
{!! Form::hidden('advance_data', (isset($advRepData->advance))?json_encode($advRepData->advance):'') !!}
{!! Form::hidden('goods_data', (isset($advRepData->goods))?json_encode($advRepData->goods):'') !!}
@if(Auth::user()->isAdmin())
<div class='row'>
    <div class="col-xs-12 col-sm-3">
        <label>Подразделение</label>
        {!! Form::hidden('subdivision_id') !!}
        <input type='text' name='subdivision_id_autocomplete' class='form-control' data-autocomplete='subdivisions' placeholder="@if(!is_null($advRep->subdivision)) {{$advRep->subdivision->name}} @endif" />
    </div>
    <div class="col-xs-12 col-sm-3">
        <label>Специалист</label>
        {!! Form::hidden('user_id') !!}
        <input type='text' name='user_id_autocomplete' class='form-control' data-autocomplete='users' placeholder="@if(!is_null($advRep->user)) {{$advRep->user->name}} @endif" />
    </div>
    <div class="col-xs-12 col-sm-3">
        <label>Дата создания</label>
        <input class="form-control" type="text" name="created_at" value="{{$advRep->created_at}}"/>
    </div>
    <div class="col-xs-12 col-sm-3">
        <label>ID 1C</label>
        <div class="input-group">
            <input class="form-control" type="text" name="id_1c" value="{{$advRep->id_1c}}"/>
            <span class="input-group-btn">
                <a href="{{url('reports/advancereports/upload?advance_id_1c='.$advRep->id_1c)}}" class="btn btn-default"><span class="glyphicon glyphicon-refresh"></span></a>
            </span>
        </div>
    </div>
</div>
@endif
<div class="row">
    <div class="col-xs-12 col-sm-6">
        <label>Контрагент</label>
        {!! Form::hidden('customer_id') !!}
        <input type='text' name='customer_id_autocomplete' class='form-control' data-autocomplete='customers' placeholder="@if(!is_null($advRep->customer)) {{$advRep->customer->id_1c}} @endif" />
    </div>
    <div class="col-xs-12 col-sm-6">
        <label>Склад</label>
        {!! Form::hidden('subdivision_store_id') !!}
        <input type='text' name='subdivision_store_id_autocomplete' class='form-control' data-autocomplete='subdivision_stores' placeholder="@if(!is_null($advRep->subdivision_store)) {{$advRep->subdivision_store->id_1c}} @endif" />
    </div>
</div>
<br>
{!! Form::close() !!}
<div>

    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active"><a href="#advance" aria-controls="home" role="tab" data-toggle="tab">Авансы</a></li>
        <li role="presentation"><a href="#goods" aria-controls="profile" role="tab" data-toggle="tab">Товары</a></li>
        <li role="presentation"><a href="#payment" aria-controls="messages" role="tab" data-toggle="tab">Оплата</a></li>
        <li role="presentation"><a href="#other" aria-controls="settings" role="tab" data-toggle="tab">Прочее</a></li>
    </ul>

    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="advance">
            <table class="table-borderless table" id="advanceTable">
                <thead>
                    <tr>
                        <th>Документ</th>
                        <th>Сумма</th>
                        <th>Выдано</th>
                        <th>Израсходовано</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name='order_id' class='form-control input-sm'>
                                @foreach($orders as $order)
                                <option value="{{$order->id}}" data-money='{{$order->money}}'>Расходно кассовый ордер {{$order->number}} от {{$order->created_at->format('d.m.Y H:i:s')}}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input name="advance_money" class="form-control input-sm money"/></td>
                        <td><input name="advance_issue" class="form-control input-sm money"/></td>
                        <td><input name="advance_spent" class="form-control input-sm money"/></td>
                        <td class='actions-column'>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-default" name="add"><span class="glyphicon glyphicon-plus"></span></button>
                                <button class="btn btn-default" name="remove"><span class="glyphicon glyphicon-trash"></span></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div role="tabpanel" class="tab-pane" id="goods">
            <br>
            <table class="table-borderless table" id="goodsTable">
                <thead>
                    <tr>
                        <th>Номенклатура</th>
                        <th>Количество</th>
                        <th>Цена</th>
                        <th>Сумма</th>
                        <th>Наименование документа (кассовый чек, квитанция, БСО и т.д.)</th>
                        <th>Номер документа (кассовый чек, квитанция, БСО и т.д.)</th>
                        <th>Дата документа (кассовый чек и т.д.)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <input type='hidden' name='nomenclature_id' />
                            <input type='text' name='nomenclature_id_autocomplete' class='form-control input-sm' data-type="{{\App\Nomenclature::TYPE_GOODS}}"/>
                        </td>
                        <td><input type='number' name="amount" class="form-control input-sm" min='0'/></td>
                        <td><input name="price" class="form-control input-sm" min='0'/></td>
                        <td><input name="total_money" class="form-control input-sm" class='money' min='0'/></td>
                        <td><input name="doc_type" class="form-control input-sm"/></td>
                        <td><input name="doc_number" class="form-control input-sm"/></td>
                        <td><input name="doc_date" class="form-control input-sm" type='date'/></td>
                        <td class='actions-column'>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-default" name="add"><span class="glyphicon glyphicon-plus"></span></button>
                                <button class="btn btn-default" name="remove"><span class="glyphicon glyphicon-trash"></span></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div role="tabpanel" class="tab-pane" id="payment"></div>
        <div role="tabpanel" class="tab-pane" id="other">
            <br>
            <table class="table-borderless table" id="otherTable">
                <thead>
                    <tr>
                        <th>Наименование документа (кассовый чек, квитанция, БСО и т.д.)</th>
                        <th>Номер документа (кассовый чек, квитанция, БСО и т.д.)</th>
                        <th>Дата документа (кассовый чек и т.д.)</th>
                        <th>Номенклатура</th>
                        <th>Сумма</th>
                        <th>Субконто 1 (БУ) выбираем из номенклатуры, содержащейся в ПО 1С</th>
                        <!--<th>Склад</th>-->
                        <!--<th>Физ. лицо</th>-->
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input name="doc_type" class="form-control input-sm"/></td>
                        <td><input name="doc_number" class="form-control input-sm"/></td>
                        <td><input name="doc_date" class="form-control input-sm" type='date'/></td>
                        <td>
                            <input type='hidden' name='nomenclature_id' />
                            <input type='text' name='nomenclature_id_autocomplete' class='form-control input-sm' data-type="{{\App\Nomenclature::TYPE_OTHER}}" />
                        </td>
                        <td><input name="total_money" class="form-control input-sm" class='money'/></td>
                        <td>
                            <input type='hidden' name='expenditure_id' />
                            <input type='text' name='expenditure_id_autocomplete' class='form-control input-sm' data-autocomplete='expenditures' />
                        </td>
                        <!--<td><input type='hidden' name='subdivision_id' value='{{Auth::user()->subdivision_id}}'/> <input name="subdivision_name" class="form-control input-sm" disabled value='{{Auth::user()->subdivision->name}}'/></td>-->
                        <!--<td><input type='hidden' name='user_id' value='{{Auth::user()->user_id}}'/> <input name="user_name" class="form-control input-sm" disabled value='{{Auth::user()->name}}'/></td>-->
                        <td class='actions-column'>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-default" name="add"><span class="glyphicon glyphicon-plus"></span></button>
                                <button class="btn btn-default" name="remove"><span class="glyphicon glyphicon-trash"></span></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <br>
    <button class='btn btn-default pull-right' id='advRepFormSubmitBtn'>Сохранить</button>
</div>
@stop
@section('scripts')
<script src="{{asset('js/common/TableController.js')}}"></script>
<script src="{{asset('js/reports/advanceReportController.js')}}"></script>
<script>
(function () {
    $.advRepCtrl.initEditor();
})(jQuery);
</script>
@stop