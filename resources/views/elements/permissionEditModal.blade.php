<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="permissionModal" aria-hidden="true" id="permissionModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Новое разрешение</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="form-group col-xs-12">
                        {!! Form::model(new \App\Permission(),['url'=>url('/adminpanel/permissions/update/ajax')])!!}
                        {!! Form::hidden('id') !!}
                        {!! Form::hidden('ajax',1) !!}
                        <div class='form-group'>
                            <label>Идентификатор</label>
                            <div class="row">
                                <div class="col-xs-12 col-lg-3">
                                    {!! Form::select('action',\App\Permission::getActionsList(),null,['class'=>'form-control']) !!}
                                </div>
                                <div class="col-xs-12 col-lg-3">
                                    {!! Form::select('subject',\App\Permission::getSubjectsList(),null,['class'=>'form-control']) !!}
                                </div>
                                <div class="col-xs-12 col-lg-3">
                                    {!! Form::select('condition',\App\Permission::getConditionsList(),null,['class'=>'form-control']) !!}
                                </div>
                                <div class="col-xs-12 col-lg-3">
                                    {!! Form::select('time',\App\Permission::getTimeList(),null,['class'=>'form-control']) !!}
                                </div>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label>Описание</label>
                            {!! Form::text('description',null,['class'=>'form-control'])!!}
                        </div>
                        <div class='form-group'>
                            <button class='btn btn-primary pull-right' type='submit'>Сохранить</button>
                        </div>
                        {!! Form::close()!!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>