<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UnfindCustomersPassports extends Model {

    protected $table = 'debtors.unfind_customers_passports';
    protected $fillable = ['series', 'number', 'fio'];

}
