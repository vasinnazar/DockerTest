<?php

namespace App\Console\Commands;

use App\UploadSqlFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DebtorSyncFilesImport extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debtor-sync:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'загрузка данных во временные таблицы';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $files = UploadSqlFile::where('completed', 0)->where('in_process', 0)->get();
        if ($files->isEmpty()) {
            return;
        }
        foreach ($files as $file) {

            $path = storage_path() . '/app/debtors/' . $file->filename;
            if (!(Storage::disk('ftp')->has('/debtors/test_csv/' . $file->filename))) {
                continue;
            }
            Storage::disk('local')
                ->put('debtors/' . $file->filename, Storage::disk('ftp')->get('/debtors/test_csv/' . $file->filename));
            try {

                if ($file->filetype == 2) {
                    $query = "LOAD DATA LOCAL INFILE '{$path}' INTO TABLE debtor_sync_about " .
                        "FIELDS TERMINATED BY ';' LINES TERMINATED BY '\n' " .
                        "(`debtor_id_1c`,`customer_id_1c`,`telephone`,`telephonehome`,`telephoneorganiz`,`telephonerodstv`," .
                        "`anothertelephone`,`zip`,`address_region`,`address_district`,`address_city`,`address_street`,`address_house`," .
                        "`address_building`,`address_apartment`,`address_city1`,`fact_zip`,`fact_address_region`," .
                        "`fact_address_district`,`fact_address_city`,`fact_address_street`,`fact_address_house`,`fact_address_building`," .
                        "`fact_address_apartment`,`fact_address_city1`) " .
                        "set created_at = now(), updated_at = now(), file_id= '{$file->id}'";
                }


                if ($file->filetype == 1) {
                    $query = "LOAD DATA LOCAL INFILE '{$path}' " .
                        "INTO TABLE debtor_sync_sql " .
                        "LINES TERMINATED BY '\n' " .
                        "(`sql_command`) " .
                        "set created_at = now(), updated_at = now(),file_id= '{$file->id}'";
                }

                DB::unprepared($query);

            } catch (\Exception $e) {
                Log::error('Error LOAD DATA file', [$e->getMessage()]);
                continue;
            }

            Storage::disk('local')->delete('debtors/' . $file->filename);
            $file->in_process = 1;
            $file->save();
        }
    }

}
