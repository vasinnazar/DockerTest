@extends('app')
@section('title') Результаты опроса @stop
@section('css')
<style>
    .hidden-child-row{
        display: none;
    }
    .pointer{
        cursor: pointer;
    }
    .comments-list{
        text-align: left;
        padding-left: 20px;
    }
    thead, tfoot { display: table-row-group }
    /*tr {page-break-inside: avoid;}*/
    @media print
    {    
        .hidden-child-row{
            display: table-row !important;
        }
        .no-print,.btn{
            display: none;
        }
    }
    @media screen
    {
        .no-screen{
            display: none;
        }
    }
</style>
@stop
@section('content')
<div class='container'>
    <div class='row no-print'>
        <div class='col-xs-12'>
            <form class='form-inline'>
                {!! Form::select('year',\App\Utils\HtmlHelper::GetYearSelectData(),Request::get('year',\Carbon\Carbon::now()->year),['class'=>'form-control']) !!}
                {!! Form::select('month',\App\Utils\HtmlHelper::GetMonthSelectData(true),Request::get('month',\Carbon\Carbon::now()->month),['class'=>'form-control']) !!}
                {!! Form::select('fio_ruk',$directors,Request::get('fio_ruk','all'),['class'=>'form-control']) !!}
                <button type='submit' class='btn btn-default'>Сформировать</button>
            </form>
        </div>
    </div>
    <div class='row'>
        <div class='col-xs-12'>
            <h4>{{\App\Utils\HtmlHelper::GetMonthSelectData(true)[Request::get('month',\Carbon\Carbon::now()->month)].' '.Request::get('year',\Carbon\Carbon::now()->year)}}</h4>
        </div>
    </div>
    <!--для печати выводим таблицу с результатами без комментариев-->
    <div class='row no-screen'>
        <div class='col-xs-12'>
            <table class='table table-bordered collapsing-table'>
                <thead>
                    <tr>
                        <th>Вопрос</th>
                        <th>Да</th>
                        <th>Нет</th>
                        <th>Комментарии</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['questions'] as $q)
                    <tr class='header children-collapsed'>
                        <td>{{$q['label']}}</td>
                        <td>{{$q['yes_count']}}</td>
                        <td>{{$q['no_count']}}</td>
                        <td>{{$q['comments_count']}}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <p style='page-break-after: always'></p>
    <div class='row'>
        <div class='col-xs-12'>
            <table class='table table-bordered collapsing-table'>
                <thead>
                    <tr>
                        <th>Вопрос</th>
                        <th>Да</th>
                        <th>Нет</th>
                        <th>Комментарии</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['questions'] as $q)
                    <tr class='header children-collapsed @if($q["comments_count"]>0) pointer @endif'>
                        <td>{{$q['label']}}</td>
                        <td>{{$q['yes_count']}}</td>
                        <td>{{$q['no_count']}}</td>
                        <td>{{$q['comments_count']}}</td>
                    </tr>
                    @if($q['comments_count']>0)
                    <tr class='hidden-child-row child-row'>
                        <td colspan="4">
                            <ul class="comments-list">
                                @foreach($q['comments'] as $comment)
                                <li>{{$comment}}</li>
                                @endforeach
                            </ul>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@stop
@section('scripts')
<script>
    (function () {
        $('.collapsing-table .header').click(function () {
            var collapsedClass = 'children-collapsed';
            if ($(this).next().hasClass('child-row')) {
                if ($(this).hasClass(collapsedClass)) {
                    $(this).removeClass(collapsedClass);
                    $(this).next().removeClass('hidden-child-row');
                } else {
                    $(this).addClass(collapsedClass);
                    $(this).next().addClass('hidden-child-row');
                }
            }
        });
    })();
</script>
@stop