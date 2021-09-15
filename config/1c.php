<?php

/**
 * настройки подключения к 1С
 */
return [
    'service' => 'ARM',
    'login' => 'KAadmin',
    'url' => '/ws/ARM/?wsdl',
//    'password' => '3',
        'password' => 'Dune25',
    'loank_number_url'=>'/ws/ARM_LoanK_number/?wsdl',
    'terminal_url'=>'/ws/Terminal/?wsdl',
    'debt_url'=>'/ws/AutoInformator/?wsdl',
    'paysheet_url'=>'/ws/PaySheet/?wsdl',
    'cashbook_url'=>'/ws/GetCashBuh/?wsdl',
    'docsregister_url'=>'/ws/DocsRegister/?wsdl',
    'dailycashreport_url'=>'/ws/DailyCashReport/?wsdl',
    'matclaim_url'=>'/ws/MatClaim/?wsdl',
    'worktime_url'=>'/ws/WorkTime/?wsdl',
    'npf_url'=>'/ws/ContractNpf/?wsdl',
    'order_terminal_url'=>'/ws/Create_order_terminal/?wsdl',
    'auto_approve_url'=>'/ws/wAuto_okay/?wsdl',
    'mole_url'=>'/ws/Mole/?wsdl',
    'mole_url_out'=>'/ws/ArmOut/?wsdl',
    'exchange_arm'=>'/ws/ExchangeARM/?wsdl',
];
