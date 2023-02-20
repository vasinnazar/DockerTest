<?php

namespace App\Jobs;

use App\UpdateDebtors;
use App\UploadSqlFile;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateDebtorsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $updateDebtors;

    public function __construct(UpdateDebtors $updateDebtors)
    {
        $this->updateDebtors = $updateDebtors;
    }

    public function handle()
    {
        try {
            DB::unprepared($this->updateDebtors->sql_command);
            UpdateDebtors::find($this->updateDebtors->id)->update('deleted_at', Carbon::now());

            $process = UpdateDebtors::whereNull('deleted_at')->where('file_id', $this->updateDebtors->file_id)->get();
            if ($process->isEmpty()) {
                UploadSqlFile::find($this->updateDebtors->file_id)->update(['completed' => 1, 'in_process' => 0]);
            }

        } catch (\Exception $e) {
            Log::error('Error insert or update debtors : ', [$e->getMessage()]);
        }

    }
}
