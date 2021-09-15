@extends('app')
@section('title') Смена подразделения @stop
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Сменить подразделение</div>
                <div class="panel-body">
                    @if (count($errors) > 0)
                    <div class="alert alert-danger">
                        <strong>Внимание!</strong> Сводка по ошибкам:<br><br>
                        <ul>
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>

                            @endforeach
                        </ul>
                    </div>
                    @endif

                    @if (!empty(Session::get('error_sub')))
                    <div class="alert alert-danger">
                        <strong>Внимание!</strong> Такого подразделения не существует. 
                        Попробуйте ввести еще раз. Если не получится позвоните в тех поддержку.
                    </div>
                    @endif

                    @if (!empty(Session::get('Subdivision')))
                    <div class="alert alert-info">
                        {!!Form::open(['route'=>'subdivisions.update','method'=>'POST'])!!}
                        <strong>Внимание!</strong> Ваше подразделение будет изменено на: <b>{{ Session::get('Subdivision') }}</b>
                        <div class="row">
                            <div class="col-xs-12">
                                <button type="submit" class="btn btn-primary pull-right">Подтвердить</button>
                            </div>
                        </div>
                        {!!Form::close()!!}
                    </div>
                    @endif



                    {!! Form::open(['route'=>'subdivisions.process', 'class'=>'form-inline']) !!}
                    <div class="form-group">
                        <label class="control-label">Введите код подразделения</label>
                        <input style="min-width: 250px" type="text" class="form-control" placeholder="Введите код с вашей печати" name="sub_code" value="{{ old('sub_code') }}"/>
                        <button type="submit" class="btn btn-primary">Сменить подразделение</button>
                    </div>
                    {!! Form::close() !!}
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@stop