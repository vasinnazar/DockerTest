@extends('adminpanel')
@section('title') Логи @stop
@section('css')@stop
@section('subcontent')
<?php

use App\Spylog\Spylog;

$tnamesLength = count(Spylog::$tablesNames);
?>
<div class="row" id="spylogFilter">
    <div class="col-xs-1"><input class="form-control input-sm" name="date_from" placeholder="Дата с"/></div>
    <div class="col-xs-1"><input class="form-control input-sm" name="date_to" placeholder="Дата до"/></div>
    <div class="col-xs-2"><input class="form-control input-sm" name="name" placeholder="Имя пользователя"/></div>
    <div class="col-xs-2">
        <select name="table" class="form-control input-sm">
            <option value="-1">Любая</option>
            @for($i=0;$i<$tnamesLength;$i++)
            <option value="{{$i}}">{{Spylog::$tablesNames[$i]}}</option>
            @endfor
        </select>
        <!--<input class="form-control input-sm" name="table" placeholder="Имя таблицы"/>-->
    </div>
    <div class="col-xs-2"><input class="form-control input-sm" name="doc_id" placeholder="ID документа"/></div>
    <div class="col-xs-2">
        <select name="action" class="form-control input-sm">
            <option value="">Действие</option>
            <?php
            for ($i = 0; $i < count($actions); $i++) {
                echo '<option value="' . $i . '">' . $actions[$i] . '</option>';
            }
            ?>
        </select>
    </div>
    <div class="col-xs-2">
        <button onclick="$.spylogCtrl.filterLogs();
                return false;" class="btn btn-primary">
            <span class="glyphicon glyphicon-search"></span>
        </button>
    </div>
</div>
<table class="table table-condensed table-borderless compact" id="spylogTable">
    <thead>
        <tr>
            <th>Дата/Время</th>
            <th>Пользователь</th>
            <th>Таблица</th>
            <th>Документ</th>
            <th>Действие</th>
            <th>Просмотр</th>
        </tr>
    </thead>
</table>

<div class="modal fade" id="viewLogModal" tabindex="-1" role="dialog" aria-labelledby="viewLogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="viewLogModalLabel">Лог</h4>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

@stop
@section('scripts')
<script src="{{ URL::asset('js/spylog/spylogController.js') }}"></script>
<script>
            (function () {
                $.spylogCtrl.init();
            })(jQuery);
</script>
@stop