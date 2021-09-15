@extends('adminpanel')
@section('title') Настройки @stop
@section('subcontent')

<div class='col-xs-12'>
    <div class='row'>
        <div class='col-xs-12 col-lg-3 col-sm-6'>
            {!! Form::open(['url'=>url('/adminpanel/smser/update')]) !!}
            <div class='form-group'>
                {!! Form::label('Сервер отправки SMS') !!}
                {!! Form::select('sms_server',$sms_servers_list,$sms_server_active,['class'=>'form-control']) !!}
            </div>
            <div class='form-group'>
                <button class='btn btn-primary'>Сохранить</button>
            </div>
            {!! Form::close() !!}
        </div>
        <div class='col-xs-12 col-lg-3 col-sm-6'>
            {!! Form::open(['url'=>url('/adminpanel/smser/check')]) !!}
            <div class='form-group'>
                {!! Form::label('Сервер отправки SMS') !!}
                {!! Form::select('sms_server',$sms_servers_list,$sms_server_active,['class'=>'form-control']) !!}
            </div>
            <div class='form-group'>
                {!! Form::text('telephone',$sms_test_telephone,['class'=>'form-control']) !!}
            </div>
            <div class='form-group'>
                <button class='btn btn-primary'>Отправить тестовое SMS</button>
            </div>
            {!! Form::close() !!}
        </div>
        <div class='col-xs-12 col-lg-3 col-sm-6'>
            {!! Form::open(['url'=>url('/adminpanel/printserver/update')]) !!}
            <div class='form-group'>
                {!! Form::label('Сервер печати') !!}
                {!! Form::select('print_server',$print_servers_list,$print_server,['class'=>'form-control']) !!}
            </div>
            <div class='form-group'>
                <button class='btn btn-primary'>Сохранить</button>
            </div>
            {!! Form::close() !!}
        </div>
        <div class='col-xs-12 col-lg-3 col-sm-6'>
            <h4>Количество соединений: <span class='label label-info' id='mysqlThreadsCount'>-</span> <button class='btn btn-default' id='getMysqlThreadsBtn'>Получить кол-во соединений</button></h4>
            <div class='form-group'>
                <a class='btn btn-danger' href='{{url('/adminpanel/config/mysql/kill/all')}}'>Удалить все соединения</a>
            </div>
            <div class='form-group'>
                <a class='btn btn-danger' href='{{url('/adminpanel/config/mysql/kill/sleep')}}'>Удалить спящие соединения</a>
            </div>
            <div class='form-group'>
                <a class='btn btn-default' href='{{url('/adminpanel/config/mysql/threads/chart')}}'>Показать статистику</a>
            </div>
        </div>
        <div class='col-xs-12 col-lg-3 col-sm-6'>
            {!! Form::open(['url'=>url('/adminpanel/config/1c/update')]) !!}
            <div class='form-group'>
                {!! Form::label('Сервер 1c') !!}
                {!! Form::select('server_1c',$servers_1c_list,$server_1c,['class'=>'form-control']) !!}
            </div>
            <div class='form-group'>
                {!! Form::checkbox('auto_change_server_1c','auto_change_server_1c',$auto_change_server_1c,['class'=>'checkbox','id'=>'autoChangeServer1cCheckbox']) !!}
                <label for='autoChangeServer1cCheckbox'>Автоматически переключать сервер 1С</label>
            </div>
            <div class='form-group'>
                <button class='btn btn-primary'>Сохранить</button>
            </div>
            {!! Form::close() !!}
        </div>
        <div class='col-xs-12 col-lg-3 col-sm-6'>
            {!! Form::open(['url'=>url('/adminpanel/config/mode/update')]) !!}
            <div class='form-group'>
                {!! Form::checkbox('maintenance_mode','maintenance_mode',$maintenance_mode,['class'=>'checkbox','id'=>'maintenanceModeCheckbox']) !!}
                <label for='maintenanceModeCheckbox'>Режим тех. обслуживания</label>
            </div>
            <div class='form-group'>
                {!! Form::checkbox('orders_without_1c','orders_without_1c',$orders_without_1c,['class'=>'checkbox','id'=>'ordersWithout1cCheckbox']) !!}
                <label for='ordersWithout1cCheckbox'>Не отправлять документы в 1С</label>
            </div>
            <div class='form-group'>
                <button class='btn btn-default' id='syncOrdersBtn' type='button'>Синхр. ордеры</button>
            </div>
            <div class='form-group'>
                <button class='btn btn-primary'>Сохранить</button>
            </div>
            {!! Form::close() !!}
        </div>
        <div class='col-xs-12 col-lg-3 col-sm-6'>
            <div class="form-group">
                Кол-во пустых контрагентов в должниках: 
                <span class="label label-warning" id="emptyDebtorsUploadData" style="font-size: 14px">
                    {{$empty_customers}}
                </span>
            </div>
            <div class='form-group'>
                <button class='btn btn-default' type='button' id="emptyDebtorsUploadBtn">Загрузить пустых должников в АРМ</button>
            </div>
        </div>
    </div>
</div>

@stop
@section('scripts')
<script src="{{ URL::asset('js/adminpanel/adminpanelController.js') }}"></script>
<script>
(function () {
    $.adminCtrl.init();
    $('#getMysqlThreadsBtn').click(function () {
        $.post($.app.url + '/adminpanel/config/mysql/threads').done(function (data) {
            $('#mysqlThreadsCount').text(data);
        });
    });
    $('#syncOrdersBtn').click(function () {
        $.app.blockScreen(true);
        $.post($.app.url + '/adminpanel/config/orders/sync').done(function (data) {
            console.log(data);
            $.app.blockScreen(false);
        });
    });
    $('#emptyDebtorsUploadBtn').click(function () {
        $.app.blockScreen(true);
        $.post($.app.url + '/adminpanel/config/debtors/upload').done(function (data) {
            $.app.blockScreen(false);
            $('#emptyDebtorsUploadData').text(data);
        });
    });
})(jQuery);
</script>
@stop