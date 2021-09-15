(function ($) {
    $.photosCtrl = {};
    $.photosCtrl.init = function () {
        $('.photo-gallery .photo-preview').click($.photosCtrl.handlePhotoClick);
        $('.photo-gallery .photo-preview[data-main="1"]').click();
    };
    $.photosCtrl.markForRemove = function (elem) {
        var remIDs = [];
        if ($(elem).parents('figure:first').hasClass('for-remove')) {
            $(elem).parents('figure:first').removeClass('for-remove');
        } else {
            $(elem).parents('figure:first').addClass('for-remove');
        }
        $('#existingFilesPreviews figure.for-remove').each(function () {
            remIDs.push($(this).attr('data-id'));
        });
        $('[name="toRemoveIDs"]').val(remIDs.toString());
        $('#savePhotoChangesForm [type="submit"]').show();
        return false;
    };
    $.photosCtrl.makeMain = function (elem) {
        $('#filesPreviews,#existingFilesPreviews').find('.photo.selected').removeClass('selected');
        $(elem).parent().addClass('selected');
        $('[name="mainID"]').val($(elem).parent().attr('data-id'));
        $('#savePhotoChangesForm [type="submit"]').show();
    };
    $.photosCtrl.readURL = function (input) {
        var f, reader;
        $('#filesPreviews').empty();
        function addPreview(e) {
            var img = '<figure class="photo" style="display:inline-block;">\n\
            <img src="' + e.target.result + '" width="150"/>\n\
            <figcaption>' + e.currentTarget.filename + '</figcaption>\n\
            </figure>';
            $('#filesPreviews').append(img);
        }
        if (input.files) {
            for (var f in input.files) {
                if (typeof (input.files[f]) === "object") {
                    reader = new FileReader();
                    reader.filename = input.files[f].name;
                    reader.onload = addPreview;
                    reader.readAsDataURL(input.files[f]);
                }
            }
        }
    };
    $.photosCtrl.openPhoto = function (elem) {
        var opened = window.open(""),
                html = $(elem).parent().children('img')[0].outerHTML,
                title = $(elem).parent().children('figcaption').text();
        html = $(html).attr('onclick', '').attr('width', '').attr('style', '').prop('outerHTML');
        opened.document.write('<!DOCTYPE html><html><head><title>' + title + '</title></head><body><h1>' + title + '</h1>' + html + '</body></html>');
        return false;
    };
    $.photosCtrl.openPhoto2 = function (elem) {
        var win = window.open(armffURL + 'photos/view?id=' + $(elem).parents('.file-preview-frame').find('.file-preview-image').attr('data-id'), '_blank');
        if (win) {
            win.focus();
        } else {
            alert('Please allow popups for this site');
        }
//        var opened = window.open(""),
//                html = $(elem).parents('.file-preview-frame').find('.file-preview-image')[0].outerHTML,
//                title = $(elem).parents('.file-preview-frame').find('.file-preview-image').attr('title');
//        html = $(html).attr('onclick', '').attr('width', '').prop('outerHTML');
//        opened.document.write('<!DOCTYPE html><html><head><title>' + title + '</title>' +
//                '<link  href="' + armffURL + 'js/libs/viewer/viewer.min.css" rel="stylesheet">' +
//                '<script src="' + armffURL + 'js/libs/cdn/jquery.min.js"></script>' +
//                '<script src="' + armffURL + 'js/libs/viewer/viewer.min.js"></script>' +
//                '<script>$(document).ready(function(){$("img").viewer();})</script>' +
//                '</head><body><h1>' + title + '</h1>' + html + '</body></html>');
        return false;
    };
    $.photosCtrl.openPhoto3 = function (elem) {
        var $modal = ($('#photoViewerModal').length > 0) ? $('#photoViewerModal') : '';
        if ($modal == '') {
            $('body').append(
                    '<div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="photoViewerModal" aria-hidden="true" id="photoViewerModal">' +
                    '<div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header">' +
                    '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button></div>' +
                    '<div class="modal-body"><img class="photo-viewer-image"/></div></div></div></div>');
            $modal = $('#photoViewerModal');
        }
        $modal.find('img').attr('src', $(elem).parents('.file-preview-frame').find('.file-preview-image').attr('src'));
        $modal.modal();
        $modal.find('img').viewer({toolbar: true, zoomable: true, rotatable: true, inline: true});
        return false;
    };
    $.photosCtrl.setMain = function (elem) {
        var $item = $(elem).parents('.file-preview-frame').find('.file-preview-image'),
                main_id = $item.attr('data-id'),
                claim_id = $item.attr('data-claim-id');
        $.photosCtrl.setMainOnServer(main_id,function(data){
            if (parseInt(data) === 1) {
                $item.parents('.file-preview-thumbnails:first').find('.main-photo').removeClass('main-photo');
                $item.parent().addClass('main-photo');
            } else {
                $.app.ajaxResult(data);
            }
        });
    };
    $.photosCtrl.setMainFromGallery = function(elem){
        $.photosCtrl.setMainOnServer(elem.attr('data-id'),function(data){
            $.app.ajaxResult(data);
        });
    };
    $.photosCtrl.setMainOnServer = function (photo_id,callback){
        $.post($.app.url + '/photos/main', {main_id: photo_id}).done(function (data) {
            callback(data);
        });
    };
    $.photosCtrl.openAddPhotoModal = function (claim_id, customer_id) {
        $('#addPhotoInputHolder').empty();
        $('#addPhotoInputHolder').append('<input name="file[]" type="file" class="file-loading" accept="image/*" multiple="true">');
        $.app.blockScreen(true);
        $.get(armffURL + 'photos/get/all', {claim_id: claim_id, customer_id: customer_id}).done(function (data) {
            $.app.blockScreen(false);
            $('#webcamPhotoModal [name="webcam_claim_id"]').val(claim_id);
            $('#webcamPhotoModal [name="webcam_customer_id"]').val(customer_id);
            $.photosCtrl.initPhotoUploader('#addPhotosModal', '#addPhotoInputHolder', data, claim_id, customer_id);
            $('#addPhotosModal').modal();
        });
        $('#addPhotosModal [name="removeAllPhotosBtn"]').attr('data-claim-id', claim_id);
    };
    $.photosCtrl.openPreSendModal = function (elem, claim_id, customer_id) {
        $('#claimPresendInputHolder').empty();
        $('#claimPresendInputHolder').append('<input name="file[]" type="file" class="file-loading" accept="image/*" multiple="true">');
        $.app.blockScreen(true);
        $('#presendModalButton').attr('href', armffURL + 'claims/status/' + claim_id + '/1');
        $.get(armffURL + 'photos/get/all', {claim_id: claim_id, customer_id: customer_id}).done(function (data) {
            $.app.blockScreen(false);
            $.photosCtrl.initPhotoUploader('#claimPresendModal', '#claimPresendInputHolder', data, claim_id, customer_id);
        });
        $('#claimPresendModal').modal();
    };
    $.photosCtrl.initPhotoUploader = function (modal, inputHolder, data, claim_id, customer_id) {
        $(inputHolder + ' input[type="file"]').fileinput({
            uploadAsync: false,
            uploadUrl: armffURL + "photos/ajax/upload2",
            allowedFileExtensions: ["jpg", "png", "gif", "jpeg"],
            maxImageWidth: 800,
            maxImageHeight: 800,
            resizePreference: 'height',
            maxFileCount: 20,
            resizeImage: true,
            language: 'ru',
            uploadExtraData: {claim_id: claim_id, customer_id: customer_id},
            initialPreview: data.initialPreview,
            initialPreviewConfig: data.initialPreviewConfig,
            previewFileType: 'image',
            browseClass: 'btn btn-primary btn-sm',
            removeClass: 'btn btn-default btn-sm',
            uploadClass: 'btn btn-success btn-sm',
            layoutTemplates: {
                actions: '<div class="file-actions">\n' +
                        '    <div class="file-footer-buttons">\n' +
                        '    <button type="button" class="kv-file-browse btn btn-xs btn-default" title="" onclick="$.photosCtrl.setMain(this); return false;"><i class="glyphicon glyphicon-star"></i></button>\n' +
                        '    <button type="button" class="kv-file-browse btn btn-xs btn-default" title="" onclick="$.photosCtrl.openPhoto2(this); return false;"><i class="glyphicon glyphicon-eye-open"></i></button>\n' +
                        '        {upload}{delete}' +
                        '    </div>\n' +
                        '    <div class="file-upload-indicator" tabindex="-1" title="{indicatorTitle}">{indicator}</div>\n' +
                        '    <div class="clearfix"></div>\n' +
                        '</div>',
            }
        }).on('filepreupload', function () {
            $('#kv-success-box').html('');
        }).on('fileimagesloaded', function () {
            $(inputHolder + ' input[type="file"]').fileinput('upload');
            $.app.blockScreen(true);
        }).on('fileuploaded', function (event, data) {
            $('#kv-success-box').append(data.response.link);
            $('#kv-success-modal').modal('show');
        }).on('filebatchuploadsuccess', function () {
            $.app.blockScreen(false);
        });
        $(inputHolder + ' .input-group').prependTo($(inputHolder + ' .file-input'));
        $(inputHolder + ' .kv-upload-progress').prependTo($(inputHolder + ' .file-input'));
    };
    $.photosCtrl.openWebcamModal = function () {
        $('#webcamPhotoModal').modal('show');
        if ($('#webcamPhotoModal').attr('data-initialized') !== '1') {
            $('#webcamPhotoModal').on('shown.bs.modal', function (e) {
                Webcam.set({
                    width: 480,
                    height: 320,
                    image_format: 'jpeg',
                    jpeg_quality: 90,
                });
                Webcam.attach('#webcamViewer');
            });
            $('#webcamFreezeTogglerBtn').click(function () {
                $.photosCtrl.toggleWebcamFreeze($('#webcamViewer'));
            });
            $('#webcamUploadBtn').click(function () {
                $.photosCtrl.uploadWebcamSnapshot();
            });
            $('#webcamClientPhotoCheckbox').change(function () {
                if ($(this).prop('checked')) {
                    $('#webcamCrosshair').show();
                } else {
                    $('#webcamCrosshair').hide();
                }
            });
            $('#webcamPhotoModal').attr('data-initialized', '1');
        }
    };

    $.photosCtrl.toggleWebcamFreeze = function ($viewer,$uploadBtn) {
        if ($viewer.attr('data-frozen') != '1') {
            Webcam.freeze();
            $viewer.attr('data-frozen', '1');
            if(typeof($uploadBtn)!=='undefined'){
                $uploadBtn.prop('disabled',false);
            }
        } else {
            Webcam.unfreeze();
            $viewer.attr('data-frozen', '0');
            if(typeof($uploadBtn)!=='undefined'){
                $uploadBtn.prop('disabled',true);
            }
        }
    };
    $.photosCtrl.uploadWebcamSnapshot = function () {
        Webcam.snap(function (data_uri) {
            Webcam.upload(data_uri, $.app.url + '/photos/ajax/webcam/upload?claim_id=' + $('[name="webcam_claim_id"]').val() + '&customer_id=' + $('[name="webcam_customer_id"]').val(), function (code, text) {
                // Upload complete!
                // 'code' will be the HTTP response code from the server, e.g. 200
                // 'text' will be the raw response content
                if ($('#webcamClientPhotoCheckbox').prop('checked')) {
                    $('#webcamClientPhotoCheckbox').click();
                }
                $('#webcamPhotoModal').modal('hide');
                $('#addPhotosModal').modal('hide');
            });
        });
    };
    $.photosCtrl.initUserPhoto = function(){
        Webcam.set({
            width: 480,
            height: 320,
            image_format: 'jpeg',
            jpeg_quality: 90,
        });
        Webcam.attach('#userPhotoWebcamViewer');
        $('#userPhotoInitBtn').prop('disabled',true);
        $('#userPhotoFreezeBtn').prop('disabled',false);
    };
    $.photosCtrl.uploadUserPhoto = function(){
        Webcam.snap(function (data_uri) {
            Webcam.upload(data_uri, $.app.url + '/photos/ajax/userphoto/upload', function (code, text) {
                $.app.ajaxResult(1);
            });
        });
        $.app.ajaxResult(1);
    };
    $.photosCtrl.removeAllPhotos = function (btn) {
        var claim_id = $(btn).attr('data-claim-id');
        if (claim_id !== '') {
            $.post($.app.url + '/photos/ajax/remove/all', {claim_id: claim_id}).done(function (data) {
                $.app.ajaxResult(data);
                $('#addPhotosModal').modal('hide');
            });
        }
    };
    /**
     * нажатие на превью фотографий
     * @returns {Boolean}
     */
    $.photosCtrl.handlePhotoClick = function () {
        var $gallery = $(this).parents('.photo-gallery:first');
        $gallery.find('.main-photo>img').attr('src', $(this).attr('src'));
        $gallery.find('.photo-preview').removeClass('selected');
        $(this).addClass('selected');
        return false;
    };
})(jQuery);