<?php

namespace App\Console\Commands;

use App\UploadSqlFile;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class DebtorSyncFilesDownload extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debtor-sync:download';

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
        $oldFiles = UploadSqlFile::where('filename', 'like', '%27022023%')->get()->pluck('filename')->toArray();

        $files = collect($files)->map(function ($item) {
            if (str_contains($item, '27022023')) {
                return str_replace('debtors/', '', $item);
            }
        });

        $newFiles = $files->filter(function ($item) use ($oldFiles) {
            if (!in_array($item, $oldFiles, true)) {
                return $item;
            }
        });

        if ($newFiles->isEmpty()) {
            return;
        }

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
