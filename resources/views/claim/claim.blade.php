@extends('app')
@section('title') Выдача займа @stop
@section('css')
<link type="text/css" rel="stylesheet" href="{{ URL::asset('css/bootstrap-nav-wizard.css') }}">
<link type="text/css" rel="stylesheet" href="{{ URL::asset('css/loan/loanCreate.css') }}">
@stop
@section('content')
@if (count($errors) > 0)
<div class="alert alert-danger">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <strong>Внимание! Сводка по ошибкам.</strong> <br>В процессе заполнения допущены ошибки<br><br>
    <ul class="list-unstyled">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
<div>
    <?php
    echo Form::model($claimForm, array(
        'action' => (is_null($claimForm->claim_id)) ? 'ClaimController@store' : 'ClaimController@update',
        'class' => 'js-form-address',
        'role' => 'form',
        'id' => 'loanCreateForm',
        'data-validate-on-ready' => (!is_null($errors) && count($errors) > 0) ? 'true' : 'false'));
    ?>
    @if($header=="new")
    <h3> Новая заявка на потребительский займ</h3>
    @elseif($header=="new_post")
    <h3> Новая заявка на потребительский займ для постоянного клиента</h3>
    @elseif($header=="edit")
    <h3> Редактирование заявки на потребительский займ</h3>
    @elseif($header=="edit_post")
    <h3> Редактирование заявки на потребительский займ для постоянного клиента</h3>
    @endif
    @if(Auth::user()->id=='5' || Auth::user()->id_1c=='KAadmin')
    <p>
        <button class='btn btn-default' onclick="$.claimCtrl.testFill();
                return false;">Тестовое заполнение</button>
        @if(!is_null($claimForm->claim_id))
        <a class='btn btn-default' href='{{url('claims/updatefrom1c/'.$claimForm->claim_id)}}'>Загрузить из 1С</a>
        @endif
    </p>
    @endif
    <div class="pages">
        <div class="content" id="loanStepHolder">
            <ul class="nav nav-pills nav-wizard">
                <li class="active"><a href="#contragentData" data-toggle="tab">Данные контрагента</a></li>
                <li><a href="#residenceData" data-toggle="tab">Проживание</a></li>
                <li><a href="#additionalData" data-toggle="tab">Дополнительная информация</a></li>
                <li><a href="#loanData" data-toggle="tab">Информация по кредиту</a></li>
            </ul>
            <div class="tab-content wizard-content">
                <div class="tab-pane active" id="contragentData">
                    @include('claim.contragentData')
                </div>
                <div class="tab-pane" id="residenceData">
                    @include('claim.residenceData')
                </div>
                <div class="tab-pane" id="additionalData">
                    @include('claim.additionalData')
                </div>
                <div class="tab-pane" id="loanData">
                    @include('claim.loanData')
                </div>
            </div>
            <div class="row">
                <div class="form-group col-xs-12">
                    <label>Комментарий</label>
                    {!! Form::textarea('comment',null,['class'=>'form-control','placeholder'=>'Комментарий']) !!}
                    {!! Form::hidden('customer_id') !!}
                    {!! Form::hidden('passport_id') !!}
                    {!! Form::hidden('claim_id') !!}
                    {!! Form::hidden('about_client_id') !!}
                    {!! Form::hidden('customer_id_1c') !!}
                    {!! Form::hidden('card_number') !!}
                    {!! Form::hidden('secret_word') !!}
                    {!! Form::hidden('special_percent') !!}
                    {!! Form::hidden('timestart') !!}

                    {!! Form::hidden('loank_claim_id_1c') !!}
                    {!! Form::hidden('loank_loan_id_1c') !!}
                    {!! Form::hidden('loank_customer_id_1c') !!}
                    {!! Form::hidden('loank_closing_id_1c') !!}
                </div>
            </div>
            @if(Auth::user()->isAdmin())
            <div class="row">
                <div class="form-group col-xs-12">
                    <label>Дата</label>
                    <input type='datetime' name='created_at' class='form-control' value='{{$claimForm->claim_date}}' />
                </div>
                <div class="form-group col-xs-12">
                    <label>Подразделение</label>
                    <input type='hidden' name='subdivision_id' />
                    <input type='text' name='subdivision_id_autocomplete' class='form-control' data-autocomplete='subdivisions' />
                </div>
            </div>
            @endif
            <div class="row">
                <div class="form-group col-xs-3 col-xs-offset-9 nav-wizard-control">
                    @if(is_null($claimForm->claim_id))
                    <button class="btn btn-default nav-wizard-back" disabled onclick="return false;"><span class="glyphicon glyphicon-chevron-left"></span> Назад</button>
                    <button class="btn btn-default nav-wizard-forward" onclick="return false;">Далее <span class="glyphicon glyphicon-chevron-right"></span></button>
                    @endif
                    <?php echo Form::submit('Сохранить', array('class' => 'btn btn-primary pull-right', 'id' => 'loanFormSubmitBtn')); ?>
                </div>
            </div>
        </div>
    </div>
    <?php echo Form::close(); ?>
