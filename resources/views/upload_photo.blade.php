@extends('app')
@section('title') Добавить фотографии к займу @stop
@section('css')
<link type="text/css" rel="stylesheet" href="{{ URL::asset('css/dashboard/addPhoto.css') }}">
<link href="{{asset('/js/libs/bootstrap-fileinput/css/fileinput.min.css')}}" media="all" rel="stylesheet" type="text/css" />
@stop
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">

                <div class="panel-heading">Добавление фото к заявке на {{$fio}} от {{$claim->date}} на сумму {{$claim->summa}} рублей сроком {{$claim->srok}} дней.</div>

                <div class="panel-body">
                    {!!Form::open(['route' => 'photos.upload', 'class' => 'regForm',
                    'files' => true, 'id' => 'uploadPhotoForm'])!!}
                    <input id="input-image-3" name="file" type="file" class="file-loading" accept="image/*" multiple="true">
                    <div class="row">
                        <!--<div id="filesPreviews" class="col-xs-12"></div>-->

                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            {!! Form::hidden('customer_id', $client->id) !!}
                            {!! Form::hidden('claim_id', $claim->id) !!}
                            <?php
                            $existingPhotos = '';
                            $mainPhotoID = '';
                            if (!is_null($photos)) {
                                foreach ($photos as $photo) {
                                    $existingPhotos .= '<figure data-id="' . $photo->id . '" ' .
                                            'class="photo ' . (((bool) $photo->is_main) ? 'selected' : '') . '" >' .
//                                            '<img src="' .url($photo->path) .
                                            '<img src="data:image/' . pathinfo(url($photo->path), PATHINFO_EXTENSION)
                                            . ';base64,' . base64_encode(Storage::get($photo->path)) .
                                            '" alt="' . $photo->id . '" width="150"' .
                                            ' onclick="$.photosCtrl.makeMain(this);  return false;" />';
//                                    $existingPhotos .= '<figure data-id="' . $photo->id . '" ' .
//                                            'class="photo ' . (((bool) $photo->is_main) ? 'selected' : '') . '" >' .
//                                            '<img src="' .url($photo->path).'" alt="' . $photo->id . '" width="150"' .
//                                            ' onclick="$.photosCtrl.makeMain(this);  return false;" />';
                                    //добавить кнопку удаления если пользователь админ
                                    if (Auth::user()->isAdmin()) {
                                        $existingPhotos .=
                                                '<button class="btn rem-btn" onclick="$.photosCtrl.markForRemove(this); return false;">'
                                                . '<span class="glyphicon glyphicon-remove"></span>'
                                                . '</button>';
                                    }
                                    $existingPhotos .= '<button class="btn open-btn" onclick="$.photosCtrl.openPhoto(this); return false;">'
                                            . '<span class="glyphicon glyphicon-eye-open"></span>'
                                            . '</button>';
                                    $existingPhotos .= (!is_null($photo->description)) ? '<figcaption>' . $photo->description . '</figcaption>' : '';
                                    $existingPhotos .= '</figure>';
                                    if ($photo->is_main) {
                                        $mainPhotoID = $photo->id;
                                    }
                                }
                            }
                            ?>
                        </div>
                        <div class="col-xs-12">
                            <!--{!! Form::submit('Добавить выбранные файлы', ['class' => 'btn btn-primary pull-right', 'onclick'=>'$.app.blockScreen(true);']) !!}-->
                        </div>
                    </div>
                    {!! Form::close() !!}
                    {!! Form::open(['route'=>'photos.update', 'class' => 'regForm', 'id' => 'savePhotoChangesForm']) !!}
                    <p>Имеющиеся файлы:</p>
                    <div class="row">
                        <div id="existingFilesPreviews" class="col-md-12">
                            <?php echo $existingPhotos; ?>
                        </div>
                    </div>
                    {!! Form::hidden('toRemoveIDs') !!}
                    {!! Form::hidden('claim_id', $claim->id) !!}
                    {!! Form::hidden('mainID', $mainPhotoID) !!}
                    <div class="row">
                        {!! Form::submit('Сохранить изменения', ['class' => 'btn btn-primary col-xs-3 col-xs-offset-8', 'style' => 'display:none']) !!}
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            <!--<a href="{{url('claims/summary/'.$claim->id)}}" class="btn btn-default pull-right btn-lg">Перейти к заявке</a>-->
                            <a href="{{url('home')}}" class="btn btn-default pull-right btn-lg">Перейти к рабочему столу</a>
                        </div>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script src="{{asset('js/libs/bootstrap-fileinput/js/plugins/canvas-to-blob.min.js')}}" type="text/javascript"></script>
<script src="{{asset('js/libs/bootstrap-fileinput/js/fileinput.min.js')}}"></script>
<script src="{{asset('js/libs/bootstrap-fileinput/js/fileinput_locale_ru.js')}}"></script>
<script src="{{ URL::asset('js/dashboard/photosController.js') }}"></script>
<script>
$("#input-image-3").fileinput({
    uploadUrl: "{{url('photos/ajax/upload')}}",
    allowedFileExtensions: ["jpg", "png", "gif"],
    maxImageWidth: 1000,
    maxImageHeight: 1000,
    resizePreference: 'height',
    maxFileCount: 10,
    resizeImage: true,
    language: 'ru',
    uploadExtraData: {'claim_id':{{$claim->id}},'customer_id':{{$client->id}}}
}).on('filepreupload', function () {
    $('#kv-success-box').html('');
}).on('fileuploaded', function (event, data) {
    $('#kv-success-box').append(data.response.link);
    $('#kv-success-modal').modal('show');
});
</script>
@stop