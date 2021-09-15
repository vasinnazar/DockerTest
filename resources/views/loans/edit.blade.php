@extends('app')
@section('title')Редактирование займа@stop
@section('content')
{!! Form::model($loan,['action'=>'LoanController@update','id'=>'loanEditForm']) !!}
{!! Form::hidden('id')!!}
{!! Form::hidden('claim_id',$loan->claim_id)!!}
{!! Form::hidden('last_card_number',((!is_null($lastCard))?$lastCard->card_number:null),['disabled'=>'disabled'])!!}
{!! Form::hidden('last_secret_word',((!is_null($lastCard))?$lastCard->secret_word:null),['disabled'=>'disabled'])!!}

<div class="form-group">
    <label class="control-label">Сумма</label>
    <input name="money" class="form-control input-sm" min="1000" max="{{$loan->claim->summa}}" type="number" value="{{$loan->money}}"/>
</div>
<div class="form-group">
    <label class="control-label">Спец процент</label>
    <input name="special_percent" class="form-control input-sm" type="text" value="{{$loan->special_percent}}"/>
</div>
<div class="form-group">
    <label class="control-label">Срок</label> 
    <input name="time" class="form-control input-sm" min="1" type="number" value="{{$loan->time}}"/>
</div>
<div class="form-group">
    <label class="control-label">Вид займа</label> 
    {!! Form::select('loantype_id',$loantypes,null,['class'=>'form-control input-sm']) !!}
</div>
<div class="form-group">
    <label class="control-label">Номер договора в 1С</label> 
    {!! Form::text('id_1c',null,['class'=>'form-control input-sm']) !!}
</div>
<div class="form-group">
    {!! Form::hidden('promocode_id',null,['class'=>'form-control input-sm','disabled'=>'disabled']) !!}
    <div class="form-inline">
        @if(is_null($loan->promocode_id))
        {!! Form::text('promocode_number',null,['class'=>'form-control input-sm','disabled'=>'disabled','style'=>'display:none']) !!}
        <div class="form-group">
            {!! Form::checkbox('promocode',1,null,['class'=>'checkbox','id'=>'getPromocodeCheckbox']) !!}
            <label for="getPromocodeCheckbox" class="control-label">Получить промокод</label>
        </div>
        <!--<button id="createPromocodeBtn" class="btn btn-default">Получить промокод</button>-->
        @else
        {!! Form::text('promocode_number',$loan->promocode->number,['class'=>'form-control input-sm','disabled'=>'disabled']) !!}
        @endif
    </div>
</div>
<div class="form-group">
    <?php echo Form::checkbox('uki', 1, null, ['class' => 'checkbox', 'id' => 'ukiCb']); ?>
    <label for="ukiCb" >УКИ</label>
</div>

<div class="form-group">
    <?php echo Form::checkbox('in_cash', 1, null, ['class' => 'checkbox', 'id' => 'createLoanModalInCash']); ?>
    <label for="createLoanModalInCash" >Наличными</label>
</div>
<div class="<?php echo ($loan->in_cash) ? 'hidden' : ''; ?>" id="cardInputHolder">
    <div class="form-group">
        <button id="getLastCardBtn" class="btn btn-default">Последняя карта</button>
        <button id="addCardBtn" class="btn btn-default">Оформить карту</button>
    </div>
    <div class="form-group">
        <label class="control-label">Номер карты</label> 
        {!! Form::text('card_number',(!is_null($loan->card))?$loan->card->card_number:'',['class'=>'form-control input-sm']) !!}
    </div>
    <div class="form-group">
        <label class="control-label">Секретное слово</label> 
        {!! Form::text('secret_word',(!is_null($loan->card))?$loan->card->secret_word:'',['class'=>'form-control input-sm']) !!}
    </div>
</div>
<br>
<button class="btn btn-primary pull-right" type="submit">Сохранить</button>
{!! Form::close() !!}

@stop
@section('scripts')
<script src="{{ URL::asset('js/loan/loanController.js') }}"></script>
<script>
(function () {
    $.loanCtrl.init();
})(jQuery);
</script>
@stop