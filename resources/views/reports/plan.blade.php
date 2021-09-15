@extends('reports.reports')
@section('title') План @stop
@section('subcontent')
<?php use Carbon\Carbon,     App\ContractForm,     App\Subdivision,     App\User; ?>
@if(Auth::user()->isAdmin())
{!! Form::open(['url'=>'reports/plan/loanslist','class'=>'form-inline','target'=>'_blank','method'=>'get']) !!}
<input name='user_id' type='hidden'/>
<input type='text' name='user_id_autocomplete' class='form-control' data-autocomplete='users' placeholder="специалист" />
<input name='subdivision_id' type='hidden'/>
<input type='text' name='subdivision_id_autocomplete' class='form-control' data-autocomplete='subdivisions' placeholder="подразделение" />
<button type='submit' class='btn btn-default'>Получить список займов</button>
{!! Form::close() !!}
@endif
<table class="table table-bordered">
    <thead>
        <tr>
            <th colspan="5">{{Auth::user()->subdivision->name}}</th>
        </tr>
        <tr>
            <th></th>
            <th>План</th>
            <th>Факт</th>
            <th>Процент выполнения</th>
            <th>Личный вклад</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Займы</td>
            <td>{{$data['loans']['plan']}} руб.</td>
            <td>{{$data['loans']['fact']}} руб.</td>
            <td>{{$data['loans']['complete']}}%</td>
            <td>{{$data['loans']['personal']}} руб.</td>
        </tr>
        <tr>
            <td>Заявки</td>
            <td>{{$data['claims']['plan']}} шт.</td>
            <td>{{$data['claims']['fact']}} шт.</td>
            <td>{{$data['claims']['complete']}}%</td>
            <td>{{$data['claims']['personal']}} шт.</td>
        </tr>
        <tr>
            <td>НПФ</td>
            <td>{{$data['npf']['plan']}} шт.</td>
            <td>{{$data['npf']['fact']}} шт.</td>
            <td>{{$data['npf']['complete']}}%</td>
            <td>{{$data['npf']['personal']}}%</td>
        </tr>
        <tr>
            <td>УКИ</td>
            <td>{{$data['uki']['plan']}} шт.</td>
            <td>{{$data['uki']['fact']}} шт.</td>
            <td>{{$data['uki']['complete']}}%</td>
            <td>{{$data['uki']['personal']}}%</td>
        </tr>
    </tbody>
</table>

@stop
@section('scripts')
@stop