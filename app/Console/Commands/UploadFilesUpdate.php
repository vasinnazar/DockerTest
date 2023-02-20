<?php

namespace App\Console\Commands;

use App\UploadSqlFile;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadFilesUpdate extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-client:upload-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'загрузка данных в таблицу dto_1c_updates';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $files = UploadSqlFile::where('completed', 0)->orderBy('id', 'asc')->get();
        if ($files->isEmpty()) {
            return;
        }
        foreach ($files as $file) {

            $path = storage_path() . '/app/debtors/' . $file->filename;
            if (!(Storage::disk('local')->has('debtors/' . $file->filename))) {
                continue;
            }
            try {


                if ($file->type == 2) {
                    $query = "LOAD DATA LOCAL INFILE '{$path}' INTO TABLE update_about_clients
        FIELDS TERMINATED BY ';' ENCLOSED BY '\"' LINES TERMINATED BY '\n'
        (`debtor_id_1c`,`customer_id_1c`,`telephone`,`telephonehome`,`telephoneorganiz`,`telephonerodstv`,
        `anothertelephone`,`zip`,`address_region`,`address_district`,`address_city`,`address_street`,`address_house`,
        `address_building`,`address_apartment`,`address_city1`,`fact_zip`,`fact_address_region`,
        `fact_address_district`,`fact_address_city`,`fact_address_street`,`fact_address_house`,`fact_address_building`,
        `fact_address_apartment`,`fact_address_city1`)
        set created_at = now(), updated_at = now(), file_id= `{$file->id}`";
                    Storage::disk('local')->delete('debtors/' . $file->filename);

                    $file->in_process = 1;
                    $file->save();
                }


                if ($file->type == 1) {
                    $query = "LOAD DATA LOCAL INFILE '{$path}'
                          INTO TABLE update_debtors
                          LINES TERMINATED BY ';'
                          (`sql_command`) 
                          set created_at = now(), updated_at = now(),file_id= `{$file->id}`";
                    Storage::disk('local')->delete('debtors/' . $file->filename);

                    $file->in_process = 1;
                    $file->save();
                }

                DB::unprepared($query);
            } catch (\Exception $e) {
                Log::error('Error LOAD DATA file');
            }
        }
    }

}
