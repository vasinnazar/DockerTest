<?php

use Illuminate\Database\Seeder;

class OrderTypesSeeder extends Seeder
{
    public function run()
    {
        $arrOrderTypes = [
            1 => [
                'textId' => 'RKO',
                'name' => 'Расходно-кассовый ордер',
                'invoice' => 58.03,
                'plus' => 0
            ],
            3 => [
                'textId' => 'CARD',
                'name' => 'Списание на карту',
                'invoice' => 58.03,
                'plus' => 0
            ],
            4 => [
                'textId' => 'PODOTCHET',
                'name' => 'Выдача в подотчет',
                'invoice' => 71.01,
                'plus' => 0
            ],
            5 => [
                'textId' => 'PKO',
                'name' => 'Приход',
                'plus' => 1
            ],
            6 => [
                'textId' => 'RASHOD',
                'name' => 'Расход (внутренние перемещения)',
                'invoice' => 71.01,
                'plus' => 0
            ],
            7 => [
                'textId' => 'SALARY',
                'name' => 'Оплата труда',
                'invoice' => 70,
                'plus' => 0
            ],
            8 => [
                'textId' => 'CANC',
                'name' => 'Канцтовары',
                'invoice' => 91.02,
                'plus' => 0
            ],
            9 => [
                'textId' => 'INCASS',
                'name' => 'Инкассация',
                'invoice' => 57.01,
                'plus' => 0
            ],
            10 => [
                'textId' => 'COMIS',
                'name' => 'Комиссия банка',
                'invoice' => 91.02,
                'plus' => 0
            ],
            11 => [
                'textId' => 'VOZVRAT',
                'name' => 'Возврат подотчетных средст',
                'invoice' => 71.01,
                'plus' => 1
            ],
            12 => [
                'textId' => 'SALARY91',
                'name' => 'Оплата труда 91',
                'invoice' => 91.02,
                'plus' => 0
            ],
            13 => [
                'textId' => 'SBERINCASS',
                'name' => 'Самоинкассация СБЕРБАНК',
                'invoice' => 57.01,
                'plus' => 0
            ],
            14 => [
                'textId' => 'URALINCASS',
                'name' => 'Самоинкасация УРАЛСИБ',
                'invoice' => 57.02,
                'plus' => 0
            ],
            15 => [
                'textId' => 'VPKO',
                'name' => 'Приход (внутренние перемещения)',
                'invoice' => 50.01,
                'plus' => 1
            ],
            16 => [
                'textId' => 'POCHTA',
                'name' => 'Почтовые расходы',
                'invoice' => 91.02,
                'plus' => 0
            ],
            17 => [
                'textId' => 'DEFICIT',
                'name' => 'Возврат недостачи',
                'invoice' => 94,
                'plus' => 1
            ],
            18 => [
                'textId' => 'OVERAGE',
                'name' => 'Излишки',
                'invoice' => 91.01,
                'plus' => 1
            ],
            19 => [
                'textId' => 'OPKO',
                'name' => 'Прочий приход',
                'invoice' => 76.13,
                'plus' => 1
            ],
            20 => [
                'textId' => 'QIWI',
                'name' => 'Платеж по QIWI',
                'plus' => 1
            ],
            21 => [
                'textId' => 'TPKO',
                'name' => 'Платеж по терминалу',
                'plus' => 1
            ],
            22 => [
                'textId' => 'BANK1',
                'name' => 'Платеж в банке',
                'plus' => 1
            ],
            23 => [
                'textId' => 'INTERNET',
                'name' => 'Оплата интернета',
                'invoice' => 71.01,
                'plus' => 0
            ],
            24 => [
                'textId' => 'HOZRASHOD',
                'name' => 'Хозяйственные расходы',
                'invoice' => 71.01,
                'plus' => 0
            ],
            25 => [
                'textId' => 'BUYEQUIP',
                'name' => 'Приобретение оборудования',
                'invoice' => 71.01,
                'plus' => 0
            ],
            26 => [
                'textId' => 'COMRASHOD',
                'name' => 'Командировочные расходы',
                'invoice' => 71.01,
                'plus' => 0
            ],
            27 => [
                'textId' => 'DEFICITRKO',
                'name' => 'Недостача',
                'invoice' => 94,
                'plus' => 0
            ],
            28 => [
                'textId' => 'PROMOTER',
                'name' => 'Оплата труда промоутеров/ГПХ',
                'invoice' => 70,
                'plus' => 1
            ],
            29 => [
                'textId' => 'TERMINALREFILL',
                'name' => 'Пополнение терминала',
                'plus' => 1
            ],
            30 => [
                'textId' => 'PAYTURE',
                'name' => 'Платеж через сайт',
                'plus' => 1
            ],
            31 => [
                'textId' => 'TERMINALPODKREP',
                'name' => 'Подкрепление терминала',
                'invoice' => 71.01,
                'plus' => 0
            ],
            32 => [
                'textId' => 'DEFICIT73',
                'name' => 'Возврат недостачи 73.02(60322.ДГВ)',
                'invoice' => 73.02,
                'plus' => 1
            ],
            33 => [
                'textId' => 'TERMINALPKOPODKREP',
                'name' => 'Подкрепление терминала (приход)',
                'invoice' => 50.02,
                'plus' => 1
            ],
            34 => [
                'textId' => 'PROCHPRIHOD7303',
                'name' => 'Прочий приход 73.03',
                'invoice' => 73.03,
                'plus' => 1
            ],
            35 => [
                'textId' => 'VOZVRAT7609',
                'name' => 'Возврат комиссии',
                'invoice' => 76.09,
                'plus' => 0
            ],
            36 => [
                'textId' => 'TINKOFFPAY',
                'name' => 'Платеж через тинькофф',
                'plus' => 1
            ],
            37 => [
                'textId' => 'SMSPACK',
                'name' => 'Оплата пакета SMS',
                'invoice' => 76.09,
                'plus' => 1
            ],
            38 => [
                'textId' => 'VOZVRATZP',
                'name' => 'Возврат зп',
                'invoice' => 60306,
                'plus' => 1
            ],
            39 => [
                'textId' => 'REFUND',
                'name' => 'Возмещение ущерба',
                'invoice' => 73.01,
                'plus' => 1
            ],
            40 => [
                'textId' => 'DEFICIT94KT',
                'name' => 'Возврат недостачи 94 кт',
                'invoice' => 94,
                'plus' => 1
            ],
            43 => [
                'textId' => 'VOZVRATSBER',
                'name' => 'Наличные с бизнес-карты ("Сбербанк")',
                'invoice' => 71.01,
                'plus' => 1
            ],
            44 => [
                'textId' => 'VOZVRATTINKOFF',
                'name' => 'Наличные с бизнес-карты ("Тинькофф")',
                'invoice' => 71.01,
                'plus' => 1
            ],
            45 => [
                'textId' => 'SAMOINCOSACIA',
                'name' => 'Возврат суммы самоинкассации',
                'invoice' => 57.01,
                'plus' => 1
            ],
            46 => [
                'textId' => 'PAYU',
                'name' => 'Зачисление Payu',
                'plus' => 0
            ],
            47 => [
                'textId' => 'RNKO',
                'name' => 'Списание РНКО',
                'plus' => 0
            ],
            48 => [
                'textId' => 'TINKOFF_DEBTS',
                'name' => 'Списание рекуррентных (Тинькофф) платежей с должников',
                'plus' => 0
            ],

        ];

        foreach ($arrOrderTypes as $orderType ){
            \App\OrderType::create([
                'text_id'=>$orderType['textId'],
                'name'=>$orderType['name'],
                'invoice'=>$orderType['invoice'] ?? null,
                'plus'=>$orderType['plus'],
            ]);
        }
    }
}
