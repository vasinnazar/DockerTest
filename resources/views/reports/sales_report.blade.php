@extends('reports.reports')
@section('title') Отчет по продажам @stop
@section('subcontent')
<p>Дата: {{$date}}</p>
<table class='table table-condensed table table-borderless' id='salesReportTable'>
    @foreach($cities as $city)
    <tr class='header bg-warning'>
        <td>{{$city['name']}}</td>
        <td style='width: 30%'>{{$city['sum']}} р.</td>
    </tr>
    <tr>
        <td colspan="2">
            <table class='table table-condensed' style='width: 100%'>
                @foreach($city['subdivisions'] as $item)
                <tr>
                    <td>{{$item['name']}}</td>
                    <td style='width: 30%'>{{$item['sum']}} р.</td>
                </tr>
                @endforeach
            </table>
        </td>
    </tr>
    @endforeach
    <tr>
        <td>Итого</td>
        <td>{{$total}} р.</td>
    </tr>
</table>
@stop
@section('scripts')
<script>
    $(document).ready(function () {
        $('#salesReportTable .header').click(function(){
            var collapsedClass = 'children-collapsed';
            if($(this).hasClass(collapsedClass)){
                $(this).removeClass(collapsedClass);
                $(this).next().show();
            } else {
                $(this).addClass(collapsedClass);
                $(this).next().hide();
            }
        });
    });
</script>
@stop