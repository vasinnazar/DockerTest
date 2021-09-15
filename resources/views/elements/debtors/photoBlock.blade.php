@if(count($photos))
<div class="main-photo" style="position: relative;">
    <img />
    <button class="btn open-btn" style="position: absolute; top: 3px; right: 3px;" onclick="$.photosCtrl.openPhoto(this)"><span class="glyphicon glyphicon-eye-open"></span></button>
    <button title='Сделать фото главным' class="btn" style="position: absolute; top: 3px; left: 3px;" onclick="$.photosCtrl.setMainFromGallery($('.debtor-card .photo-gallery .photo-preview.selected'))"><span class="glyphicon glyphicon-star"></span></button>
</div>
<div class="previews">
    @foreach($photos as $p)
    <img data-main="{{$p['is_main']}}" src="{{$p['src']}}" alt="{{$p['id']}}" data-id='{{$p['id']}}' class="photo-preview" />
    <!--<img data-main="{{$p['is_main']}}" class="photo-preview" src="{{url($p['path'])}}" alt="{{$p['id']}}" />-->
    @endforeach
</div>
@else
<p>Нет фото.</p>
@endif