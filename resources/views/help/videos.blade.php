@extends('app')
@section('title') Видео инструкции @stop
@section('content')
<div class='row'>
    <div class='col-xs-6 col-md-4 col-lg-2'>
        <div class='nav nav-pills nav-stacked'>
            @foreach($videos as $v)
            <li class='{{ (Request::path()=='help/videos/'.$v->id) ? 'active' : '' }}'><a href='{{url('help/videos/'.$v->id)}}'>{{$v->name}}</a></li>
            @endforeach
        </div>
    </div>
    <div class='col-xs-6 col-md-8 col-lg-10'>
        @if(isset($video))
        <video width="100%" controls="controls">
            <track src='{{url('helpfiles/'.$video->id.'/vtt')}}'>
            <source src="{{url('helpfiles/'.$video->id.'/ogv')}}" type='video/ogg; codecs="theora, vorbis"'>
            <source src="{{url('helpfiles/'.$video->id.'/mp4')}}" type='video/mp4; codecs="avc1.42E01E, mp4a.40.2"'>
            <source src="{{url('helpfiles/'.$video->id.'/webm')}}" type='video/webm; codecs="vp8, vorbis"'>
            Тег video не поддерживается вашим браузером. 
        </video>
        @endif
    </div>
</div>
@stop