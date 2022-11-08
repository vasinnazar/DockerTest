<?php

use App\EmailMessage;
use App\Role;
use Illuminate\Database\Seeder;

class EmailsMessagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $arrayMessageDebtorsRemote = [
            '        Уважаемый клиент, срочно оплатите задолженность по договору потребительского займа. Вам начисляются проценты и пени. Позвоните нам и мы расскажем, как решить проблему. Телефон {{company_phone}}, звонок бесплатный, {{company_new_name}}.
{{fio_spec}}
Специалист управления персональных решений
{{company_new_name}}.',
            '        Здравствуйте! {{company_new_name}} напоминает о необходимости оплаты просроченной задолженности по договору потребительского займа, Вам предоставляется возможность фиксации суммы задолженности при её частичной оплате и дальнейшее погашение частями. Подробности по телефону {{company_phone}}, звонок бесплатный.
{{fio_spec}}
Специалист управления персональных решений
{{company_new_name}}.',
            '        Здравствуйте! Уведомляем Вас о необходимости погасить задолженность по договору в течении 3-х дней с момента получения настоящего сообщения.  Сообщаем, что сведения о текущем размере задолженности, возникшей по договору потребительского займа, заключенного с Компанией, получатель финансовой услуги может узнать непосредственно в «Личном кабинете», расположенном на официальном сайте Компании в сети «Интернет» по адресу: {{company_site}}, а также в ближайшем отделении Компании, список отделений с адресами размещен в сети «Интернет» по адресу: {{company_site}}., либо позвонить на номер горячей линии {{company_phone}}. Звонок бесплатный {{company_new_name}}.
{{fio_spec}}
Специалист управления персональных решений
{{company_new_name}}.',
            '       Здравствуйте! В настоящее время у Вас имеются не исполненные обязательства перед {{company_new_name}}. Необходимо оплатить задолженность по договору в течении 3-х дней с момента получения настоящего сообщения . Согласно ч.1 ст. 810 ГК РФ, Заемщик обязан возвратить Займодавцу полученную сумму займа в срок и  в порядке, которые предусмотрены договором займа. Согласно Условиям Договора потребительского займа, Заемщик обязуется вернуть сумму займа и начисленные проценты единовременным платежом в дату, указанную в п.2 индивидуальных условий. Согласно Условиям Договора потребительского займа сумма Вашей задолженности ежедневно увеличивается. Текущий размер задолженности Вы можете узнать непосредственно в «Личном кабинете», расположенном на официальном сайте Компании в сети «Интернет» по адресу: {{company_site}}, а также в ближайшем отделении Компании, список отделений с адресами размещен в сети «Интернет» по адресу: {{company_site}}, либо позвонить на номер горячей линии {{company_phone}}. Звонок бесплатный {{company_new_name}}.
{{fio_spec}}
Специалист управления персональных решений
{{company_new_name}}.',
            '        Здравствуйте! В настоящее время у Вас имеются не исполненные обязательства перед {{company_new_name}}.  Необходимо оплатить задолженность по договору в течении 3-х дней с момента получения настоящего сообщения . В случае существенного изменения обстоятельств, возникших у Вас на дату возврата займа по Условиям договора, а также, отсутствия в настоящее время возможностей по оплате задолженности в полном объеме, мы готовы рассмотреть Вашу ситуацию в индивидуальном порядке. Более точную информацию относительно заключения соглашения об урегулирования задолженности, вы можете уточнить, позвонив по бесплатному номеру горячей линии: {{company_phone}}, где Вас соединят с нужным специалистом.
{{fio_spec}}
Специалист управления персональных решений
{{company_new_name}}.',
            '        Здравствуйте! Напоминаем о задолженности по Графику договора потребительского займа {{company_new_name} {{company_phone}}
{{fio_spec}}
Специалист управления персональных решений
{{company_new_name}}.',
            '        Здравствуйте! В личном кабинете {{company_site}} условия рассрочки долга {{company_new_name}} {{company_phone}}
{{fio_spec}}
Специалист управления персональных решений
{{company_new_name}} ',
            '        Здравствуйте! Для урегулирования вопроса задолженности, продлите срок по договору потребительского займа в в «Личном кабинете» на 30 дней,  расположенном на официальном сайте Компании в сети «Интернет» по адресу: {{company_site}}, а также в ближайшем отделении Компании, список отделений с адресами размещен в сети «Интернет» по адресу: {{company_site}}.
{{fio_spec}}
Специалист управления персональных решений
{{company_new_name}}',
            '        Здравствуйте! Перезвоните. Варианты погашения долга. {{company_new_name}} {{company_phone}}
{{fio_spec}}
Специалист управления персональных решений
{{company_new_name}}',
            '        Здравствуйте! Оплатите задолженность до {{date_payment}}. После закрытия договора, Вы можете подать заявку на новый займ! {{company_new_name}} {{company_phone}}
{{fio_spec}}
Специалист управления персональных решений
{{company_new_name}}',
            '        Здравствуйте! Вам необходимо оплатить задолженность в течении 24 часов с момента получения сообщения! Связаться с Компанией для уточнения суммы задолженности Вы можете по телефону {{company_phone}} {{company_new_name}} 
{{fio_spec}}
Специалист управления персональных решений
{{company_new_name}}',
            '        Здравствуйте! Взыскание долга передано Управлению персональных решений {{company_new_name}} {{company_phone}}
{{fio_spec}}
Специалист управления персональных решений
{{company_new_name}}',
            '        Здравствуйте! Уведомляем о инициировании передачи взыскания задолженности в Управление досудебного розыска {{company_new_name}} {{company_phone}}
{{fio_spec}}
Специалист управления персональных решений
{{company_new_name}} ',
            '        Здравствуйте! Реквизиты для оплаты долга: 
ОГРН 1114205007443
ИНН 4205219217 / КПП 540601001
К/с 30101810200000000612 в Отделении №8615 Сбербанка России г. Кемерово
Р/с 40701810026000000039
БИК 043207612

{{fio_spec}}
Специалист управления персональных решений
{{company_new_name}} {{company_phone}}',
            '        Здравствуйте! В настоящее время у Вас имеются неисполненные обязательства перед {{company_new_name}}. 
Напоминаем Вам о способах взаимодействия согласно ФЗ от 03.07.2016 N 230- Глава 2, статья 4, пункт 1, кредитор, вправе взаимодействовать с должником, используя:
1) личные встречи, телефонные переговоры (непосредственное взаимодействие);
Предлагаем Вам решить вопрос по урегулированию задолженности до {{date_payment}}, оставаясь в рамках правого поля, путем телефонных переговоров и исключить личные встречи (с целью экономии Вашего времени) для заключения мирового соглашения. Соглашение Вам доступно в «Личном кабинете», расположенном на официальном сайте Компании в сети «Интернет» по адресу: {{company_site}}. Вы можете позвонить по бесплатному номеру горячей линии: {{company_phone}}, где Вас соединят с нужным специалистом.
С Вами взаимодействует {{fio_spec}},
Специалист управления персональных решений
{{company_new_name}} '

        ];


        for($i = 1; $i <= count($arrayMessageDebtorsRemote);$i++){
            EmailMessage::create([
                'name' => '1.' . $i,
                'role_id' => Role::DEBTORS_REMOTE,
                'template_message' => $arrayMessageDebtorsRemote[$i -1],
            ]);
        }

        $arrayMessageDebtorsPersonal = [
            '        Здравствуйте! У Вас имеется просроченная задолженность!
Вам направлено контрольное сообщение. {{company_new_name}} , уведомляет Вас о том, что истекает срок добровольной оплаты задолженности по договору потребительского займа.  В Ваших интересах решить вопрос с задолженностью. Не усугубляйте свое положение! Когда Вы намерены погасить задолженность?
После оплаты задолженности Вы снова сможете пользоваться услугами компании. 
С Вами взаимодействует {{fio_spec}},
Специалист {{company_new_name}}. 
Телефон бесплатной горячей линии {{company_phone}}',
            '        {{fio_debtors}}, здравствуйте!
Ваша задолженность не погашена! {{company_new_name}}  требует вернуть денежные средства в течение 3-х дней. В случае неисполнения обязательств компания будет вынуждена обратиться в суд для принудительного взыскания задолженности. В ближайшее время компания направит Вам требование по месту регистрации и проживания о погашении задолженности. Каким образом Вы намерены решать вопрос?
С Вами взаимодействует {{fio_spec}},
Специалист {{company_new_name}} 
Телефон бесплатной горячей линии {{company_phone}}',
            '         Здравствуйте! Вам доступна СКИДКА при единовременном закрытии долга!
Вместо {{debtor_money_on_day}} руб. на сегодня, внесите {{discount_payment}} руб.  Предложение доступно до «{{date_payment}}».
КОГДА ПОСТУПИТ ОПЛАТА? Отправляю договор для корректировки суммы в личном кабинете. 
ДАЙТЕ ОТВЕТ до «{{date_answer}}»
С Вами взаимодействует   {{fio_spec}},
Специалист {{company_new_name}} 
Телефон бесплатной горячей линии {{company_phone}}',
            '         Здравствуйте! Задолженность не погашена!
Отделом взыскания зафиксирован вход в личный кабинет, Вы ознакомлены с имеющейся задолженностью. В случае непогашения обязательств в течение 24-х часов, данный факт будет расцениваться как уклонение от исполнения кредитных обязательств.
Вам доступны индивидуальные условия оплаты задолженности. Для согласования скидки напишите мне ответное сообщение или перезвоните по телефону горячей линии. 
С Вами взаимодействует   {{fio_spec}},
Специалист {{company_new_name}} 
Телефон бесплатной горячей линии {{company_phone}}',
            '        Здравствуйте!
По причине неисполнения обязательств по договору потребительского займа, незаконного удержания денежных средств, нарушения законодательства РФ (ст. 309, ст.310, ст. 395 ГК РФ) {{company_new_name}}  требует оплатить задолженность до «{{date_payment}}». 
С Вами взаимодействует {{fio_spec}},
Специалист {{company_new_name}} 
Телефон бесплатной горячей линии {{company_phone}} ',
            '        Здравствуйте!
Акция!!! При условии погашения задолженности по договору потребительского займа в {{company_new_name}}  единоразовым платежом, спишем пени и часть процентов, начисленных после срока окончания действия договора. 
С Вами взаимодействует  {{fio_spec}},
Специалист {{company_new_name}} 
Телефон бесплатной горячей линии {{company_phone}}',
            '        Здравствуйте! 
Предлагаем рассрочку по долгу. Для решения вопроса позвоните по телефону горячей линии.
С Вами взаимодействует {{fio_spec}},
Специалист {{company_new_name}} 
Телефон бесплатной горячей линии {{company_phone}}',
            '       Здравствуйте!
В случае если по объективным причинам Вы не можете закрыть задолженность в полном объеме, компания готова пойти Вам навстречу и списать часть просроченных процентов и пени. Для решения вопроса позвоните по телефону горячей линии.
С Вами взаимодействует  {{fio_spec}},
Специалист {{company_new_name}} 
Телефон бесплатной горячей линии {{company_phone}}',
            '         Здравствуйте!
По причине неисполнения обязательств по договору потребительского займа, незаконного удержания денежных средств, нарушения законодательства РФ (ст. 309, ст.310, ст. 395 ГК РФ) {{company_new_name}}  требует оплатить задолженность в течение 24-х часов.
В ближайшее время компания направит Вам требование по месту регистрации и проживания о погашении заложенности.
В случае, если по объективным причинам Вы не можете закрыть задолженность в полном объеме, компания готова пойти Вам навстречу и списать часть просроченных процентов и пени. Для решения вопроса позвоните по телефону горячей линии.
С уважением,{{fio_spec}} 
Специалист {{company_new_name}} 
Телефон бесплатной горячей линии {{company_phone}}',
            '         Здравствуйте!
В Вашем личном кабинете на сайте {{company_new_name}}  условия рассрочки долга по договору потребительского займа.                                                                                                              
С уважением, {{fio_spec}}
Специалист {{company_new_name}} 
Телефон бесплатной горячей линии {{company_phone}}',
            '        Здравствуйте!
Уведомляем об инициировании взыскания задолженности в судебном порядке {{company_new_name}} {{company_phone}}
С уважением, {{fio_spec}}
Специалист {{company_new_name}} 
Телефон бесплатной горячей линии {{company_phone}}',
            '        Здравствуйте, {{fio_debtors}}! 
У Вас имеется задолженность перед {{company_new_name}}.
Общая сумма задолженности {{debtor_money_on_day}} рублей.
Можем предложить Вам выплатить задолженность в рассрочку.
С Вами взаимодействует {{fio_spec}},
Специалист {{company_new_name}}, {{spec_phone}}
Телефон бесплатной горячей линии {{company_phone}}.',
            '        {{fio_debtors}}! 
Можем предложить Вам оформить Мировое соглашение для оплаты Вашей задолженности в рассрочку. При оформлении соглашения начисление процентов не происходит в течение всего периода рассрочки. Задолженность выплачивается ежемесячными платежами по графику. 
Сумма платежа и срок рассрочки оговариваются с клиентом индивидуально.
{{company_new_name}} .
Более подробную информацию можно узнать по телефону {{spec_phone}} специалист {{fio_spec}} или по телефону горячей линии {{company_phone}}(звонок бесплатный).
Это индивидуальное предложение для Вас – срок предложения ограничен!',
        ];

        for($i = 1; $i <= count($arrayMessageDebtorsPersonal);$i++){
            EmailMessage::create([
                'name' => '2.' . $i,
                'role_id' => Role::DEBTORS_PERSONAL,
                'template_message' => $arrayMessageDebtorsPersonal[$i -1],
            ]);
        }
    }
}
