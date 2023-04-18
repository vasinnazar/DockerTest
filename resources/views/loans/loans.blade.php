@extends('app')
@section('title') Кредитники @stop
@section('content')
<a class="btn btn-default" href="#" data-toggle="modal" data-target="#searchLoanModal"><span class="glyphicon glyphicon-search"></span> Поиск</a>
<button class="btn btn-default" id="clearLoansFilterBtn" disabled>Очистить фильтр</button>
<button class="btn btn-default" id="repeatLastSearchBtn">Повторить последний запрос</button>
<table id="loansTable" class="table table-borderless table-condensed table-striped">
    <thead>
        <tr>
            <th>Дата</th>
            <th>Контрагент</th>
            <th>Сумма</th>
            <th>Срок</th>
            <th>Статус</th>
            <th></th>
        </tr>
    </thead>
</table>

<div class="modal fade" id="searchLoanModal" tabindex="-1" role="dialog" aria-labelledby="searchLoanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="loansFilter">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="searchLoanModalLabel">Поиск кредитника</h4>
            </div>
            @include('elements.searchClaimModalBody')
            <div class="modal-footer">
                <button class="btn btn-primary" id="loansFilterBtn" data-dismiss="modal">
                    <span class="glyphicon glyphicon-search"></span> Поиск
                </button>
            </div>
        </div>
    </div>
</div>
@stop
@section('scripts')
<script>
    (function () {
        var tableCtrl = new TableController('loans', [
            {data: 'loans_created_at', name: 'loans_created_at'},
            {data: 'passports_fio', name: 'passports_fio'},
            {data: 'loans_money', name: 'loans_money'},
            {data: 'loans_time', name: 'loans_time'},
            {data: 'loans_status', name: 'loans_status'},
            {data: 'actions', name: 'actions', searchable: false, orderable: false},
        ],{
            clearFilterBtn:$('#clearLoansFilterBtn'),
            repeatLastSearchBtn:$('#repeatLastSearchBtn'),
        });
    })(jQuery);
</script>
@stop
