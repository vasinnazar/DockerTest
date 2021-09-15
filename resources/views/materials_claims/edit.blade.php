@extends('app')
@section('title') Редактирование заявления на материалы @stop
@section('content')
{!! Form::model($item,['action'=>'MaterialsClaimsController@updateItem','id'=>'materialsClaimEditForm']) !!}
{!! Form::hidden('id') !!}
{!! Form::hidden('user_id') !!}
{!! Form::hidden('data') !!}
{!! Form::hidden('subdivision_id') !!}
{!! Form::hidden('id_1c') !!}
<?php use Carbon\Carbon; ?>
<div class="row">
    <div class="col-xs-2">
        <label>Дата исполнения</label>
        <input name="claim_date" type="date" value="{{with(new Carbon($item->claim_date))->format('Y-m-d')}}" class="form-control" />
    </div>
    <div class="col-xs-2">
        <label>Статус</label>
        <select name="status" class="form-control">
            <option value="0" @if($item->status==0) selected @endif>Не выполнен</option>
            <option value="1" @if($item->status==1) selected @endif>Выполнен</option>
        </select>
    </div>
    <div class="col-xs-2">
        <label>СФП старые</label>
        <input name="sfp_old" type="number" value="{{$item->sfp_old}}" class="form-control" />
    </div>
    <div class="col-xs-2">
        <label>СФП новые</label>
        <input name="sfp_new" type="number" value="{{$item->sfp_new}}" class="form-control" />
    </div>
    <div class="col-xs-2">
        <label>Заявка на карты СФП</label>
        <input name="sfp_claim" type="number" value="{{$item->sfp_claim}}" class="form-control" />
    </div>
<!--    <div class="col-xs-12">
        <label>Комментарий</label>
        <textarea name="comment" class="form-control">{{$item->comment}}</textarea>
        <br>
    </div>-->
</div>
<div class="row">
    <div class="col-xs-12">
        <br>
        <table class="table-bordered table" id="materialsClaimTable">
            <thead>
                <tr>
                    <th>Материал</th>
                    <th>Количество</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input name="material" class="form-control input-sm"/></td>
                    <td><input name="amount" class="form-control input-sm"/></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-default" name="add"><span class="glyphicon glyphicon-plus"></span></button>
                            <button class="btn btn-default" name="remove"><span class="glyphicon glyphicon-trash"></span></button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <button class="btn btn-primary pull-right" type="submit">Сохранить</button>
    </div>
</div>
{!! Form::close() !!}
@stop
@section('scripts')
<script src="{{asset('js/common/DataGridController.js')}}">
</script>
<script>
    (function ($) {
        var dataGrid = new DataGridController($('#materialsClaimEditForm [name="data"]').val(), {
            table: $("#materialsClaimTable"),
            form:$('#materialsClaimEditForm'),
            dataField:$('#materialsClaimEditForm [name="data"]')
        });
    })(jQuery);
</script>
@stop