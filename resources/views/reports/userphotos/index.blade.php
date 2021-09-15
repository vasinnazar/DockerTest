@extends('reports.reports')
@section('title') Фото специалистов @stop
@section('css')
<style>
    .user-photo{
        display: inline-block;
        margin: 10px;
    }
    .user-photo p{
        margin: 0;
        padding: 0;
    }
</style>
@stop
@section('subcontent')
<form class='form-inline' id='userphotosFilter'>
    {!! Form::select('director',$directorsList,null,['class'=>'form-control']) !!}
    {!! Form::hidden('subdivision_id') !!}
    <input type='text' name='subdivision_id_autocomplete' class='form-control' data-autocomplete='subdivisions' placeholder="Подразделение" />
    {!! Form::hidden('user_id') !!}
    <input type='text' name='user_id_autocomplete' class='form-control' data-autocomplete='users' placeholder="Специалист" />
    <label>С</label>
    <input type='date' name='date_min' class='form-control'/>
    <label>По</label>
    <input type='date' name='date_max' class='form-control'/>
    <button type='button' class="btn btn-default" id="userphotosClearFilterBtn" data-dismiss="modal">
        Очистить
    </button>
    <button type='button' class="btn btn-primary" id="userphotosFilterBtn" data-dismiss="modal">
        <span class="glyphicon glyphicon-search"></span> Поиск
    </button>
</form>
<!--<table class='table table-borderless' id='userphotosTable'>
    <thead>
        <tr>
            <th>Дата</th>
            <th>Специалист</th>
            <th>Подразделение</th>
            <th>Фото</th>
            <th></th>
        </tr>
    </thead>
</table>-->
<div id="photosHolder">

</div>

@stop
@section('scripts')
<script src="{{asset('js/common/TableController.js')}}"></script>
<script>
(function () {
    $('#userphotosFilterBtn').click(function(){
        uploadPhotos();
    });
    uploadPhotos();
    function uploadPhotos() {
        $.get($.app.url + '/ajax/userphotos/list2', $('#userphotosFilter').serialize()).done(function (data) {
            console.log(data);
            var html = '';
            var photo, photoDate, path, p;
            var last_date = null;
            for (p in data) {
                photo = data[p];
                photoDate = moment(photo.up_created_at);
                if (last_date === null || photoDate.format('DD.MM.YYYY') !== last_date) {
                    last_date = photoDate.format('DD.MM.YYYY');
                    html += '<h3>'+last_date+'</h3><hr>';
                }
                html += '<div class="user-photo">';
                path = $.app.url + '/' + photo.up_path;
                html += '<div class="text-center"><a href="' + path + '"><img src="' + path + '" width="200px"/></a></div>';
                html += '<p class="text-center">' + photoDate.format('DD.MM.YYYY HH:mm:ss') + '</p>';
                html += '<p class="text-center">' + photo.user_name + '</p>';
                html += '<p class="text-center"><span class="label label-default">' + photo.subdiv_name + '</span></p>';
                html += '</div>';
            }
            $('#photosHolder').html(html);
        });
    }
    function getPhotoHtml(photo) {
        var html = '';
        html += '<div class="user-photo">';
        var path = $.app.url + '/' + photo.up_path;
        html += '<div class="text-center"><a href="' + path + '"><img src="' + path + '" width="200px"/></a></div>';
        html += '<p class="text-center">' + photo.up_created_at + '</p>';
        html += '<p class="text-center">' + photo.user_name + '</p>';
        html += '<p class="text-center"><span class="label label-default">' + photo.subdiv_name + '</span></p>';
        html += '</div>';
        return html;
    }
//    var tableCtrl = new TableController('userphotos', [
//        {data: '0', name: 'up_created_at'},
//        {data: '1', name: 'user_name'},
//        {data: '2', name: 'subdiv_name'},
//        {data: '3', name: 'up_path', searchable: false, orderable: false},
//        {data: '4', name: 'actions', searchable: false, orderable: false},
//    ], {
//        order: [[0, "desc"]], 
//        listURL: 'ajax/userphotos/list',
//        clearFilterBtn:$('#userphotosClearFilterBtn')
//    });
})(jQuery);
</script>
@stop