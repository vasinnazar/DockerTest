@extends('app')
@section('title') Договоры @stop
@section('content')
<a class="btn btn-default" href="#" data-toggle="modal" data-target="#searchRepaymentModal"><span class="glyphicon glyphicon-search"></span> Поиск</a>
<!--<button class="btn btn-default" id="clearRepaymentsFilterBtn" disabled>Очистить фильтр</button>-->
<table id="repaymentsTable" class="table table-borderless table-condensed table-striped">
    <thead>
        <tr>
            <th>№</th>
            <th>№ 1C</th>
            <th>Дата</th>
            <th>Кредитный договор</th>
            <th>ФИО</th>
            <th>Тип договора</th>
            <th>Текст. Ид. договора</th>
            <th>Статус кредитника</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td>{{$item->rep_id}}</td>
            <td>{{$item->rep_id_1c}}</td>
            <td>{{$item->rep_created_at}}</td>
            <td><a href="{{url('loans/summary/'.$item->_loan_id)}}" target='_blank'>Открыть</a></td>
            <td>{{$item->pass_fio}}</td>
            <td>{{$item->reptype_name}}</td>
            <td>{{$item->reptype_text_id}}</td>
            <td>{{$item->loan_closed}}</td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="pull-right">
    {!! $items->render() !!}
</div>

<div class="modal fade" id="searchRepaymentModal" tabindex="-1" role="dialog" aria-labelledby="searchRepaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="repaymentFilter">
            {!! Form::open(['url'=>url('repayments'),'method'=>'get']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="searchRepaymentModalLabel">Поиск договора</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="form-group-sm col-xs-12">
                        <label>ФИО</label>
                        <input name="fio" class="form-control"/>
                    </div>
                    <div class="form-group-sm col-xs-12">
                        <label>Номер договора</label>
                        <input name="rep_id_1c" class="form-control"/>
                    </div>
                    <div class="form-group-sm col-xs-12">
                        <label>Статус кредитника</label>
                        <input name="loan_closed" class="form-control"/>
                    </div>
                    <div class="form-group-sm col-xs-12">
                        <label>Ид типа договора</label>
                        <select name='reptype_text_id' class='form-control'>
                            @foreach($repayment_types as $rt)
                            <option value='{{$rt}}'>{{$rt}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="repaymentFilterBtn" type='submit'>
                    <span class="glyphicon glyphicon-search"></span> Поиск
                </button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
@include('elements.claimForRemoveModal')
@stop
@section('scripts')
@stop