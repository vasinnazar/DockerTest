@extends('app')
@section('title') 404: Страница не найдена @stop
@section('content')
<h1>Ошибка 404: Страница не найдена</h1>
Сейчас Вы будете перенаправлены на рабочий стол
@stop
@section('scripts')
<script>
    $(document).ready(function () {
        setTimeout(function () {
            window.location.replace(armffURL);
        }, 1500);
    });
</script>
@stop