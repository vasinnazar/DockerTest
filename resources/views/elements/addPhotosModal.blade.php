<div class="modal fade" id="addPhotosModal" tabindex="-1" role="dialog" aria-labelledby="addPhotosModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <!--<h4 class="modal-title" id="addPhotosModalLabel">После добавления фотографии нажмите на кнопку "Загрузить" под фотографиями!!</h4>-->
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <button type="button" name="openWebcamModalBtn" class="btn btn-default" onclick="$.photosCtrl.openWebcamModal();
                                return false;">
                            <span class="glyphicon glyphicon-cameraglyphicon glyphicon-camera"></span> Сделать фото с вебкамеры
                        </button>
                        @if(Auth::user()->isAdmin())
                        <button type='button' name='removeAllPhotosBtn' class='btn btn-default' onclick='$.photosCtrl.removeAllPhotos(this);' data-claim-id=''>
                            <span class='glyphicon glyphicon-trash'></span> Удалить все
                        </button>
                        @endif
                        <br>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group-sm col-xs-12" id="addPhotoInputHolder">

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="webcamPhotoModal" tabindex="-1" role="dialog" aria-labelledby="webcamPhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        {!! Form::checkbox('webcam_client_photo',1,null,['class'=>'checkbox','id'=>'webcamClientPhotoCheckbox']) !!}
                        <label class="control-label" for="webcamClientPhotoCheckbox">Фото клиента</label>
                        <button type="button" class="btn btn-default" id="webcamFreezeTogglerBtn">Сфотографировать</button>
                        <button type="button" class="btn btn-success" id="webcamUploadBtn">Загрузить</button>
                    </div>
                    <div class="col-xs-12">
                        <div id="webcamViewer"></div>
                        <div id="webcamCrosshair" class="webcam-crosshair"></div>
                    </div>
                </div>
                <input name="webcamData" type="hidden"/>
                <input name="webcam_claim_id" type="hidden"/>
                <input name="webcam_customer_id" type="hidden"/>
            </div>
        </div>
    </div>
</div>