<div class="modal fade" id="findCustomersModal" tabindex="-1" role="dialog" aria-labelledby="findCustomersModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="findCustomersModalLabel">Введите данные клиента</h4>
            </div>
            {!!Form::open(['route'=>'ajax.customers.find','method'=>'get'])!!}
            <div class="modal-body">
                <div class="row">
                    <div class="form-group-sm col-xs-6">
                        <label>Серия</label>
                        {!! Form::text('series',null,array(
                        'placeholder'=>'XXXX',
                        'pattern'=>'[0-9]{4}', 'class'=>'form-control',
                        'data-minlength'=>'4','maxlength'=>'4'
                        ))!!}
                    </div>
                    <div class="form-group-sm col-xs-6">
                        <label>Номер</label>
                        {!! Form::text('number',null,array(
                        'placeholder'=>'XXXXXX',
                        'pattern'=>'[0-9]{6}', 'class'=>'form-control',
                        'data-minlength'=>'6', 'maxlength'=>'6'
                        ))!!}
                    </div>
                    <div class="form-group-sm col-xs-12">
                        <label>ФИО</label>
                        {!! Form::text('fio',null,['class'=>'form-control']) !!}
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-xs-12">
                        <div class="list-group" id="findCustomersResult">

                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                {!!Form::button('Найти',['class'=>'btn btn-primary','type'=>'submit'])!!}
            </div>
            {!!Form::close()!!}
        </div>
    </div>
</div>