@extends('adminpanel')
@section('title') Исправление проблем @stop
@section('subcontent')
<div>
    Если не приходит кредитник:<br>
    Проверить есть ли такой контрагент в арме, если есть то нажать кнопку создать, ввести нужные данные и потом найти кредитник в "Погасить займ"
    <a href='#' data-toggle="modal" data-target="#createFakeClaimLoanModal" class='btn btn-default'>Создать</a>
    @if(Session::has('new_loan_id'))
    <a target="_blank" href="{{url('loans/summary/').Session::get('new_loan_id')}}">Открыть договор</a>
    @endif

    <div class="modal fade" id="createFakeClaimLoanModal" tabindex="-1" role="dialog" aria-labelledby="createFakeClaimLoanModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                {!! Form::open(['url'=>url('adminpanel/problemsolver/fakeloan')]) !!}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="createFakeClaimLoanModalLabel">Создание заявки и кредитника</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group-sm col-xs-12">
                            <label>Номер контрагента</label>
                            {!! Form::text('customer_id_1c',null,['class'=>'form-control']) !!}
                            <label>Номер заявки</label>
                            {!! Form::text('claim_id_1c',null,['class'=>'form-control']) !!}
                            <label>Номер кредитника</label>
                            {!! Form::text('loan_id_1c',null,['class'=>'form-control']) !!}
                            <label>Серия паспорта</label>
                            {!! Form::text('passport_series',null,['class'=>'form-control']) !!}
                            <label>Номер паспорта</label>
                            {!! Form::text('passport_number',null,['class'=>'form-control']) !!}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Создать</button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>
<hr>
<div>
    Удаление кредитника с сайта (необходим один любой параметр)<br>
    (на случай если договоры в таблице перемешались, или в 1с есть закрещенные, а в арме не находит последнего кредитника)
    {!! Form::open(['url'=>url('adminpanel/problemsolver/loan/remove2'),'class'=>'form-inline']) !!}
    <label>ID договора в арме</label>
    {!! Form::text('loan_id',null,['class'=>'form-control']) !!}
    <label>Код договора в 1С</label>
    {!! Form::text('loan_id_1c',null,['class'=>'form-control']) !!}
    <label>ID заявки в арме</label>
    {!! Form::text('claim_id',null,['class'=>'form-control']) !!}
    <button class='btn btn-primary'>Удалить</button>
    {!! Form::close() !!}
</div>
<hr>
<div>
    Удаление карты (на случай если она зарегистрирована на кого то другого)<br>
    {!! Form::open(['url'=>url('adminpanel/problemsolver/card/remove'),'class'=>'form-inline']) !!}
    <label>Номер карты</label>
    {!! Form::text('card_number',null,['class'=>'form-control']) !!}
    <button class='btn btn-primary'>Удалить</button>
    {!! Form::close() !!}
</div>
<hr>
<div>
    Удаление допника<br>
    {!! Form::open(['url'=>url('adminpanel/problemsolver/repayment/remove'),'class'=>'form-inline']) !!}
    <label>ID допника в 1С</label>
    {!! Form::text('repayment_id_1c',null,['class'=>'form-control']) !!}
    <label>ID допника в ARM</label>
    {!! Form::text('repayment_id',null,['class'=>'form-control']) !!}
    <button class='btn btn-primary'>Удалить</button>
    {!! Form::close() !!}
</div>
<hr>
<div>
    {!! Form::open(['url'=>url('adminpanel/problemsolver/photos/changefolder'),'class'=>'form-inline']) !!}
    Поменять папку с фотками<br>
    <label>ID заявки в арме</label>
    <input name="claim_id" class='form-control'/>
    <label>Старые серия и номер паспорта без пробела</label>
    <input name="oldseriesnumber" class='form-control'/>
    <label>Новые серия и номер паспорта без пробела</label>
    <input name="seriesnumber" class='form-control'/>
    <button class='btn btn-primary'>Поменять</button><br>
    {!! Form::close() !!}
</div>
<hr>
<div>
    {!! Form::open(['url'=>url('adminpanel/problemsolver/order/remove'),'class'=>'form-inline']) !!}
    Удалить ордер с сайта<br>
    <input name="order_number" class='form-control'/>
    <button class='btn btn-primary'>Удалить</button><br>
    {!! Form::close() !!}
</div>
<hr>
<div>
    {!! Form::open(['url'=>url('adminpanel/problemsolver/promocode/info'),'class'=>'form-inline']) !!}
    Получить информацию по промокоду<br>
    <input name="promocode_number" class='form-control' value='{{Session::get("promocode_number","")}}'/>
    <button class='btn btn-primary'>Получить</button><br>
    {!! Form::close() !!}
    @if(Session::has('promo_loan'))
    <p>Кредитник: {{Session::get('promo_loan')->loan_id_1c}} <a href='{{url('loans/summary/'.Session::get('promo_loan')->loan_id)}}' target="_blank">Открыть</a> {{Session::get('promo_loan')->fio}}</p>
    @endif
    @if(Session::has('promo_claims'))
    Заявки:
    <table class='table table-bordered'>
        <thead>
            <tr>
                <th>Номер заявки</th>
                <th>ФИО</th>
            </tr>
        </thead>
        <tbody>
            @foreach(Session::get('promo_claims') as $claim)
            <tr>
                <td>{{$claim->claim_id_1c}}</td>
                <td>{{$claim->fio}}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
<hr>
<!--<div>
    Заменить подстроку в формах договоров
    {!! Form::open(['url'=>url('adminpanel/problemsolver/phone/change'),'class'=>'form-inline']) !!}
    <input name="needle" placeholder="что нужно заменить" class="form-control" />
    <input name="replacer" placeholder="чем заменить" class="form-control" />
    <button class='btn btn-primary'>Поменять</button><br>
    {!! Form::close() !!}
</div>-->
<hr>
<div>
    {!! Form::open(['url'=>url('adminpanel/problemsolver/repayment/change'),'class'=>'form-inline']) !!}
    <div class="form-group">
        <label>Номер допника\заявления\мирового из 1С</label>
        <input type='text' name='id_1c' class='form-control' />
    </div>
    <div class="form-group">
        <label>Специалист</label>
        <input type='hidden' name='user_id' />
        <input type='text' name='user_id_autocomplete' class='form-control' data-autocomplete='users' />
    </div>
    <button class='btn btn-primary'>Поменять</button><br>
    {!! Form::close() !!}
</div>
<hr>
<div>
    {!! Form::open(['url'=>url('adminpanel/problemsolver/claim/remove'),'class'=>'form-inline']) !!}
    <div class="form-group">
        <label>Номер заявки из 1С</label>
        <input type='text' name='id_1c' class='form-control' />
    </div>
    <button class='btn btn-danger'>Удалить</button><br>
    {!! Form::close() !!}
</div>


@stop
@section('scripts')
<!--<script src="{{ URL::asset('js/adminpanel/adminpanelController.js') }}"></script>
<script>
(function () {
    $.adminCtrl.init();
})(jQuery);
</script>-->
@stop