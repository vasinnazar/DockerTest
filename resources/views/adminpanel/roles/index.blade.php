@extends('adminpanel')
@section('title') Роли @stop
@section('subcontent')

<div class='col-xs-12'>
    <div class='row'>
        <div class='col-xs-12 col-lg-4 col-sm-6'>
            <div>
                <a class='btn btn-default' href='{{url("/adminpanel/roles/create")}}'><span class='glyphicon glyphicon-plus'></span> Создать</a>
            </div>
            <hr>
            <ul class='list-group'>
                @foreach($roles as $role)
                <li class='list-group-item'>
                    <h4 class='list-group-item-heading'>{{$role->name}}</h4>
                    <p class='list-group-item-text small'>
                        {{$role->description}} 
                        <a href='{{url('/adminpanel/roles/edit/'.$role->id)}}' class='btn btn-default btn-sm'><span class='glyphicon glyphicon-pencil'></span></a>
                    </p>
                </li>
                @endforeach
            </ul>
        </div>
        <div class='col-xs-12 col-lg-4 col-sm-6'>
            <div>
                <button type='button' class='btn btn-default' data-toggle="modal" data-target="#permissionModal"><span class='glyphicon glyphicon-plus'></span> Добавить новое разрешение</button>
            </div>
            <hr>
            <ul class='list-group' id='permissionsList2'>
                <li class='list-group-item permission-item permission-item-template2 hidden'>
                    <h4 class='list-group-item-heading'>
                        <span class='permission-name'></span>
                        <span class='pull-right'>
                            <button class='btn btn-default btn-sm edit-btn'><span class='glyphicon glyphicon-pencil'></span></button>
                            <a href='' class='btn btn-default btn-sm remove-btn'><span class='glyphicon glyphicon-remove'></span></a>
                        </span>
                    </h4>
                    <p class='permission-description'></p>
                </li>
                @foreach($permissions as $perm)
                <li class='list-group-item permission-item' data-permission-id='{{$perm->id}}'>
                    <h4 class='list-group-item-heading'>
                        <span class='permission-name'>{{$perm->name}}</span>
                        <span class='pull-right'>
                            <button onclick='$.rolesCtrl.editPermission({{$perm->id}});' class='btn btn-default btn-sm edit-btn'><span class='glyphicon glyphicon-pencil'></span></button>
                            <a href='{{url('/adminpanel/permissions/destroy/'.$perm->id)}}' class='btn btn-default btn-sm remove-btn'><span class='glyphicon glyphicon-remove'></span></a>
                        </span>
                    </h4>
                    <p class='permission-description'>{{$perm->description}}</p>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@include('elements.permissionEditModal')
@stop
@section('scripts')
<script src="{{asset('js/adminpanel/rolesController.js')}}"></script>
<script>
(function () {
    $.rolesCtrl.init();
})(jQuery);
</script>
@stop