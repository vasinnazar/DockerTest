<div class="modal fade" id="searchClaimModal" tabindex="-1" role="dialog" aria-labelledby="searchClaimModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            {!!Form::open(['method'=>'get','class'=>'form-horizontal', 'route'=>'claims.list2'])!!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="searchClaimModalLabel">Поиск заявки</h4>
            </div>
            <div class="modal-body">

                @if(Auth::user()->isAdmin())
                <div class="form-group">
                    <label class="col-sm-2 control-label">ID</label>
                    <div class="col-sm-10">
                        {!! Form::text('loan_id',null,['class'=>'form-control'])!!}
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
                        {!! Form::text('telephone',null,[
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
            </div>
            <div class="modal-footer">
                {!!Form::button('<span class="glyphicon glyphicon-search"></span> Поиск', ['class'=>'btn btn-primary','type'=>'submit'])!!}
            </div>
            {!!Form::close()!!}
        </div>
    </div>
</div>