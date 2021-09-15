@extends('adminpanel')
@section('title') НПФ @stop
@section('subcontent')
<a href="{{url('adminpanel/npffonds/create')}}" class="btn btn-default"><span class="glyphicon glyphicon-plus"></span> Создать</a>
<br>
<br>
<ul class="list-group">
    @foreach($npf_fonds as $item)
    <li class="list-group-item">
        <div class="btn-group btn-group-sm">
            <a href="{{url('adminpanel/npffonds/edit?id='.$item->id)}}" class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
            <a href="{{url('adminpanel/npffonds/remove?id='.$item->id)}}" class="btn btn-default"><span class="glyphicon glyphicon-remove"></span></a>
        </div>
        {{$item->name}} 
    </li>
    @endforeach
</ul>
@stop
@section('scripts')
@stop