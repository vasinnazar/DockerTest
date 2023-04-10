@extends('app')
@section('title') Просроченные должники @stop
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
@if ($isChief)
<div class="row">
    <div class="col-xs-12">
        <table class='table table-borderless' id='forgottenDebtorFilter'>
            <tr>
                <td class="form-inline" style='text-align: left;'>
                    {!!Form::open()!!}
                    Ответственный: <input name='users@name' type='text' class='form-control autocomplete' data-hidden-value-field='search_field_users@id_1c' style='width: 350px;'/>
                    <input id='search_field_users@id_1c' name='search_field_users@id_1c' type='hidden' />
                    {!!Form::button('Найти',['class'=>'btn btn-primary','type'=>'button', 'id'=>'forgottenDebtorFilterButton'])!!}
                    &nbsp;<button class="btn btn-primary" onclick="$.debtorsCtrl.forgottenToExcel(); return false;">Excel</button>
                    {!!Form::close()!!}
                </td>
            </tr>
        </table>
    </div>
</div>
@endif
<div class="row">
    <div class="col-xs-12">
        <table class="table table-condensed table-striped table-bordered" id="debtorforgottenTable">
            <thead>
                <tr>
                    <th>

                    </th>
                    <th>Дата закрепления</th>
                    <th>ФИО</th>
                    <th>Ответственный</th>
                    <th>Стр. подр.</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@stop
@section('scripts')
<script src="{{asset('js/debtors/debtorsController.js?6')}}"></script>
<script>
$(document).ready(function () {
    $.debtorsCtrl.init();
    $.debtorsCtrl.initDebtorForgottenTable();
    // $.debtorsCtrl.changeForgottenDebtorFilter();
});
</script>
@stop
