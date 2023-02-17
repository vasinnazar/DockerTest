<?php

namespace App\Console\Commands;

use App\UploadSqlFile;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FilesUpdateClient extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-client:get-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'поиск новых файлов и добавление их в таблицу upload_sql_files';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $files = Storage::disk('local')->files('/debtors');
        $newFiles = collect($files)->map(function ($item) {
            if (str_contains($item, Carbon::now()->format('dmY'))) {
                return str_replace('debtors/', '', $item);
            }
        });

        foreach ($newFiles as $newFile) {
            if (str_contains($newFile, 'about_clients')) {
                UploadSqlFile::create([
                    'filetype' => 2,
                    'filename' => $newFile,
                ]);
//                Storage::disk('local')->put('debtors/' . $file, Storage::disk('ftp')->get('debtors/' . $file));
            }
            if (str_contains($newFile, 'debtors')) {
                UploadSqlFile::create([
                    'filetype' => 1,
                    'filename' => $newFile,
                ]);
//                Storage::disk('local')->put('debtors/' . $file, Storage::disk('ftp')->get('debtors/' . $file));
            }
        }
    }

}
