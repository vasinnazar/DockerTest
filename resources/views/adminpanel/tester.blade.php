@extends('adminpanel')
@section('title') Тестирование @stop
@section('subcontent')
<div>
    <h2>Тестирование карт</h2>
    <form id="cardCheckForm">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Номер карты" maxlength="13" name="card_number">
            <span class="input-group-btn">
                <button class="btn btn-default" type="submit">Проверить карту</button>
            </span>
        </div>
    </form>
    <hr>
    <h2>Тестирование</h2>
    <form id="testButtonForm">
        <button class="btn btn-default" type="submit">Тыц</button>
    </form>
    <hr>
    <div>
        <h2>Тестирование запросов в 1С</h2>
        <table id="exchangeArmDataTable" class="table">
            <thead>
                <tr>
                    <th>Тег</th>
                    <th>Значение</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input name="tag" class="form-control"/></td>
                    <td><input name="val" class="form-control"/></td>
                    <td style="min-width: 100px;">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-default" name="add"><span class="glyphicon glyphicon-plus"></span></button>
                            <button class="btn btn-default" name="remove"><span class="glyphicon glyphicon-trash"></span></button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class='well'>
            <label>Результат отправки XML</label>
            <textarea id='exchangeArmResponse' class='form-control'>
            
            </textarea>
        </div>
        <form id="sendTestExchangeForm">
            <div class='form-group'>
                <label>Модуль в который отправляется запрос:</label>
                <select name='module' class='form-control'>
                    <option value="Exchange">Exchange</option>
                    <option value="Mole">Mole</option>
                </select>
            </div>
            <div class='form-group'>
                <label>Заполнять если нужно отправить XML вручную:</label>
                <textarea name='rawXml' class='form-control'></textarea>
            </div>
            <input id='exchangeArmData' type='hidden' name='data'/>
            <br>
            <button class="btn btn-default pull-right" type="submit">Отправить</button>
        </form>
        <br>
        <hr>
    </div>
</div>

@stop
@section('scripts')
<script src="{{ URL::asset('js/adminpanel/adminpanelController.js') }}"></script>
<script>
(function () {
    $.adminCtrl.init();

    $('#cardCheckForm').submit(function (e) {
        e.preventDefault();
        $.post($.app.url + '/ajax/cards/check', $(this).serialize()).done(function (data) {
            console.log(data);
            if (data.result) {
                $.app.openErrorModal('Внимание', data.comment);
            } else {
                $.app.openErrorModal('Ошибка!', data.error);
            }
        });
    });

    var exchangeArmDataGrid = new DataGridController($('#sendTestExchangeForm [name="data"]').val(), {
        table: $("#exchangeArmDataTable"),
        form: $('#sendTestExchangeForm'),
        dataField: $('#sendTestExchangeForm [name="data"]')
    });

    $('#sendTestExchangeForm').submit(function (e) {
        e.preventDefault();
        $.post($.app.url + '/ajax/adminpanel/tester/exchangearm', $(this).serialize()).done(function (data) {
            $('#exchangeArmResponse').val(data);
        });
    });
    $('#testButtonForm').submit(function(e){
        e.preventDefault();
        $.post($.app.url+'/ajax/adminpanel/tester/button').done(function(data){
            console.log(data);
        });
    });
})(jQuery);
</script>
@stop