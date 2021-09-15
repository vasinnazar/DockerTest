<!DOCTYPE html>
<html>
    <head>
        <title>Фото: {{$photo->description}}</title>
        <link href="{{asset('/js/libs/viewer/viewer.min.css')}}" media="all" rel="stylesheet" type="text/css" />
        <script src="{{asset('js/libs/cdn/jquery.min.js')}}" type="text/javascript"></script>
        <script src="{{asset('js/libs/viewer/viewer.min.js')}}" type="text/javascript"></script>
    </head>
    <body>
        <div class="viewer" style="height: 800px">
            <img class="image" style="display: none;" src="{{$photo_img}}"/>
        </div>
        <script>
            $('.viewer .image').viewer({toolbar: true, zoomable: true, rotatable: true, navbar: false, title: false, button: false}).viewer('show');
        </script>
    </body>
</html>




