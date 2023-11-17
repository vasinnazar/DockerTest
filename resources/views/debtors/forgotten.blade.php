@extends('app')
@section('title')
    Просроченные должники
@stop
@section('css')
    <link rel="stylesheet" href="{{asset('css/debtors/common.css')}}"/>
@stop
@section('content')

    <div class="row">
        <div class="col-xs-12">
            <table class='table table-borderless' id='forgottenDebtorFilter'>
                <tr>
                    <td class="form-inline" style='text-align: left;'>
                        {!!Form::open()!!}
                        <input id='user_id_auth' name='user_id_auth' value="{{$userId}}" type='hidden'/>
                        @if ($isChief)
                            Ответственный: <input name='users@name' type='text' class='form-control autocomplete'
                                                  data-hidden-value-field='search_field_users@id_1c'
                                                  style='width: 350px;'/>
                            <input id='search_field_users@id_1c' name='search_field_users@id_1c' type='hidden'/>

                            {!!Form::button('Найти', [
                                                        'class'=>'btn btn-primary',
                                                        'type'=>'button',
                                                        'id'=>'forgottenDebtorFilterButton'
                                                     ])
                            !!}
                            &nbsp;
                            <button class="btn btn-primary"
                                    onclick="$.debtorsCtrl.forgottenToExcel(); return false;">Excel
                            </button>
                        @endif
                        {!!Form::close()!!}
                    </td>
                </tr>
            </table>
        </div>
    </div>

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
    <script src="{{asset('js/debtors/debtorsController.js?2')}}"></script>
    <script>
        $(document).ready(function () {
            $.debtorsCtrl.init();
            $.debtorsCtrl.initDebtorForgottenTable();
        });
    </script>
@stop
