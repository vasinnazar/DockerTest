<div class="loan-step">
    <div class="row">
        <h4 class="col-xs-12">Юридический адрес проживания</h4>
    </div>
    <div id="registrationResidence">
        <div class="row">
            <div class="form-group col-xs-12 col-md-2">
                <label>Индекс</label>
                {!! Form::text('zip',null,array(
                'placeholder'=>'XXXXXX',
                'pattern'=>'[0-9]{6}', 'class'=>'form-control input-sm',
                'readonly'=>'readonly'
                ))!!}
            </div>
            <div class="form-group col-xs-12 col-md-5">
                <label class="control-label">Область</label>
                {!! Form::text('address_region',null,array(
                'class'=>'form-control input-sm',
                'readonly'=>'readonly'
                ))!!}
            </div>
            <div class="form-group col-xs-12 col-md-5">
                <label class="control-label">Район</label>
                {!! Form::text('address_district',null,array(
                'class'=>'form-control input-sm',
                'title'=>'Не заполнять если город - районный центр',
                'readonly'=>'readonly'
                ))!!}
            </div>
            <div class="form-group col-xs-12 col-md-6">
                <label class="control-label" >Город</label>
                {!! Form::text('address_city',null,array('class'=>'form-control input-sm','readonly'=>'readonly'))!!}
            </div>
            <div class="form-group col-xs-12 col-md-6">
                <label class="control-label" >Нас. пункт</label>
                <div class='input-group'>
                    {!! Form::text('address_city1',null,array('class'=>'form-control input-sm'))!!}
                    <span class='input-group-btn'>
                        <button class='btn btn-danger btn-sm' onclick="$.claimCtrl.clearAddressForm(this); return false;" title='Очистить юридический адрес'><span class='glyphicon glyphicon-remove-circle'></span></button>
                    </span>
                </div>
            </div>
            <div class="form-group col-xs-12 col-md-6">
                <label class="control-label" >Улица</label>
                {!! Form::text('address_street',null,array(
                'class'=>'form-control input-sm'
                ))!!}
            </div>
            <div class="form-group col-xs-12 col-md-2">
                <label class="control-label" for="address_house">Дом</label>
                {!! Form::text('address_house',null,array(
                'class'=>'form-control input-sm'
                ))!!}
            </div>
            <div class="form-group col-xs-12 col-md-2">
                <label class="control-label">Строение</label>
                {!! Form::text('address_building',null,array('class'=>'form-control input-sm'))!!}
            </div>
            <div class="form-group col-xs-12 col-md-2">
                <label class="control-label" >Квартира</label>
                {!! Form::text('address_apartment',null,['class'=>'form-control input-sm'])!!}
            </div>
        </div>
        <div class="row">
            
        </div>
    </div>
    <div id="factResidence">
        <div class="row">
            <h4 class=" col-xs-12 col-md-3">Фактический адрес проживания</h4>
            <div class="form-group col-xs-3">
                <input type="checkbox" class="checkbox"  name="copyFactAddress" id="checkbox"  value="1" onclick="$.claimCtrl.copyFactAddress(this)"> 
                <label for="checkbox">Совпадает</label>
            </div>
        </div>        
        <div class="row">
            <div class="form-group col-xs-12 col-md-2">
                <label class="control-label">Индекс</label>
                {!! Form::text('fact_zip',null,array(
                'placeholder'=>'XXXXXX',
                'pattern'=>'[0-9]{6}', 'class'=>'form-control input-sm',
                'readonly'=>'readonly'
                ))!!}
            </div>
            <div class="form-group col-xs-12 col-md-5">
                <label class="control-label">Область</label>
                {!! Form::text('fact_address_region',null,array(
                'class'=>'form-control input-sm',
                'readonly'=>'readonly'
                ))!!}
            </div>
            <div class="form-group col-xs-12 col-md-5">
                <label class="control-label">Район</label>
                {!! Form::text('fact_address_district',null,array(
                'class'=>'form-control input-sm',
                'title'=>'Не заполнять если город - районный центр',
                'readonly'=>'readonly'
                ))!!}
            </div>
        </div>
        <div class="row">
            <div class="form-group col-xs-12 col-md-6">
                <label class="control-label" >Город</label>
                {!! Form::text('fact_address_city',null,array(
                'class'=>'form-control input-sm',
                'readonly'=>'readonly'
                ))!!}
            </div>
            <div class="form-group col-xs-12 col-md-6">
                <label class="control-label" >Нас. пункт</label>
                <div class='input-group'>
                    {!! Form::text('fact_address_city1',null,array('class'=>'form-control input-sm'))!!}
                    <span class='input-group-btn'>
                        <button class='btn btn-danger btn-sm' onclick="$.claimCtrl.clearAddressForm(this); return false;" title='Очистить фактический адрес'><span class='glyphicon glyphicon-remove-circle'></span></button>
                    </span>
                </div>
            </div>
            <div class="form-group col-xs-12 col-md-6">
                <label class="control-label" >Улица</label>
                {!! Form::text('fact_address_street',null,array(
                'class'=>'form-control input-sm'
                ))!!}
            </div>
            <div class="form-group col-xs-12 col-md-2">
                <label class="control-label">Дом</label>
                {!! Form::text('fact_address_house',null,array(
                'class'=>'form-control input-sm'
                ))!!}
            </div>
            <div class="form-group col-xs-12 col-md-2">
                <label class="control-label">Строение</label>
                {!! Form::text('fact_address_building',null,array(
                'class'=>'form-control input-sm'
                ))!!}
            </div>
            <div class="form-group col-xs-12 col-md-2">
                <label class="control-label">Квартира</label>
                {!! Form::text('fact_address_apartment',null,array(
                'class'=>'form-control input-sm'
                ))!!}
            </div>
        </div>
    </div>
</div>