@extends('adminpanel')
@section('title') Массовые обработки @stop
@section('subcontent')

<div class="row">
    <div class="col-xs-12">
        <h3>Поменять доверенности у специалистов</h3>
        {!! Form::open(['id'=>'userDocsChange','url'=>'adminpanel/massivechange/execute','files'=>true,'method'=>'POST']) !!}
        {!! Form::hidden('change_type','0') !!}
        <div class='form-group'>
            <label class='control-label'>Файл</label>
            {!! Form::file('file') !!}
        </div>
        <div class='form-group'>
            <button class='btn btn-primary'>Выполнить</button>
        </div>
        {!! Form::close()!!}
    </div>
</div>
<!--<div class="row">
    <div class="col-xs-12">
        <h3>Добавить роль специалистам</h3>
        {!! Form::open(['id'=>'userDocsChange','url'=>'adminpanel/massivechange/execute','files'=>true,'method'=>'POST','class'=>'form-inline']) !!}
        {!! Form::hidden('change_type','1') !!}
        <div class='form-group'>
            <label class='control-label'>Роль</label>
            {!! Form::select('role_id',\App\Role::lists('name','id'),null,['class'=>'form-control']) !!}
        </div>
        <div class='form-group'>
            <button class='btn btn-primary'>Выполнить</button>
        </div>
        {!! Form::close()!!}
    </div>
</div>-->

@stop
@section('scripts')
<script src="{{ URL::asset('js/adminpanel/adminpanelController.js') }}"></script>
<script>
(function () {
    $.adminCtrl.init();
})(jQuery);
</script>
@stop