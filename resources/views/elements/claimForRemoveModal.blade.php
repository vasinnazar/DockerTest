<div class="modal fade" id="claimForRemoveModal" tabindex="-1" role="dialog" aria-labelledby="claimForRemoveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="claimForRemoveModalLabel">Подать заявку на удаление</h4>
            </div>
            {!!Form::open(['route'=>'ajax.removeRequests.claim','method'=>'post'])!!}
            <div class="modal-body">
                <div class="row">
                    <div class="form-group-sm col-xs-12">
                        <label>Комментарий</label>
                        {!! Form::textarea('comment',null,['class'=>'form-control'])!!}
                        {!! Form::hidden('id') !!}
                        {!! Form::hidden('doctype') !!}
                    </div>
                </div>
                <br>
            </div>
            <div class="modal-footer">
                {!!Form::button('Сохранить',['class'=>'btn btn-primary','type'=>'submit'])!!}
            </div>
            {!!Form::close()!!}
        </div>
    </div>
</div>