@extends('app')
@section('title') Редактирование ордера @stop
@section('content')
{!! Form::model($order,['action'=>'OrdersController@updateOrder','id'=>'orderEditForm']) !!}
{!! Form::hidden('id')!!}
{!! Form::hidden('customer_id')!!}
<div class="row">
    <div class="form-group col-xs-12 col-md-2">
        <label class="control-label">Тип</label>
        <select name="type">
        @foreach($order_types as $type)
        <option @if($order->type==$type->id) selected @endif {{$type->text_id}} >{{$type->name}}</option>
        @endforeach
        </select>
        <!--{!! Form::select('type',$order_types,null,['class'=>'form-control']) !!}-->
    </div>
    <div class="form-group col-xs-12 col-md-2">
        <label class="control-label">Сумма</label>
        {!! Form::text('money',null,['class'=>'form-control','data-mask'=>'#0.00','data-mask-reverse'=>'true']) !!}
    </div>
    <div class="form-group col-xs-12 col-md-2">
        <label class="control-label">Серия паспорта</label>
        <div>
            {!! Form::text('passport_series',null,array(
            'placeholder'=>'XXXX',
            'pattern'=>'[0-9]{4}', 'class'=>'form-control',
            'data-minlength'=>'4','maxlength'=>'4'
            ))!!}
        </div>
    </div>
    <div class="form-group col-xs-12 col-md-2">
        <label class="control-label">Номер паспорта</label>
        <div>
            {!! Form::text('passport_number',null,array(
            'placeholder'=>'XXXXXX',
            'pattern'=>'[0-9]{6}', 'class'=>'form-control',
            'data-minlength'=>'6', 'maxlength'=>'6'
            ))!!}
        </div>
    </div>
    <a href="#" class="btn btn-default" name="findCustomerBtn" data-toggle="modal" data-target="#findCustomersModal"><span class="glyphicon glyphicon-search"></span></a>
    {!! Form::hidden('customer_id') !!}
    @if(!is_null($order->number))
    <div class="form-group">
        <label class="control-label">Номер ордера в 1С</label> 
        {!! Form::text('number',null,['class'=>'form-control input-sm','disabled'=>'disabled']) !!}
    </div>
    @endif
</div>
<br>
<button class="btn btn-primary pull-right" type="submit">Сохранить</button>
{!! Form::close() !!}
@stop
@section('scripts')
<script>
    (function () {
        $('#findCustomersModal form').submit(function () {
            $.get($(this).attr('action'), $(this).serialize()).done(function (data) {
                var i, str, attrs;
                for (i in data) {
                    str = data[i].fio + ' ' + data[i].series + ' ' + data[i].number;
                    attrs = 'data-id="' + data[i].id + '" data-series="' + data[i].series + '" ' + 'data-number="' + data[i].number + '"';
                    $('<a href="#" class="list-group-item" ' + attrs + '>' + str + '</a>').appendTo('#findCustomersResult').click(function () {
                        $('#orderEditForm [name="customer_id"]').val($(this).attr('data-id'));
                        $('#orderEditForm [name="passport_series"]').val($(this).attr('data-series'));
                        $('#orderEditForm [name="passport_number"]').val($(this).attr('data-number'));
                        $('#findCustomersModal').modal('hide');
                        return false;
                    });
                }
            });
            return false;
        });
    })(jQuery);
</script>
@stop