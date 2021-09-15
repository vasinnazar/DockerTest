<?php
$url = 'http://192.168.1.33/armff.ru/test';
$data = array('login' => 'value1', 'password' => '1', 'subdivision_id_1c'=>'12', 'doc'=>'dlfkldkfld', 'id_1c'=>'111');

// use key 'http' even if you send the request to https://...
$options = array(
    'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data),
    ),
);
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo($result);