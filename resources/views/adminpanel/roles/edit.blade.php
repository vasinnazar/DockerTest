@extends('adminpanel')
@section('title') {{$title}} @stop
@section('subcontent')

<div class='col-xs-12'>
    <div class='row'>
        <div class='col-xs-12'>
            {!! Form::model($role,['url'=>url('/adminpanel/roles/update')]) !!}
            {!! Form::hidden('id') !!}
            <div class='form-group'>
                <label>Уникальный идентификатор</label>
                {!! Form::text('name',null,['class'=>'form-control']) !!}
            </div>
            <div class='form-group'>
                <label>Описание</label>
                {!! Form::text('description',null,['class'=>'form-control']) !!}
            </div>
            <div class='form-group'>
                <ul id='permissionsList' class='list-group'>
                    <?php
                    $checked = $role->permissions->pluck('id')->all();
                    foreach ($permissions as $perm) {
                        $opts = [];
                        if (in_array($perm->id, $checked)) {
                            $opts['checked'] = 'checked';
                        }
                        echo '<li class="list-group-item"><label>';
                        echo Form::checkbox('permission[]', $perm->id, $opts);
                        echo ' '.$perm->name;
                        echo '</label></li>';
                    }
                    ?>
                </ul>
                <div>
                    <button type='button' class='btn btn-default' data-toggle="modal" data-target="#permissionModal"><span class='glyphicon glyphicon-plus'></span> Добавить новое разрешение</button>
                </div>
            </div>
            <div class='form-group'>
                <button class='btn btn-primary' type='submit'>Сохранить</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

@include('elements.permissionEditModal')

@stop
@section('scripts')
<script src="{{asset('/js/adminpanel/rolesController.js')}}"></script>
<script>
(function () {
    $.rolesCtrl.init();
})(jQuery);
</script>
@stop
