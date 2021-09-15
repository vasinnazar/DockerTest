@extends('app')
@section('title') Заявления @stop
@section('content')
<h3>{{$header}}</h3>
<div class="list-group">
    @foreach($blanks as $b)
    <a class="list-group-item" target="_blank" href="{{url('blanks/pdf/'.$b->id)}}">
        <h4 class="list-group-item-heading">{{$b->name}}</h4>
        <p class="list-group-item-text">{{$b->description}}</p>
    </a>
    @endforeach
</div>
@stop