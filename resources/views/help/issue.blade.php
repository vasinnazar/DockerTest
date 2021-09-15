@extends('help.helpmenu')
@section('title')Заявки на подотчет@stop
@section('subcontent')
<video width="50%" controls="controls">
   <source src="{{asset('files/help/issue/issue.ogv')}}" type='video/ogg; codecs="theora, vorbis"'>
   <source src="{{asset('files/help/issue/issue.mp4')}}" type='video/mp4; codecs="avc1.42E01E, mp4a.40.2"'>
   <source src="{{asset('files/help/issue/issue.webm')}}" type='video/webm; codecs="vp8, vorbis"'>
   Тег video не поддерживается вашим браузером. 
   <a href="files/help/issue/issue.mp4">Скачайте видео</a>.
</video>
@stop