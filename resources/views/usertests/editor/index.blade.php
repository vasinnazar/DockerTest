@extends('adminpanel')
@section('title') Тесты @stop
@section('subcontent')

<a class='btn btn-default' href='{{url('usertests/editor/create')}}'><span class='glyphicon glyphicon-plus'></span> Создать тест</a>

@if(isset($usertests))
<table class="table table-condensed" id='usertestsTable'>
    <thead>
        <tr>
            <th>Название теста</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($usertests as $test)
        <tr>
            <td>{{$test->name}}</td>
            <td>
                <a class="btn btn-default" href="{{url('usertests/editor/edit/'.$test->id)}}"><span class="glyphicon glyphicon-pencil"></span></a>
                <!--<a class="btn btn-default" href="{{url('usertests/editor/remove/'.$test->id)}}"><span class="glyphicon glyphicon-remove"></span></a>-->
                <a class="btn btn-default" href="{{url('usertests/view/'.$test->id)}}">Начать</a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
@stop
@section('scripts')
<script>
    (function(){
        $('#usertestsTable').DataTable({
            order: [[0, "desc"]],
            searching: true,
            sDom: 'Rfrtlip',
            lengthMenu: [[25, 50, 100], [25, 50, 100]],
            language: dataTablesRuLang,
        });
    })();
</script>
@stop