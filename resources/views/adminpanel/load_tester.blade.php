@extends('adminpanel')
@section('title') Тестирование @stop
@section('css')
<style>
    #csvFileDataHolder{
        margin-top: 20px;
    }
</style>
@stop
@section('subcontent')

<ol class="breadcrumb">
    <li><a href="{{url('adminpanel')}}">Админпанель</a></li>
    <li><a href="{{url('adminpanel/tester')}}">Тестирование</a></li>
    <li class="active">Тестирование нагрузки</li>
</ol>

<div>
    <h2>Тестирование нагрузки</h2>
    <div class='row'>
        <div class='col-xs-12 col-sm-2'>
            <form id="csvFileForm">
                <input name="csv_file" type="file" accept="text/csv" />
            </form>
        </div>
        <div class='col-xs-12 col-sm-10 form-inline'>
            <form id='soapParamsForm'>
                <select name='server_1c' class='form-control input-sm'>
                    @foreach(config('admin.servers_1c_list') as $item)
                    <option value='{{$item}}'>{{$item}}</option>
                    @endforeach
                </select>
                <input class='form-control input-sm' name='user_1c' value='KAadmin' placeholder="логин"/>
                <input type='password' class='form-control input-sm' name='password_1c'/>
                <select name='function_1c' class='form-control input-sm'>
                    @foreach(config('tester.modules_1c') as $k=>$v)
                    <optgroup label='{{$k}}'>
                        @foreach(config('tester.modules_1c')[$k]['functions'] as $f)
                        <option value='{{$f}}'>{{$f}}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
                <button type='button' class='btn btn-primary btn-sm' id='csvFileSendTo1cBtn' disabled>Отправить в 1С</button>
                <button type='button' class='btn btn-default btn-sm' id='csvFileTableRefreshBtn' disabled>Обновить таблицу</button>
                <button type='button' class='btn btn-default btn-sm' id='csvExportBtn' onclick="$.testerCtrl.exportToCsv();" disabled>Экспортировать в CSV</button>
            </form>
        </div>
    </div>
    <div id="csvFileDataHolder">

    </div>
    <hr>
</div>
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
<script src="{{asset('js/libs/papaparse.min.js')}}"></script>
<script src="{{ URL::asset('js/adminpanel/testerController.js') }}"></script>
<script>
(function () {
    $.testerCtrl.loadTestInit();
})(jQuery);
</script>
@stop