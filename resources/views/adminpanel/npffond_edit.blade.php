@extends('adminpanel')
@section('title') Редактирование фонда @stop
@section('subcontent')

{!! Form::model($item,['route'=>'adminpanel.npffonds.update','id'=>'npfFondEditForm']) !!}
{!! Form::hidden('id') !!}
<div class="row">
    <div class="form-group col-xs-12 col-md-12">
        <label>Наименование фонда</label>
        {!! Form::text('name',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-12">
        <label>Форма договора</label>
        {!! Form::select('contract_form_id',$contract_forms,null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-12">
        <label>Форма заявления перехода из НПФ</label>
        {!! Form::select('claim_from_npf_id',$contract_forms,null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-12">
        <label>Форма заявления перехода из ПФР</label>
        {!! Form::select('claim_from_pfr_id',$contract_forms,null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-12">
        <label>Согласие на обработку ПД</label>
        {!! Form::select('pd_agreement_id',$contract_forms,null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-12">
        <label>Анкета</label>
        {!! Form::select('anketa_id',$contract_forms,null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-12">
        <label>ID 1C</label>
        {!! Form::text('id_1c',null,['class'=>'form-control']) !!}
    </div>
</div>
{!! Form::button('Сохранить',['class'=>'btn btn-primary pull-right','type'=>'submit']) !!}
{!! Form::close() !!}

@stop
@section('scripts')
@stop