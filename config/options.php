<?php

return [
    //идентификатор формы для договора займа
    'loan_contract' => 'loan',
    //идентификатор формы для анкеты заявки
    'claim_contract' => 'claim',
    //идентификатор формы расходно-кассового ордера
    'orderRKO' => 'order1',
    //идентификатор формы приходно-кассового ордера
    'orderPKO' => 'orderPKO',
    //идентификатор сетки
    'grid' => 'grid',
    //идентификатор ежедневного отчёта
    'dailycashreport' => 'dailycashreport',
    //бланк заявления для клиентов
    'blank_customer' => 'blank_customer',
    //бланк заявления для сотрудников
    'blank_user' => 'blank_user',
    //форма договора погашения
    'repayment' => 'repayment',
    //форма договора НПФ
    'npf' => 'npf',
    //форма уведомления изменения процентов
    'pc_notification' => 'pc_notification',
    //форма расчетного листа
    'paysheet' => 'paysheet',
    //форма реестра документов
    'docsregister' => 'docsregister',
    //форма реестра документов сфп
    'docsregister_sfp' => 'docsregister_sfp',
    //форма памятки
    'pay_reminder' => 'pay_reminder',
    //форма отчета по уки
    'uki_report' => 'uki_report',
    
    //пенсионный возраст для мужчин
    'man_retirement_age' => 60,
    //пенсионный возраст для женщин
    'woman_retirement_age' => 55,
    //количество займов для того чтобы стать постоянным клиентом
    'regular_client_loansnum' => 3,
    //процент просрочки для постоянных клиентов
    'pensioner_percent' => 1.50,
    //процент просрочки для постоянных клиентов
    'exp_percent_perm' => 2,
    //процент просрочки
    'exp_percent' => 5,
    //штрафной процент
    'fine_percent' => 20,
    //штрафной процент
    'fine_percent_daily' => 0.1,
    //штрафной процент для постоянных клиентов
    'fine_percent_perm' => 20,
    //срок действия одобренной заявки в днях
    'claim_exp_time' => 30,
    //процент просрочки по платежам в мировом соглашении
    'peace_pay_exp_percent' => 5,
    //процент просрочки по платежам в мировом соглашении для постоянных
    'peace_pay_exp_percent_perm' => 2,
    //сумма скидки по промокоду в копейках
    'promocode_discount' => 50000,
    'promocode_activate_num' => 3,
    //если пользователь не заходит более этого количества дней, то пользователь блокируется
    'days_until_user_block' => 7,
    //если в типе кредитника стоит галочка "базовая ставка", то процент берется из этого массива, в зависимости от суммы займа. суммы займов в рублях
    'basic_rate' => [
        [
            'min' => 1000,
            'max' => 16000,
            'percent' => 2
        ],
        [
            'min' => 16000,
            'max' => 31000,
            'percent' => 1.5
        ],
        [
            'min' => 31000,
            'max' => 51000,
            'percent' => 1
        ],
    ],
    //значение по-умолчанию конечной суммы для сетки платежей
    'grid_def_money' => 25000,
    //значение по-умолчанию конечного времени для сетки платежей
    'grid_def_time' => 30,
    //путь до библиотеки, генерирующей пдфки
    'wkhtmltopdf_path' => 'C:\\wkhtmltopdf\bin\wkhtmltopdf.exe',
    //айпи компа для доступа к фоткам, вставляется в путь, который пересылается в 1ску
    'photos_path' => '\\\192.168.1.123\\images\\',
    'photos_terminal_path' => '\\\192.168.1.123\\terminal\\',
    //ИДЕНТИФИКАТОРЫ ДЛЯ ТИПОВ ПОГАШЕНИЙ
    //заявление о приостановке
    'rtype_claim' => 'claim',
    'rtype_claim2' => 'claim_290316',
    'rtype_claim3' => 'dop_commission',
    //допник
    'rtype_dopnik' => 'dopnik',
    //допник
    'rtype_dopnik2' => 'dopnik2',
    //допник
    'rtype_dopnik3' => 'dopnik3',
    //допник
    'rtype_dopnik4' => 'dopnik4',
    //просроченный допник
    'rtype_dopnik5' => 'dopnik5',
    //допник с разными формами на нал и на карту
    'rtype_dopnik6' => 'dopnik6',
    //просроченный допник под 2.17
    'rtype_dopnik7' => 'dopnik7',
    //закрытие
    'rtype_closing' => 'closing',
    //мировое
    'rtype_peace' => 'peace',
    'rtype_peace2' => 'peace2',
    'rtype_peace3' => 'peace3',
    'rtype_peace4' => 'peace4',
    
    'rtype_suzstock1' => 'suzstock1',
    'rtype_suzstock2' => 'suzstock2',
    'suz_arhiv_ub' => 'АрхивУбытки',
    //оплата по сус
    'rtype_suz1' => 'suz1',
    'rtype_suz2' => 'suz2',
    //необходимое количество дней просрочки для создания заявления
    'days_to_claim' => 30,
    'suz_days' => 10000,
    //дата ввода новых правил
    'new_rules_day' => '2016-03-29',
    //дата ввода правил по которым постоянные клиенты платят просроченные проценты так же как новые
    'perm_new_rules_day' => '2016-05-20',
    //дата ввода нового мирового для договоров до ввода новых правил
    'new_peace_day' => '2016-08-22',
    //дата ввода нового мирового для договоров до ввода новых правил
    'card_nal_dop_day' => '2016-11-16 12:00:00',
    
    'new_rules_day_010117'=>'2017-01-01',    
    
    'new_rules_loantype_id' => 20,
    'max_dopnik_time'=>30,
    'dopnik_loantype'=>'ARM000009',
    'office_subdivision_id'=>'113',
    'uki_days'=>20,
    'uki_money'=>59000,
    'uki_agreement'=>'uki_agreement',
    'terminal_promo_discount'=>100,
    
    'offline'=>1,

    //Урл к сервису с электронными архивами
    'archive'=>'http://10.17.18.10:8087/documents?'
];
