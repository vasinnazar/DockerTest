@extends('app')
@section('title') Сверка РНКО: Фотографии @stop
@section('css')
@parent
<link href="{{asset('/js/libs/viewer/viewer.min.css')}}" media="all" rel="stylesheet" type="text/css" />
<style>
    .viewer-holder{
        min-height: 300px;
    }
    .viewer-play{
        display: none;
    }
    #images li{
        list-style: none;
        /*display: inline-block;*/
        cursor: pointer;
    }
    #images li img{
        max-height: 200px;
        max-width: 200px;
    }
</style>
@stop
@section('content')
<div class='row'>
    <div class='col-xs-12'>
        <div class='viewer-holder'>
            <ul id="images">
                @foreach($photos as $p)
                <li><img src="{{$p}}" alt="Picture" width="0"></li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@stop
@section('scripts')
<script src="{{asset('js/libs/viewer/viewer.min.js')}}" type="text/javascript"></script>
<script>
$(document).ready(function () {
    $('#images').viewer({inline: true, fullscreen: false, navbar: true, minHeight: 500, zoomRatio: 0.5, title: false});
});
</script>
@stop