</div>
<div class="modal fade" id="claimFormWarningModal" tabindex="-1" role="dialog" aria-labelledby="claimFormWarningModalLabel" aria-hidden="true" data-not-opened='1'>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="claimFormWarningModalModalLabel">Внимание!</h4>
            </div>
            <div class="modal-body">
                @if(
                    !Auth::user()->isAdmin() 
                    && (
                        is_null($claimForm->claim_id) || 
                        (
                            $claimForm->claim_subdivision_id == config('options.office_subdivision_id') 
                            && Auth::user()->subdivision_id != config('options.office_subdivision_id')
                        ) || (
                            $claimForm->claim_subdivision_id == \App\Subdivision::where('name_id','Teleport')->value('id') 
                            && Auth::user()->subdivision_id != config('options.office_subdivision_id')
                        )
                    )
                )
                <div class="panel panel-default" id="checkPassportDataForm">
                    <div class="panel-heading">Проверка паспортных данных</div>
                    <div class="panel-body">
                        <div class='alert alert-danger' id='checkPassportDataAlert'>
                            Введите паспортные данные вручную еще раз!!! (Паспортные данные в этой форме должны совпадать с паспортными данными в форме заявки)
                        </div>
                        <div class="row">
                            <div class="form-group col-xs-12">
                                <label class="control-label">ФИО:<span class="required-mark">*</span></label>
                                <input class="form-control" name="check_fio" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-xs-4">
                                <label class="control-label">Дата рождения:<span class="required-mark">*</span></label>
                                <input class="form-control auto-year" name="check_birth_date" pattern='(0[1-9]|1[0-9]|2[0-9]|3[01]).(0[1-9]|1[012]).[0-9]{4}' />
                            </div>
                            <div class="form-group col-xs-8">
                                <label class="control-label">Место рождения:<span class="required-mark">*</span></label>
                                <input class="form-control" name="check_birth_city" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Кем выдан:<span class="required-mark">*</span></label>
                            <input class="form-control" name="check_issued" />
                        </div>
                        <div class="row">
                            <div class="form-group col-xs-6">
                                <label class="control-label">Код подразделения:<span class="required-mark">*</span></label>
                                <input class="form-control" name="check_subdivision_code" />
                            </div>
                            <div class="form-group col-xs-6">
                                <label class="control-label">Когда выдан:<span class="required-mark">*</span></label>
                                <input class="form-control auto-year" name="check_issued_date" pattern='(0[1-9]|1[0-9]|2[0-9]|3[01]).(0[1-9]|1[012]).[0-9]{4}' />
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                <div id="emptyInputsAlert" class="alert alert-warning">

                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" name="confirmBtn">Всё равно продолжить</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script type="text/javascript" src="{{ asset('js/form.js?8') }}"></script>
<script src="{{ URL::asset('js/jquery.bs-steps-wizard-ctrl.js') }}"></script>
<script src="{{ URL::asset('js/claim/claimController.js?20') }}"></script>
<script>
                        (function () {
                            $.claimCtrl.init();
                        })(jQuery);
</script>
@endsection
