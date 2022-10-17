<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Config;

class DebtorNotice extends Model {

    protected $table = 'debtors.debtors_notices';
    protected $fillable = ['struct_subdivision', 'in_progress', 'completed'];
    
}
