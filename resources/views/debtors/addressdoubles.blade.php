@extends('app')
@section('title') Дубли адресов @stop
@section('content')
<div class="btn-group">
    <button type="button" class="btn btn-default" data-toggle="modal" data-target="#addressdoublesSearchModal"><span class="glyphicon glyphicon-search"></span> Поиск</button>
</div>
<table class="table table-bordered table-condensed" id="addressdoublesTable">
    <thead>
        <tr>
            <th>ФИО должника</th>
            <th>Адрес должника</th>
            <th>Телефон должника</th>
            <th>Кол-во дней просрочки</th>
            <th>ФИО контрагента</th>
            <th>Адрес контрагента</th>
            <th>Телефон контрагента</th>
            <th>Комментарий</th>
            <th>Дата</th>
            <th>Ответственный</th>
            <th>Должник</th>
            <th></th>
        </tr>
    </thead>
</table>
<div class="modal fade" id="addressdoublesSearchModal" tabindex="-1" role="dialog" aria-labelledby="addressdoublesSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="addressdoublesSearchModalLabel">Введите данные клиента</h4>
            </div>
            {!!Form::open()!!}
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <table class='table table-borderless' id='addressdoublesFilter'>
                            @foreach($addressdoublesSearchFields as $dsf)
                            <tr>
                                <td>{{$dsf['label']}}</td>
                                <td>
                                    <select class='form-control' name='{{'search_field_'.$dsf['name'].'_condition'}}'>
                                        <option value='='>=</option>
                                        <option value="<"><</option>
                                        <option value="<="><=</option>
                                        <option value=">">></option>
                                        <option value=">=">>=</option>
                                        <option value="<>">Не равно</option>
                                        <option value="like">подобно</option>
                                    </select>
                                </td>
                                <td>
                                    @if(array_key_exists('hidden_value_field',$dsf))
                                    <input name='{{$dsf['name']}}' type='{{$dsf['input_type']}}' class='form-control autocomplete' data-hidden-value-field='{{'search_field_'.$dsf['hidden_value_field']}}'/>
                                    <input name='{{'search_field_'.$dsf['hidden_value_field']}}' type='hidden' />
                                    @else
                                    <input name='{{'search_field_'.$dsf['name']}}' type='{{$dsf['input_type']}}' class='form-control'/>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                {!!Form::button('Очистить фильтр',['class'=>'btn btn-default','type'=>'button', 'id'=>'addressdoublesClearFilterBtn'])!!}
                {!!Form::button('Найти',['class'=>'btn btn-primary','type'=>'button', 'id'=>'addressdoublesFilterBtn'])!!}
            </div>
            {!!Form::close()!!}
        </div>
    </div>
</div>
@stop
@section('scripts')
<script>
    (function () {
        var tableCtrl = new TableController('addressdoubles', [
            {data: 'debtor_fio', name: 'debtor_fio'},
            {data: 'debtor_address', name: 'debtor_address'},
            {data: 'debtor_telephone', name: 'debtor_telephone'},
            {data: 'debtor_overdue', name: 'debtor_overdue'},
            {data: 'customer_fio', name: 'customer_fio'},
            {data: 'customer_address', name: 'customer_address'},
            {data: 'customer_telephone', name: 'customer_telephone'},
            {data: 'comment', name: 'comment'},
            {data: 'date', name: 'date'},
            {data: 'responsible_user_id_1c', name: 'responsible_user_id_1c'},
            {data:  'is_debtor', name: 'is_debtor'},
            {data:  'actions', name: 'actions', searchable: false, orderable: false},
        ], {
            clearFilterBtn: $('#addressdoublesClearFilterBtn'),
        });
    })();
</script>
@stop
