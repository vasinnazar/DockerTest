<div class="modal-body">
    {!!Form::open(['method'=>'post','class'=>'form-horizontal'])!!}
    {!! Form::hidden('without1c',0)!!}
    @if(Auth::user()->isAdmin())
    <div class="form-group">
        <label class="col-sm-2 control-label">ID</label>
        <div class="col-sm-10">
            {!! Form::text('loan_id',null,['class'=>'form-control'])!!}
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">ID_1C</label>
        <div class="col-sm-10">
            {!! Form::text('loan_id_1c',null,['class'=>'form-control'])!!}
        </div>
    </div>
    @endif
    <div class="form-group">
        <label class="col-sm-2 control-label">ФИО</label>
        <div class="col-sm-10">
            {!! Form::text('fio',null,[
            'placeholder'=>'','class'=>'form-control'
            ])!!}
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">Телефон</label>
        <div class="col-sm-10">
            {!! Form::text('tele',null,[
            'placeholder'=>'','class'=>'form-control'
            ])!!}
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">Серия</label>
        <div class="col-sm-10">
            {!! Form::text('series',null,array(
            'placeholder'=>'XXXX',
            'pattern'=>'[0-9]{4}', 'class'=>'form-control',
            'data-minlength'=>'4','maxlength'=>'4'
            ))!!}
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">Номер</label>
        <div class="col-sm-10">
            {!! Form::text('number',null,array(
            'placeholder'=>'XXXXXX',
            'pattern'=>'[0-9]{6}', 'class'=>'form-control',
            'data-minlength'=>'6', 'maxlength'=>'6'
            ))!!}
        </div>
    </div>
    @if(Auth::user()->isAdmin())
    <div class="form-group">
        <label class="col-sm-3 control-label">Подразделение</label>
        <div class="col-sm-9">
            <input type='hidden' name='subdivision_id' />
            <input type='text' name='subdivision_id_autocomplete' class='form-control' data-autocomplete='subdivisions' />
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label">Дата</label>
        <div class="col-sm-9">
            <input type='date' name='date_start' class='form-control' />
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-6 control-label">Показать терминальные</label>
        <div class="col-sm-6">
            {!! Form::checkbox('onlyTerminals',1,null,['class'=>'checkbox','id'=>'cbOnlyTerminals'])!!}
            <label for="cbOnlyTerminals"></label>
        </div>
    </div>
    @endif
    <div class="form-group">
        <label class="col-sm-6 control-label">Заявка оформлена на другом подразделении</label>
        <div class="col-sm-6">
            {!! Form::checkbox('anotherSubdivision',1,null,['class'=>'checkbox','id'=>'cbAnotherSubdivision'])!!}
            <label for="cbAnotherSubdivision"></label>
        </div>
    </div>
    {!!Form::close()!!}
</div>