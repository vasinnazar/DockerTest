<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Config;

class DebtorRecurrentQuery extends Model {

    protected $table = 'debtors_recurrent_queries';
    protected $fillable = ['debtor_id'];

}
