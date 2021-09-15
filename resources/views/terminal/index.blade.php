@extends('app')
@section('title') Тест терминала @stop
@section('content')
<div class="form-group form-inline">
    <label>ID</label>
    <input class="form-control" id="terminalID" name="ID" value="27" />
    <label>hardwareID</label>
    <input class="form-control" id="terminalHID" name="hardwareID" value="23423423" />
    <label>password</label>
    <input class="form-control" id="terminalPASS" name="password" value="123" />
    <label>telephone</label>
    <input class="form-control" id="terminalPHONE" name="telephone" value="79131234545" />
    <label>PIN</label>
    <input class="form-control" id="terminalPIN" name="pin" value="0000" />
</div>
<div class='btn-group'>
    <button class='btn btn-default' onclick='preauth();'>PREAUTH</button>
    <button class='btn btn-default' onclick='status();'>STATUS</button>
    <button class='btn btn-default' onclick='auth();'>AUTH</button>
    <button class='btn btn-default' onclick='pinauth();'>PINAUTH</button>
</div>
<script>
    function preauth() {
        $.post(armffURL + 'terminal/preauth', {ID: $('#terminalID').val(), hardwareID: $('#terminalHID').val()}).done(function (data) {
//            console.log(data, 'onpreauth');
//            var AuthState = 0;
//            if (data) {
//                if (data == $('#terminalPASS').val()) {
//                    AuthState = 0;
//                } else {
//                    AuthState = 1;
//                }
//            } else {
//                AuthState = 2;
//            }
            $.post(armffURL + 'terminal/preauth2', {md5hash: '', hardwareID: $('#terminalHID').val()}).done(function (data) {
                console.log(data, 'onpreauth2');
            });
        });
    }
    function status() {
        $.post(armffURL + 'terminal/status', {AppVer: '1.0.0.2', TerminalState: '0|0|0|0|0|0'}).done(function (data) {
            console.log(data, 'onstatus');
        });
    }
    function auth() {
        $.post(armffURL + 'terminal/auth', {telephone: $('#terminalPHONE').val()}).done(function (data) {
            console.log(data, 'onauth');
        });
    }
    function pinauth() {
        $.post(armffURL + 'terminal/pinauth', {telephone: $('#terminalPHONE').val(), pin: $('#terminalPIN').val()}).done(function (data) {
            console.log(data, 'pinauth');
        });
    }
</script>
@stop