@extends('adminpanel')
@section('title') Гашение @stop
@section('subcontent')
<a href="{{url('adminpanel/repaymenttypes/create/')}}" class="btn btn-default"><span class="glyphicon glyphicon-plus"></span> Создать</a>
<table class="table table-borderless table-condensed table-striped">
    <thead>
        <tr>
            <th>Тип</th>
            <th>Сумма ОД</th>
            <th>Сумма процентов</th>
            <th>Сумма просроч. процентов</th>
            <th>Сумма пени</th>
            <th>% просрочки</th>
            <th>% пени</th>
            <th>Срок заморозки</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($repaymentTypes as $r)
        <tr>
            <td>{{$r->name}}</td>
            <td>{{$r->od_money/100}} руб.</td>
            <td>{{$r->percents_money/100}} руб.</td>
            <td>{{$r->exp_percents_money/100}} руб.</td>
            <td>{{$r->fine_money/100}} руб.</td>
            <td>{{$r->exp_percent}} %</td>
            <td>{{$r->fine_percent}} %</td>
            <td>{{$r->freeze_days}} д.</td>
            <td>
                <a href="{{url('adminpanel/repaymenttypes/edit/'.$r->id)}}" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-pencil"></span></a>
                <a href="{{url('adminpanel/repaymenttypes/remove/'.$r->id)}}" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-remove"></span></a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@stop
@section('scripts')
<!--<script>
    (function () {
        var tableCtrl = new TableController('subdivisions', [
            {data: '0', name: 'name_id'},
            {data: '1', name: 'name'},
            {data: '2', name: 'actions', orderable: false, searchable: false},
        ], {listURL: 'ajax/adminpanel/subdivisions/list'});
    })(jQuery);
</script>-->
@stop