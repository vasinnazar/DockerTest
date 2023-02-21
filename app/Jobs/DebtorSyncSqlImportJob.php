<?php

namespace App\Jobs;

use App\DebtorSync;
use App\UploadSqlFile;
use Carbon\Carbon;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DebtorSyncSqlImportJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    private $debtorSyncId;

    public function __construct(int $updateDebtorsId)
    {
        $this->debtorSyncId = $updateDebtorsId;
    }

    public function handle()
    {
        try {
            $debtorSync = DebtorSync::find(str_replace('﻿','',$this->debtorSyncId));
            DB::unprepared($debtorSync->sql_command);

        } catch (\Exception $e) {
            Log::error('Error insert or update debtors : ', [$e->getMessage()]);
        }

        $debtorSync->deleted_at = Carbon::now();
        $debtorSync->save();

        $process = DebtorSync::whereNull('deleted_at')->where('file_id', $debtorSync->file_id)->get();
        if ($process->isEmpty()) {
            UploadSqlFile::find($debtorSync->file_id)->update(['completed' => 1, 'in_process' => 0]);
        }



    }
}
