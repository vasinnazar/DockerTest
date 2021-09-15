<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MysqlThread extends Model {

    protected $table = 'mysql_threads';
    protected $fillable = ['amount','created_at'];
    
    static function addToStat(){
        $mysqlThreads = DB::select("show status where `variable_name` = 'Threads_connected'");
        MysqlThread::create(['amount'=>$mysqlThreads[0]->Value]);
    }
    static function getCurrentThreadsNum(){
        $mysqlThreads = DB::select("show status where `variable_name` = 'Threads_connected'");
        return (int)$mysqlThreads[0]->Value;
    }


    static function getCurrentThreads(){
        $threads = DB::select('SHOW PROCESSLIST');
        return $threads;
    }
    
    static function killSleepingThreads($sleepTime = 2000){
        $threads = MysqlThread::getCurrentThreads();
        $threadsnum = count($threads);
        $killedThreads = 0;
        foreach($threads as $t){
            if($t->Command == 'Sleep' && $t->Time >= $sleepTime){
                DB::select('KILL ' . $t->Id);
                $killedThreads++;
            }
        }
    }

}
