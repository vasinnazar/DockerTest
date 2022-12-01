<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Config;

class CourtOrderTasks extends Model {

    protected $table = 'debtors.court_orders_tasks';
    protected $fillable = ['struct_subdivision', 'in_progress', 'completed'];
    
}
