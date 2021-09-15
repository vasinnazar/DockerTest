@extends('app')
@section('content')
<h1>Подождите немного. Если документ не открылся автоматически, нажмите кнопку "Печать"</h1>
<form method="post" action="http://<?php echo config('admin.print_server'); ?>/htmltopdf.loc/index.php" id="htmlToPdfForm">
    <input type='hidden' name='html' value="{{$html}}"/>
    <input type='hidden' name='opts' value="{{$opts}}"/>
    <button type="submit" class="btn btn-default">Печать</button>
</form>
<script>
    $(document).ready(function () {
        $('#htmlToPdfForm').submit();
    });
</script>
@stop