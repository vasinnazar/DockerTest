@extends('app')
@section('content')
<form class='form-inline' url='{{url('adminpanel/tester')}}'>
    <input class='form-control' name='phone' value='79059060315' />
    <input class='form-control' name='message' />
    <button type='submit' class='btn btn-default'>Отправить</button>
</form>
<hr>
<a href='{{url('/adminpanel/tester?handle_inbox=1')}}' class='btn btn-default'>Обработать смски из базы</a>
<a href='{{url('/adminpanel/tester?handle_inbox=1&goip=1')}}' class='btn btn-default'>Обработать реальные смски</a>
@stop
@section('scripts')
<script>
    $(document).ready(function(){
        
    });
</script>
@stop