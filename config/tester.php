<?php

return [
    'modules_1c' => [
        'ARM'=>[
            'functions'=>[
                'CheckK',
                'CreateK',
                'CreateСreditAgreement',
                'checkPromocode',
                'Create_KO',
                'CreateK_other',
                'CreateZPP',
                'CreateMS',
                'CreateClose',
                'Delete',
                'Loan_sub',
                'LoanK',
                'LoanK_FIO',
                'Create_order',
                'Create_order_card',
                'CreateFL'
            ]
        ],
        'ARM_LoanK_number'=>[
            'functions'=>[
                'LoanK_Number'
            ]
        ],
        'Terminal'=>['functions'=>[
            'AddTerminal',
            'Create_order'
        ]],
        'AutoInformator'=>['functions'=>[]],
        'PaySheet'=>['functions'=>[
            'GetSheet'
        ]],
        'GetCashBuh'=>['functions'=>[
            'GetSubdivisionCash'
        ]],
        'DocsRegister'=>['functions'=>[
            'GetDocsRegister'
        ]],
        'DailyCashReport'=>['functions'=>[
            'CreateDailyCashReport'
        ]],
        'MatClaim'=>['functions'=>[
            'SaveMatClaim'
        ]],
        'WorkTime'=>['functions'=>[
            'CreateWorkTime'
        ]],
        'ContractNpf'=>['functions'=>[
            'CreateNPF'
        ]],
        'Create_order_terminal'=>['functions'=>[]],
        'wAuto_okay'=>['functions'=>[
            'Auto_okay'
        ]],
        'Mole'=>['functions'=>[
            'IAmMole'
        ]],
        'ArmOut'=>['functions'=>[
            'armout'
        ]],
        'ExchangeARM'=>['functions'=>[
            'Main'
        ]]
    ]
];
