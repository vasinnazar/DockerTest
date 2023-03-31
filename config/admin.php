<?php
return array (
    'sms_servers_list' =>
        array (
            0 => 'smsc.ru',
            1 => 'www2.smsc.ru',
            2 => 'www3.smsc.ru',
            3 => 'www4.smsc.ru',
        ),
    'sms_server' => 'smsc.ru',
    'print_servers_list' =>
        array (
            0 => '52a204b0bdc1.sn.mynetname.net:9656',
            1 => '52a204b0bdc1.sn.mynetname.net:8889',
            2 => '5.175.225.158:8081',
            3 => '52a204b0bdc1.sn.mynetname.net:16590',
        ),
    'print_server' => '81.177.142.230',
    //'print_server' => '5.175.225.158:8081',
    //'print_server' => '52a204b0bdc1.sn.mynetname.net:8889',
    // 'print_server' => '192.168.34.209',
    'local_print_servers_list' =>
        array (
            0 => '192.168.1.123:8080',
            1 => '192.168.1.60',
            2 => '5.175.225.158:8081',
            3 => '192.168.1.21:444',
        ),
    'local_print_server' => '5.175.225.158:8081',
    //'local_print_server' => '81.177.142.230',
    //'local_print_server' => '52a204b0bdc1.sn.mynetname.net:8889',
    //'local_print_server' => '192.168.34.209',
    'servers_1c_list' =>
        array (
            0 => '192.168.1.114:80/11spdSSD',
            1 => '192.168.1.47:8080/1c47',
            2 => '192.168.1.198:80/test_gummel',
            3 => '192.168.1.15:80/test',
            4 => '192.168.1.23:8085/PersonaArea1',
            5 => '192.168.1.123/PersonaArea1',
            6 => '192.168.1.34:81/PersonaArea1',
            7 => '192.168.1.21:443/PersonaArea1',
        ),
    'server_1c' => '192.168.1.34:81/PersonaArea1',
    //'server_1c' => '192.168.1.21:443/PersonaArea1',
    'auto_change_server_1c' => 0,
    'last_mysql_check_date' => '2017-05-05 10:52:35',
    'alert_phone' => '79030466344',
    'orders_without_1c' => 0,
    'zup_connection' => [
        'url' => '192.168.35.21:443/zupregpd31/ws/Employment?wsdl',
        'login' => 'Администратор',
        'password' => '135274',
        'absolute_url' => true
    ],
    'buh_base_1c' => [
        'url' => '192.168.35.38:8080/ARMDolzniki/ws/Mole/?wsdl',
        'login' => 'KAadmin',
        'password' => 'dune25',
        'absolute_url' => true
    ],
    'debtors_arm' => 'http://192.168.1.215'
) ;
