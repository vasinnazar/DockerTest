@extends('adminpanel')
@section('title') Подразделения @stop
@section('subcontent')
<div id="subdivisionsFilter" class="form-inline">
    <label>Код</label>
    <input class="form-control input-sm" name="name_id"/> 
    <label>Название</label>
    <input class="form-control input-sm" name="name"/>
    <button class="btn btn-primary btn-sm" id="subdivisionsFilterBtn">
        <span class="glyphicon glyphicon-search"></span>
    </button>
    <a href="{{url('adminpanel/subdivisions/create')}}" class="btn btn-sm btn-default"><span class="glyphicon glyphicon-plus"></span> Добавить</a>
    <a href="{{url('adminpanel/subdivisions/cities')}}" class="btn btn-sm btn-default"><span class="glyphicon glyphicon-refresh"></span> Обновить города</a>
    <!--<button class="btn btn-default" id="getSubdivsBtn">Получить подразделения из 1С</button>-->
    
</div>
<table class="table table-borderless table-condensed table-striped" id="subdivisionsTable">
    <thead>
        <tr>
            <th>Код</th>
            <th>Адрес</th>
            <th></th>
        </tr>
    </thead>
</table>
@stop
@section('scripts')
<script>
    (function () {
        var tableCtrl = new TableController('subdivisions', [
            {data: '0', name: 'name_id'},
            {data: '1', name: 'name'},
            {data: '2', name: 'actions', orderable: false, searchable: false},
        ], {listURL: 'ajax/adminpanel/subdivisions/list'});
        
        $('#getSubdivsBtn').click(function(){
            $.app.blockScreen(true);
            $.post(armffURL+'/ajax/adminpanel/subdivisions/list/1c').done(function(data){
                $.app.blockScreen(false);
            });
            return false;
        });
    })(jQuery);
</script>
@stop
