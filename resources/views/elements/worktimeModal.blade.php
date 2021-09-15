<?php

use Carbon\Carbon; ?>
@if(!Auth::guest())
<div class="modal fade bs-example-modal-lg <?php
if (isset($show_worktime_form) && $show_worktime_form) {
    echo 'open-on-load';
}
?>" tabindex="-1" role="dialog" aria-labelledby="addWorkTimeModal" aria-hidden="true" id="addWorkTimeModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Учет рабочего времени</h4>
            </div>
            {!! Form::open(['url' => 'worktime/update','id'=>'workTimeForm']) !!}
            <div class="modal-body">
                <div class="row">
                    @if(is_null(Auth::user()->birth_date))
                    <div class="form-group col-xs-12 col-md-12">
                        <div class="alert alert-warning">
                            <label class="control-label">Дата рождения:</label>
                            <input class="form-control" name="birth_date" type="date"/>
                        </div>
                    </div>
                    @endif
                    <div class="form-group col-xs-12 col-md-12">
                        <label class="control-label">Код подразделения:</label>
                        <input class="form-control" name="subdivision_name_id" type="text" value="{{Auth::user()->subdivision->name_id}}"/>
                    </div>
                    <div class="form-group col-xs-12 col-md-6">
                        <label class="control-label">Дата начала:</label>
                        <input class="form-control" name="date_start" type="text" value="{{Carbon::now()->format('d.m.Y H:i:s')}}" disabled/>
                    </div>
                    <div class="form-group col-xs-12 col-md-6">
                        <label class="control-label">Дата завершения:</label>
                        <input class="form-control" name="date_end" type="text" disabled/>
                    </div>
                    <div class='col-xs-12'>
                        @if(in_array(Request::path(),['','dashboard','home','/']))
                        <div class="row">
                            <div class="col-xs-12 text-center">
                                <div style="width: 480px; display: inline-block">
                                    <div id="userPhotoWebcamViewer"></div>
                                </div>
                            </div>
                            <div class="col-xs-12 text-center">
                                <button type="button" class="btn btn-default" onclick="$.photosCtrl.initUserPhoto()" id="userPhotoInitBtn">Добавить фото</button>
                                <button disabled type="button" class="btn btn-default" onclick='$.photosCtrl.toggleWebcamFreeze($("#userPhotoWebcamViewer"),$("#userPhotoUploadBtn"))' id="userPhotoFreezeBtn">Сфотографировать</button>
                                <button disabled type="button" class="btn btn-success" onclick='$.photosCtrl.uploadUserPhoto()' id="userPhotoUploadBtn">Загрузить</button>
                            </div>
                        </div>
                        <input name="webcamData" type="hidden"/>
                        @endif
                    </div>
                    <div class="form-group col-xs-12 col-md-9">
                        <label class="control-label">Отзыв:</label>
                        <input class="form-control" name="review"/>
                    </div>
                    <div class="form-group col-xs-12 col-md-3">
                        <label class="control-label">Оценка работы поддержки:</label>
                        <select name="evaluation" class="form-control">
                            @for($i=5;$i>0;$i--)
                            <option value="{{$i}}" @if($i==5) selected @endif>{{$i}}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group col-xs-12">
                        <label class="control-label">Комментарий:</label>
                        <input class="form-control" name="comment"/>
                    </div>
                    <div class="col-xs-12">
                        <label class="control-label">Причины отсутствия:</label>
                        <input name="reason" type="hidden" />
                        <input name="id" type="hidden" />
                        <input name="logout" type="hidden" value="0" />
                        <table id="workTimeReasonTable" class="table">
                            <thead>
                                <tr>
                                    <th>С</th>
                                    <th>По</th>
                                    <th>Причина</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input name="absent_start" type="time" class="form-control"/></td>
                                    <td><input name="absent_end" type="time" class="form-control"/></td>
                                    <td><input name="absent_reason" type="text" class="form-control"/></td>
                                    <td style="min-width: 100px;">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-default" name="add"><span class="glyphicon glyphicon-plus"></span></button>
                                            <button class="btn btn-default" name="remove"><span class="glyphicon glyphicon-trash"></span></button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="submit">Сохранить</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
@endif