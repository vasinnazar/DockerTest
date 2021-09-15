@extends('adminpanel')
@section('title') Сообщения @stop
@section('subcontent')
<!--<a class="btn btn-default" href="#" data-toggle="modal" data-target="#searchNpfModal"><span class="glyphicon glyphicon-search"></span> Поиск</a>-->
<!--<button class="btn btn-default" id="clearNpfsFilterBtn" disabled>Очистить фильтр</button>-->
<a class="btn btn-default" href="{{url('messages/create')}}"><span class="glyphicon glyphicon-plus"></span> Добавить</a>
<table id="messagesTable" class="table table-borderless table-condensed table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Дата</th>
            <th>Сообщение</th>
            <th>Пользователь</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td>{{$item->msg_id}}</td>
            <td>{{with(new Carbon\Carbon($item->msg_created))->format('d.m.Y H:i:s')}}</td>
            <td>{{$item->msg_text}}</td>
            <td>{{$item->username}}</td>
            <td>
                <a class='btn btn-default btn-sm' href='{{url('messages/edit?id='.$item->msg_id)}}'><span class='glyphicon glyphicon-pencil'></span></a>
                <a class='btn btn-default btn-sm' href='{{url('messages/remove?id='.$item->msg_id)}}'><span class='glyphicon glyphicon-remove'></span></a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class='pull-right'>{!! $items->render() !!}</div>

<!--<div class="modal fade" id="searchNpfModal" tabindex="-1" role="dialog" aria-labelledby="searchNpfModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="npfFilter">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="searchNpfModalLabel">Поиск договора</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="form-group-sm col-xs-12">
                        <label>ФИО</label>
                        <input name="fio" class="form-control"/>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="npfFilterBtn" data-dismiss="modal">
                    <span class="glyphicon glyphicon-search"></span> Поиск
                </button>
            </div>
        </div>
    </div>
</div>-->
@include('elements.claimForRemoveModal')
@stop