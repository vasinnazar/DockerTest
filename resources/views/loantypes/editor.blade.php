@extends('adminpanel')
@section('title') Редактор вида займа @stop

@section('subcontent')
{!! Form::model($loanType,['route'=>'loantypes.update'])!!}
{!! Form::hidden('id') !!}
<div class="row">
    <div class="form-group col-xs-7">
        <label>Наименование</label>
        {!! Form::text('name',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-3">
        <label for="status">Статус</label>
        {!! Form::select('status', [
        \App\LoanType::STATUS_ACTIVE=>'Активный',
        \App\LoanType::STATUS_CLOSED=>'Закрытый',
        \App\LoanType::STATUS_CREDITSTORY1=>'Исправление КИ 1',
        \App\LoanType::STATUS_CREDITSTORY2=>'Исправление КИ 2',
        \App\LoanType::STATUS_CREDITSTORY3=>'Исправление КИ 3',
        ],null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-2">
        <label for="id_1c">Номер в 1С</label>
        {!! Form::text('id_1c',null,['class'=>'form-control'])!!}
    </div>
    <div class="form-group col-xs-2">
        <label>Сумма</label>
        {!! Form::text('money',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-2">
        <label>Срок</label>
        {!! Form::text('time',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-2">
        <label>Проценты</label>
        {!! Form::text('percent',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-3">
        <label>Дата начала</label>
        {!! Form::text('start_date',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-3">
        <label>Дата завершения</label>
        {!! Form::text('end_date',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-6">
        <label>Форма договора наличными</label>
        {!! Form::select('contract_form_id',$contract_forms,null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-6">
        <label>Форма договора на карту</label>
        {!! Form::select('card_contract_form_id',$contract_forms,null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-6">
        <label>Договор на нал. для пост. клиентов или пенсионеров</label>
        {!! Form::select('perm_contract_form_id',$contract_forms,null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-6">
        <label>Договор на карту для пост. клиентов или пенсионеров</label>
        {!! Form::select('perm_card_contract_form_id',$contract_forms,null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-6">
        <label for="additional_contract_id">Доп. документ для новых клиентов</label>
        {!! Form::select('additional_contract_id',$contract_forms,null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-6">
        <label for="additional_contract_perm_id">Доп. документ для пост. клиентов или пенсионеров</label>
        {!! Form::select('additional_contract_perm_id',$contract_forms,null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-6">
        <label for="additional_contract_id">Доп. документ для новых клиентов на карту</label>
        {!! Form::select('additional_card_contract_id',$contract_forms,null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-6">
        <label for="additional_contract_perm_id">Доп. документ для пост. клиентов или пенсионеров на карту</label>
        {!! Form::select('additional_card_contract_perm_id',$contract_forms,null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-12">
        {!! Form::checkbox('basic', 1) !!}
        <label for="basic">Базовая ставка</label>
    </div>
    <div class="form-group col-xs-12">
        {!! Form::checkbox('show_in_terminal', 1) !!}
        <label for="show_in_terminal">Показывать в терминале</label>
    </div>
    
    <div class="form-group col-xs-12">
        <label for="docs">Требуемые документы</label>
        {!! Form::text('docs',null,['class'=>'form-control'])!!}
    </div>
    <div class="form-group col-xs-3">
        <label for="exp_pc">Просроч. проценты</label>
        {!! Form::text('exp_pc',null,['class'=>'form-control'])!!}
    </div>
    <div class="form-group col-xs-3">
        <label for="exp_pc_perm">Просроч. проценты для пост. клиентов</label>
        {!! Form::text('exp_pc_perm',null,['class'=>'form-control'])!!}
    </div>
    <div class="form-group col-xs-3">
        <label for="fine_pc">Пеня</label>
        {!! Form::text('fine_pc',null,['class'=>'form-control'])!!}
    </div>
    <div class="form-group col-xs-3">
        <label for="fine_pc_perm">Пеня для пост. клиентов</label>
        {!! Form::text('fine_pc_perm',null,['class'=>'form-control'])!!}
    </div>
    <div class="form-group col-xs-3">
        <label for="pc_after_exp">Срочный процент после просрочки для новых клиентов</label>
        {!! Form::text('pc_after_exp',null,['class'=>'form-control'])!!}
    </div>
    <div class="form-group col-xs-3">
        <label for="special_pc">Использовать спец. процент(0|1)</label>
        {!! Form::text('special_pc',null,['class'=>'form-control'])!!}
    </div>
    <div class="form-group col-xs-3">
        <label for="special_pc">Скидка на проценты в терминалах</label>
        {!! Form::text('terminal_promo_discount',null,['class'=>'form-control'])!!}
    </div>
    
    <div class="form-group col-xs-12">
        <?php
        $checked = $loanType->conditions->lists('id')->all();
        foreach ($conditions as $cond) {
            $opts = [];
            if (in_array($cond->id, $checked)) {
                $opts['checked'] = 'checked';
            }
            echo Form::checkbox('condition[]', $cond->id, $opts);
            echo Form::label($cond->name) . '<br>';
        }
        ?>
    </div>    
</div>
{!! Form::button('Сохранить',['class'=>'btn btn-primary pull-right','type'=>'submit']) !!}
{!! Form::close() !!}
@stop