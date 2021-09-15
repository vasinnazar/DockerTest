@extends('help.helpmenu')
@section('title')Заполнение адресов@stop
@section('subcontent')
<video width="50%" controls="controls">
   <source src="{{asset('files/help/naspunkt2/naspunkt2.ogv')}}" type='video/ogg; codecs="theora, vorbis"'>
   <source src="{{asset('files/help/naspunkt2/naspunkt2.mp4')}}" type='video/mp4; codecs="avc1.42E01E, mp4a.40.2"'>
   <source src="{{asset('files/help/naspunkt2/naspunkt2.webm')}}" type='video/webm; codecs="vp8, vorbis"'>
   Тег video не поддерживается вашим браузером. 
   <a href="files/help/naspunkt2/naspunkt2.mp4">Скачайте видео</a>.
</video>
<hr>
<video width="50%" controls="controls">
   <source src="{{asset('files/help/naspunkt/naspunkt.ogv')}}" type='video/ogg; codecs="theora, vorbis"'>
   <source src="{{asset('files/help/naspunkt/naspunkt.mp4')}}" type='video/mp4; codecs="avc1.42E01E, mp4a.40.2"'>
   <source src="{{asset('files/help/naspunkt/naspunkt.webm')}}" type='video/webm; codecs="vp8, vorbis"'>
   Тег video не поддерживается вашим браузером. 
   <a href="files/help/naspunkt/naspunkt.mp4">Скачайте видео</a>.
</video>
@stop