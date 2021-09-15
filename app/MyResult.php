<?php

namespace App;

class MyResult {

    public $result = false;
    public $error = '';

    public function __construct($result, $error = '') {
        $this->result = ((int) $result == 1 || $result == true) ? true : false;
        $this->error = (string)((empty($error)) ? Utils\StrLib::ERR : $error);
    }

}
