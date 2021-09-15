@extends('app')
@section('title') SMS: редактирование количества @stop
@section('content')
<div class="row">
    <div class="col-xs-12">
        <a href="#" class="btn btn-primary pull-right saveSmsEdit" style="margin-bottom: 10px; margin-right: 15px;">Сохранить</a>
    </div>
</div>
<div class='row'>
    <div class="col-xs-12">
        <form id="smsEditForm">
            <table class="table table-striped">
                <tr>
                    <th style="text-align: center; vertical-align: middle;">
                        ФИО специалиста
                    </th>
                    <th style="text-align: center;">
                        Кол-во SMS
                    </th>
                    <th style="text-align: center;">
                        Остаток
                    </th>
                </tr>
                @foreach ($users as $user)
                <tr>
                    <td style="text-align: center; vertical-align: middle;">
                        {{$user['name']}}
                    </td>
                    <td style="text-align: center;">
                            <center><input type="text" name="sms_user_{{$user['id']}}" class="form-control" style="width: 200px; text-align: center;"/></center>
                    </td>
                    <td style="text-align: center; vertical-align: middle;">
                        {{$user['sms_now']}}
                    </td>
                </tr>
                @endforeach
            </table>
        </form>
    </div>
</div>
<div class="row">
    <div class="col-xs-12">
        <a href="#" class="btn btn-primary pull-right saveSmsEdit" style="margin-bottom: 10px; margin-right: 15px;">Сохранить</a>
    </div>
</div>
@stop
@section('scripts')
<script src="{{asset('js/debtors/debtorsController.js')}}"></script>
<script src="{{asset('js/libs/bootstrap-datetimepicker/js/bootstrap-datetimepicker.js')}}"></script>
<script>
$(document).ready(function () {
    $.debtorsCtrl.init();
    $.debtorsCtrl.initDebtorsCard();
    
    $(document).on('click', '.saveSmsEdit', function(){
        $('.saveSmsEdit').attr('disabled', true);
        $.post($.app.url + '/debtors/editSmsCount', {smseditdata: $('#smsEditForm').serialize()}).done(function(){
            window.location.reload();
        });
    });
});
</script>

@stop
