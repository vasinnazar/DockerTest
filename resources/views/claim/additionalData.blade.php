<div class="loan-step">
    <div class="row">
        <div class="col-xs-12 col-md-6 bordered-group">
            <div class="row">
                <h4 class="col-xs-12">Личные сведения</h4>
                <div class="form-group col-xs-12 col-md-2">
                    <label class="control-label" >Пол</label>
                    {!! Form::select('sex',['0'=>'Женский','1'=>'Мужской'],null,['class'=>'form-control input-sm']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-6">
                    <label class="control-label"  for="fioizmena">ФИО изменялось</label>
                    {!! Form::text('fioizmena',null,['class'=>'form-control input-sm','placeholder'=>'Предыдущее ФИО']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-4">
                    <label class="control-label" >Образование</label>
                    {!! Form::select('obrasovanie',$education_levels,null,['class'=>'form-control input-sm']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-4">
                    <label class="control-label"> Жилищные условия</label>
                    {!! Form::select('zhusl',$live_conditions,null,['class'=>'form-control input-sm']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-3">
                    <label  for="marital_type_id">Семейное положение</label>
                    {!! Form::select('marital_type_id',$maritaltypes,null,['class'=>'form-control input-sm']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-5">
                    <label  for="fiosuprugi">ФИО супруги(а)</label>
                    {!! Form::text('fiosuprugi',null,['class'=>'form-control input-sm']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-5">
                    <label class="control-label"  for="deti">Дети</label>
                    {!! Form::text('deti',null,['class'=>'form-control input-sm']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-4">
                    <label class="control-label"  for="telephonehome"> Телефон домашний</label>
                    {!! Form::text('telephonehome',null,['class'=>'form-control input-sm']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-3">
                    <label class="control-label">Автомобиль</label>
                    {!! Form::select('avto',['0'=>'Нет','1'=>'Отечественный','2'=>'Иномарка'],null,['class'=>'form-control input-sm']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-4">
                    <label class="control-label"  for="anothertelephone">Другой телефон<span class="required-mark">*</span></label>
                    <!--<div class="input-group">-->
                        {!! Form::text('anothertelephone',null,['class'=>'form-control input-sm','required'=>'required']) !!}
<!--                        <span class="input-group-btn">
                            <button class="btn btn-default btn-sm" onclick="$.claimCtrl.checkPhone(this);
                                return false;"><span class='glyphicon glyphicon-refresh'></span></button>
                        </span>
                    </div>-->
                </div>
                <div class="form-group col-xs-12 col-md-4">
                    <label  class="control-label" for="telephonerodstv">Телефон родственников</label>
                    <!--<div class="input-group">-->
                        {!! Form::text('telephonerodstv',null,['class'=>'form-control input-sm']) !!}
<!--                        <span class="input-group-btn">
                            <button class="btn btn-default btn-sm" onclick="$.claimCtrl.checkPhone(this);
                                    return false;"><span class='glyphicon glyphicon-refresh'></span></button>
                        </span>-->
                    <!--</div>-->
                </div>
                <div class="form-group col-xs-12 col-md-4">
                    <label  class="control-label" for="stepenrodstv">Степень родства</label>
                    {!! Form::select('stepenrodstv',$stepenrodstv,null,['class'=>'form-control input-sm']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-6">
                    <label class="control-label"  for="telephonehome"> Электронная почта</label>
                    {!! Form::text('email',null,['class'=>'form-control input-sm']) !!}
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-6 bordered-group">
            <div class="row">
                <h4 class="col-xs-12 col-md-12">Сведения о работе</h4>
                <div class="form-group col-xs-12 col-md-3">
                    <label class="control-label"  for="stazlet">Стаж (в месяцах)</label>
                    {!! Form::text('stazlet',null,['class'=>'form-control input-sm']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-5">
                    <label class="control-label"  for="dolznost">Должность</label>
                    {!! Form::text('dolznost',null,['class'=>'form-control input-sm']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-4">
                    <label class="control-label"  for="pensionnoeudost">Пенсионное удостоверение</label>
                    {!! Form::text('pensionnoeudost',null,['class'=>'form-control input-sm']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-3">
                    <label class="control-label" >Вид трудоустройства</label>
                    {!! Form::select('vidtruda',['0'=>'Не официальное','1'=>'Официальное'],null,['class'=>'form-control input-sm']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-4">
                    <label class="control-label"  for="organizacia">Название организации<span class="required-mark">*</span></label>
                    {!! Form::text('organizacia',null,['class'=>'form-control input-sm','required'=>'required']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-5">
                    <label class="control-label"  for="adresorganiz">Адрес организации</label>
                    {!! Form::text('adresorganiz',null,['class'=>'form-control input-sm']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-3">
                    <label class="control-label"  for="telephoneorganiz">Телефон организации</label>
                    {!! Form::text('telephoneorganiz',null,['class'=>'form-control input-sm']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-6">
                    <label class="control-label"  for="fiorukovoditel">ФИО Руководителя</label>
                    {!! Form::text('fiorukovoditel',null,['class'=>'form-control input-sm']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-3">
                    <label class="control-label"  for="innorganizacia">ИНН организации</label>
                    {!! Form::text('innorganizacia',null,['class'=>'form-control input-sm','max'=>10]) !!}
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-md-6 bordered-group">
            <div class="row">
                <h4 class="col-xs-12">Доп. данные</h4>
                <div class="form-group col-xs-12 col-md-6">
                    <label class="control-label" >Источники</label>
                    {!! Form::select('adsource',$adsources,null,['class'=>'form-control input-sm','data-changed'=>((is_null($claimForm->claim_id)?'0':'1'))]) !!}
                </div>
                <div class="form-group col-xs-12 col-md-6">
                    <label class="control-label" >Цель займа</label>
                    {!! Form::select('goal',$loangoals,null,['class'=>'form-control input-sm']) !!}
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-6 bordered-group">
            <div class="row">
                <h4 class="col-xs-12">Данные о доходах</h4>
                <div class="form-group col-xs-12 col-md-4">
                    <label class="control-label"  for="credit">Текущие кредиты</label>
                    {!! Form::text('credit',null,['class'=>'form-control input-sm']) !!}
                </div>
                <div class="form-group col-xs-12 col-md-2">
                    <label class="control-label"  for="dohod">Доход<span class="required-mark">*</span></label>
                    <input name="dohod" type="number" class="form-control input-sm" value="{{$claimForm->dohod}}" required />
                </div>
                <div class="form-group col-xs-12 col-md-2">
                    <label class="control-label"  for="dopdohod">Доп доход</label>
                    <input name="dopdohod" type="number" class="form-control input-sm" value="{{$claimForm->dopdohod}}" />
                </div>
                <div class="form-group col-xs-12 col-md-2">
                    <label class="control-label"  for="dohod">Доход мужа/жены</label>
                    <input name="dohod_husband" type="number" class="form-control input-sm" value="{{$claimForm->dohod_husband}}" />
                </div>
                <div class="form-group col-xs-12 col-md-2">
                    <label class="control-label"  for="dopdohod">Пенсия</label>
                    <input name="pension" type="number" class="form-control input-sm" value="{{$claimForm->pension}}" />
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-xs-12 col-md-12">
            <label class="control-label" >Сбор рекомендаций</label>
            <table class="table table-borderless">
                <tr>
                    <td>{!! Form::text('recomend_phone_1',null,['class'=>'form-control input-sm','placeholder'=>'Телефон']) !!}</td>
                    <td>{!! Form::text('recomend_fio_1',null,['class'=>'form-control input-sm','placeholder'=>'ФИО']) !!}</td>
                </tr>
                <tr>
                    <td>{!! Form::text('recomend_phone_2',null,['class'=>'form-control input-sm','placeholder'=>'Телефон']) !!}</td>
                    <td>{!! Form::text('recomend_fio_2',null,['class'=>'form-control input-sm','placeholder'=>'ФИО']) !!}</td>
                </tr>
                <tr>
                    <td>{!! Form::text('recomend_phone_3',null,['class'=>'form-control input-sm','placeholder'=>'Телефон']) !!}</td>
                    <td>{!! Form::text('recomend_fio_3',null,['class'=>'form-control input-sm','placeholder'=>'ФИО']) !!}</td>
                </tr>
            </table>
        </div>
        <div class="form-group col-xs-12 col-md-12">
            <label class="control-label" >Услугами каких других МФО пользуется клиент?</label>
            {!! Form::text('other_mfo',null,['class'=>'form-control input-sm']) !!}
        </div>
        <div class="form-group col-xs-12 col-md-12">
            <label class="control-label" >...и почему?</label>
            {!! Form::text('other_mfo_why',null,['class'=>'form-control input-sm']) !!}
        </div>
    </div>
    <br>
    <div class="row">
        <div class="form-group col-xs-12 col-md-2">
            <?php echo Form::checkbox('pensioner', 1, null, ['class' => 'checkbox', 'id' => 'checkbox5']); ?>
            <label for="checkbox5" > Пенсионер (для пенсионеров по возрасту)</label>
        </div>
        <div class="form-group col-xs-12 col-md-2">
            <?php echo Form::checkbox('postclient', 1, null, ['class' => 'checkbox', 'id' => 'checkbox6']); ?>
            <label for="checkbox6" >Пост. клиент</label>
        </div>
        <div class="form-group col-xs-12 col-md-2">
            <?php echo Form::checkbox('armia', 1, null, ['class' => 'checkbox', 'id' => 'checkbox3']); ?>
            <label for="checkbox3" >Армия</label>  
        </div>
        <div class="form-group col-xs-12 col-md-2">
            <?php echo Form::checkbox('poruchitelstvo', 1, null, ['class' => 'checkbox', 'id' => 'checkbox4']); ?>
            <label for="checkbox4" > Поручительство</label> 
        </div>
        <div class="form-group col-xs-12 col-md-3">
            <?php echo Form::checkbox('zarplatcard', 1, null, ['class' => 'checkbox', 'id' => 'checkbox2']); ?>
            <label for="checkbox2" >Наличие ЗК(зарплатной карты)</label>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-xs-12 col-md-2">
            <?php echo Form::checkbox('alco', 1, null, ['class' => 'checkbox', 'id' => 'checkbox7']); ?>
            <label for="checkbox7" >Пьяный</label>
        </div>
        <div class="form-group col-xs-12 col-md-2">
            <?php echo Form::checkbox('drugs', 1, null, ['class' => 'checkbox', 'id' => 'checkbox8']); ?>
            <label for="checkbox8" >Наркоман</label>
        </div>
        <div class="form-group col-xs-12 col-md-2">
            <?php echo Form::checkbox('stupid', 1, null, ['class' => 'checkbox', 'id' => 'checkbox9']); ?>
            <label for="checkbox9" >Недееспособен</label>
        </div>
        <div class="form-group col-xs-12 col-md-2">
            <?php echo Form::checkbox('badspeak', 1, null, ['class' => 'checkbox', 'id' => 'checkbox10']); ?>
            <label for="checkbox10" >Не связная речь</label>
        </div>
        <div class="form-group col-xs-12 col-md-4">
            <?php echo Form::checkbox('pressure', 1, null, ['class' => 'checkbox', 'id' => 'checkbox11']); ?>
            <label for="checkbox11" >Находится под давлением третьих лиц</label>
        </div>
        <div class="form-group col-xs-12 col-md-2">
            <?php echo Form::checkbox('dirty', 1, null, ['class' => 'checkbox', 'id' => 'checkbox17']); ?>
            <label for="checkbox17" >Грязный неопрятный вид</label>
        </div>
        <div class="form-group col-xs-12 col-md-2">
            <?php echo Form::checkbox('smell', 1, null, ['class' => 'checkbox', 'id' => 'checkbox12']); ?>
            <label for="checkbox12" >Неприятный резкий запах</label>
        </div>
        <div class="form-group col-xs-12 col-md-2">
            <?php echo Form::checkbox('badbehaviour', 1, null, ['class' => 'checkbox', 'id' => 'checkbox13']); ?>
            <label for="checkbox13" >Неадекватное поведение</label>
        </div>
        <div class="form-group col-xs-12 col-md-2">
            <?php echo Form::checkbox('soldier', 1, null, ['class' => 'checkbox', 'id' => 'checkbox14']); ?>
            <label for="checkbox14">Военные</label>
        </div>
        <div class="form-group col-xs-12 col-md-2">
            <?php echo Form::checkbox('watch', 1, null, ['class' => 'checkbox', 'id' => 'checkbox15']); ?>
            <label for="checkbox15" >Вахта</label>
        </div>
        <div class="form-group col-xs-12 col-md-2">
            <?php echo Form::checkbox('other', 1, null, ['class' => 'checkbox', 'id' => 'checkbox16']); ?>
            <label for="checkbox16" >Другое</label>
        </div>
    </div>
</div>