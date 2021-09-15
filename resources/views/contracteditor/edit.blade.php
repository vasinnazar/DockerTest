@extends('adminpanel')
@section('title') Редактирование шаблона договора @stop
@section('subcontent')
@if(isset($contract))
{!! Form::model($contract,['action'=>'ContractEditorController@update','method'=>'post']) !!}
{!! Form::hidden('id') !!}
@else
{!! Form::open(['action'=>'ContractEditorController@update','method'=>'post']) !!}
@endif
<div class="row">
    <div class="form-group col-xs-6 col-lg-6">
        <label>Наименование</label>
        {!! Form::text('name',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-5 col-lg-5">
        <label>Идентификатор (идентификатор прописанный в App\Http\config\options.php)</label>
        {!! Form::text('text_id',null,['class'=>'form-control']) !!}
    </div>
    <div class="form-group col-xs-1 col-lg-1">
        <label>Справка</label>
        <button class="btn btn-primary" type="button" data-toggle="collapse" 
                data-target="#helpPanel" aria-expanded="false" aria-controls="helpPanel">
            ?
        </button>
    </div>
</div>
<div class="row">
    <div class="col-xs-6 form-group">
        <label>Описание</label>
        {!! Form::text('description',null,['class'=>'form-control']) !!}
    </div>
    <div class="col-xs-6 form-group">
        <label>Имя файла шаблона (*.odt, *.ods)</label>
        {!! Form::text('tplFileName',null,['class'=>'form-control']) !!}
    </div>
</div>
<div class="collapse" id="helpPanel">
    <div class="well">
        <div class="row">
            <div class="col-xs-3">&#123;&#123;claims.summa&#125;&#125; - Сумма в заявке</div>
            <div class="col-xs-3">&#123;&#123;claims.srok&#125;&#125; - Срок в заявке</div>
            <div class="col-xs-3">&#123;&#123;claims.date&#125;&#125; - Дата создания займа</div>
            <div class="col-xs-3">&#123;&#123;claims.status&#125;&#125; - Статус заявки</div>
            <div class="col-xs-3">&#123;&#123;customers.fio&#125;&#125; - ФИО клиента</div>
            <div class="col-xs-3">&#123;&#123;customers.telephone&#125;&#125; - Телефон клиента</div>
            <div class="col-xs-3">&#123;&#123;about_clients.sex&#125;&#125; - Пол клиента</div>
            <div class="col-xs-3">&#123;&#123;about_clients.goal&#125;&#125; - Цель займа</div>
            <div class="col-xs-3">&#123;&#123;about_clients.zhusl&#125;&#125; - Жилищные условия</div>
            <div class="col-xs-3">&#123;&#123;about_clients.deti&#125;&#125; - Дети</div>
            <div class="col-xs-3">&#123;&#123;about_clients.fiosuprugi&#125;&#125; - ФИО супруги(а)</div>
            <div class="col-xs-3">&#123;&#123;about_clients.fioizmena&#125;&#125; - Предыдущее ФИО</div>
            <div class="col-xs-3">&#123;&#123;about_clients.avto&#125;&#125; - Автомобиль</div>
            <div class="col-xs-3">&#123;&#123;about_clients.telephonehome&#125;&#125; - Домашний телефон</div>
            <div class="col-xs-3">&#123;&#123;about_clients.organizacia&#125;&#125; - Организация клиента</div>
            <div class="col-xs-3">&#123;&#123;about_clients.innorganizacia&#125;&#125; - ИНН организации клиента</div>
            <div class="col-xs-3">&#123;&#123;about_clients.dolznost&#125;&#125; - Должность клиента</div>
            <div class="col-xs-3">&#123;&#123;about_clients.vidtruda&#125;&#125; - Официально или не официально работает</div>
            <div class="col-xs-3">&#123;&#123;about_clients.fiorukovoditel&#125;&#125; - ФИО руководителя организации клиента</div>
            <div class="col-xs-3">&#123;&#123;about_clients.adresorganiz&#125;&#125; - Адрес организации клиента</div>
            <div class="col-xs-3">&#123;&#123;about_clients.telephoneorganiz&#125;&#125; - Телефон организации клиента</div>
            <div class="col-xs-3">&#123;&#123;about_clients.credit&#125;&#125; - Наличие кредитов</div>
            <div class="col-xs-3">&#123;&#123;about_clients.dohod&#125;&#125; - Доход</div>
            <div class="col-xs-3">&#123;&#123;about_clients.dopdohod&#125;&#125; - Доп. доход</div>
            <div class="col-xs-3">&#123;&#123;about_clients.stazlet&#125;&#125; - Стаж</div>
            <div class="col-xs-3">&#123;&#123;about_clients.adsource&#125;&#125; - Источник рекламы</div>
            <div class="col-xs-3">&#123;&#123;about_clients.pensionnoeudost&#125;&#125; - Пенсионное удостоверение</div>
            <div class="col-xs-3">&#123;&#123;about_clients.telephonerodstv&#125;&#125; - Телефон родственников</div>
            <div class="col-xs-3">&#123;&#123;about_clients.obrasovanie&#125;&#125; - Образование</div>
            <div class="col-xs-3">&#123;&#123;loans.money&#125;&#125; - Сумма в кредитном договоре</div>
            <div class="col-xs-3">&#123;&#123;loans.time&#125;&#125; - Срок в кредитном договоре</div>
            <div class="col-xs-3">&#123;&#123;loans.created_at&#125;&#125; - Дата создания кредитного договора</div>
            <div class="col-xs-3">&#123;&#123;loans.id_1c&#125;&#125; - Номер кредитного договора</div>
            <div class="col-xs-3">&#123;&#123;orders.number&#125;&#125; - Номер РКО</div>
            <div class="col-xs-3">&#123;&#123;passports.birth_date&#125;&#125; - Дата рождения</div>
            <div class="col-xs-3">&#123;&#123;passports.birth_city&#125;&#125; - Место рождения</div>
            <div class="col-xs-3">&#123;&#123;passports.series&#125;&#125; - Серия паспорта</div>
            <div class="col-xs-3">&#123;&#123;passports.number&#125;&#125; - Номер паспорта</div>
            <div class="col-xs-3">&#123;&#123;passports.issued&#125;&#125; - Кем выдан паспорта</div>
            <div class="col-xs-3">&#123;&#123;passports.issued_date&#125;&#125; - Дата выдачи паспорта</div>
            <div class="col-xs-3">&#123;&#123;passports.subdivision_code&#125;&#125; - Код подразделения, выдавшего паспорт</div>
            <div class="col-xs-3">&#123;&#123;passports.zip&#125;&#125; - Индекс по прописке</div>
            <div class="col-xs-3">&#123;&#123;passports.address_region&#125;&#125; - Регион по прописке</div>
            <div class="col-xs-3">&#123;&#123;passports.address_district&#125;&#125; - Район по прописке</div>
            <div class="col-xs-3">&#123;&#123;passports.address_city&#125;&#125; - Город по прописке</div>
            <div class="col-xs-3">&#123;&#123;passports.address_street&#125;&#125; - Улица по прописке</div>
            <div class="col-xs-3">&#123;&#123;passports.address_house&#125;&#125; - Дом по прописке</div>
            <div class="col-xs-3">&#123;&#123;passports.address_building&#125;&#125; - Строение по прописке</div>
            <div class="col-xs-3">&#123;&#123;passports.address_apartment&#125;&#125; - Квартира по прописке</div>
            <div class="col-xs-3">&#123;&#123;passports.fact_zip&#125;&#125; - Индекс по факту</div>
            <div class="col-xs-3">&#123;&#123;passports.fact_address_region&#125;&#125; - Регион по факту</div>
            <div class="col-xs-3">&#123;&#123;passports.fact_address_district&#125;&#125; - Район по факту</div>
            <div class="col-xs-3">&#123;&#123;passports.fact_address_city&#125;&#125; - Город по факту</div>
            <div class="col-xs-3">&#123;&#123;passports.fact_address_street&#125;&#125; - Улица по факту</div>
            <div class="col-xs-3">&#123;&#123;passports.fact_address_house&#125;&#125; - Дом по факту</div>
            <div class="col-xs-3">&#123;&#123;passports.fact_address_building&#125;&#125; - Строение по факту</div>
            <div class="col-xs-3">&#123;&#123;passports.fact_address_apartment&#125;&#125; - Квартира по факту</div>
            <div class="col-xs-3">&#123;&#123;passports.address_reg_date&#125;&#125; - Дата прописки</div>
            <div class="col-xs-3">&#123;&#123;subdivisions.name&#125;&#125; - Название поразделения</div>
            <div class="col-xs-3">&#123;&#123;subdivisions.name_id&#125;&#125; - Код подразделения</div>
            <div class="col-xs-3">&#123;&#123;subdivisions.address&#125;&#125; - Адрес подразделения</div>
            <div class="col-xs-3">&#123;&#123;subdivisions.peacejudge&#125;&#125; - Мировой судья</div>
            <div class="col-xs-3">&#123;&#123;subdivisions.districtcourt&#125;&#125; - Районный суд</div>            
            <div class="col-xs-3">&#123;&#123;users.name&#125;&#125; - ФИО текущего пользователя системы</div>            
            <div class="col-xs-3">&#123;&#123;users.doc&#125;&#125; - Удостоверяющий документ пользователя системы</div>      
            <div class="col-xs-3">&#123;&#123;loantypes.name&#125;&#125; - Название вида займа</div>      
            <div class="col-xs-3">&#123;&#123;loantypes.percent&#125;&#125; - Процентная ставка в день</div>      
            <div class="col-xs-3">&#123;&#123;moneyAndPercents&#125;&#125; - Сумма займа с процентами</div>       
            <div class="col-xs-3">&#123;&#123;moneyPercents&#125;&#125; - Сумма процентов</div>       
            <div class="col-xs-3">&#123;&#123;yearPercent&#125;&#125; - Годовая процентная ставка</div>       
            <div class="col-xs-3">&#123;&#123;loanEndDate&#125;&#125; - Дата окончания кредитного договора</div>       
            <div class="col-xs-3">&#123;&#123;expirationPercent&#125;&#125; - Процент на время просрочки</div>    
            <div class="col-xs-3">&#123;&#123;finePercent&#125;&#125; - Процент пени</div>
            <div class="col-xs-3">&#123;&#123;date_type&#125;&#125; - вставляет слово обновление\создание по дате анкеты</div>
            <div class="col-xs-3">&#123;&#123;num2str(идентификатор)&#125;&#125; - Возвращает переданное число прописью</div>
            <div class="col-xs-3">&#123;&#123;full_address&#125;&#125; - полный юридический адрес</div>
            <div class="col-xs-3">&#123;&#123;full_fact_address&#125;&#125; - полный фактический адрес</div>
            <div class="col-xs-3">&#123;&#123;generateGrid()&#125;&#125; - сетка</div>
        </div>
    </div>
</div>
<div class="form-group">
    <label>Шаблон</label>
    {!! Form::textarea('template',null,['class'=>'form-control']) !!}
</div>
<div class="form-group">
    {!! Form::submit('Сохранить',['class'=>'btn btn-primary pull-right']) !!}
</div>
{!! Form::close() !!}
@stop
@section('scripts')
<script src="{{ URL::asset('js/contracteditor/contractEditorController.js') }}"></script>
<script>
(function () {
    $.contrEditorCtrl.init();
})(jQuery);
</script>
@stop