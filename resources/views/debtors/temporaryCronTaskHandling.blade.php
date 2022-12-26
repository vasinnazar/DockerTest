@extends('app')
@section('title') Исправления @stop
@section('css')
<style>
    .debtors-frame{
        height: 250px;
        overflow-y:scroll;
    }
    .debtors-table-frame{
        border: 1px solid #ccc;
        padding: 10px;
    }
</style>
@stop
@section('content')
@if ($message)
<div class="alert alert-success" role="alert">
    {{ $message }}
</div>
@endif
<div class="row">
    <div class="col-xs-12">
        <table class="table table-condensed table-striped table-bordered">
            <tr>
                <td style="padding-right: 20px;"><a href="/debtors/temporary/cron/handle?action=od_closing" class="btn btn-primary">Реестр</a></td>
                <td style="padding-right: 20px;"><a href="/debtors/temporary/cron/handle?action=base_b0" class="btn btn-primary">Б-0 на Б-1</a></td>
                <td><a href="/debtors/temporary/cron/handle?action=omicron" class="btn btn-primary">Автоинформатор</a></td>
            </tr>
        </table>
    </div>
</div>
@stop
