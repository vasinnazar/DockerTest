<?php

namespace App\Utils;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use ZipArchive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

/**
 * Создает бэкапы базы данных и складывает на фтп
 */
class MysqlBackupUtil {

    /**
     * 
     * @param array $tables список названий таблиц для бэкапа
     * @param string $db имя базы данных
     */
    static function createBackup($tables = null, $db = 'armf') {
//        mysqldump.exe --defaults-file="c:\users\andrey\appdata\local\temp\tmpwopnls.cnf"  --host=127.0.0.1 --protocol=tcp --user=root --replace=TRUE --port=3306 --default-character-set=utf8 --skip-triggers "armf" "adsources"
        set_time_limit(300);
        $backuper = new MysqlBackupUtil();
//        $backuper->_createBackup(['adsources', 'npf_fonds'], $db);
        $backuper->_createBackup($tables, $db);
    }

    function _createBackup($tables, $db) {
        if (is_null($tables)) {
            $tables = $this->getTablesList($db);
        }
        $folderName = $this->createDumpFolder($db);
        foreach ($tables as $t) {
            $this->createBackupForTable($t, $db, $folderName);
            sleep(1);
        }
        $zipFileName = $this->zipDump($folderName);
        if ($zipFileName !== FALSE) {
            $this->sendDumpToFtp($zipFileName);
            $this->removeBackupFiles($folderName, $zipFileName);
        }
    }
    /**
     * удаляет папку и архив с бэкапом
     * @param string $folderName
     * @param string $zipFileName
     */
    function removeBackupFiles($folderName, $zipFileName) {
        if (config('app.dev')) {
            $folderName = substr($folderName, strrpos(str_replace('/', '\\', $folderName), 'dumps'));
            $zipFileName = substr($zipFileName, strrpos(str_replace('/', '\\', $zipFileName), 'dumps'));
        }
        $folderName = substr($folderName,strrpos($folderName,'dumps'));
        $zipFileName = substr($zipFileName,strrpos($zipFileName,'dumps'));
        try {
            Storage::disk('local')->deleteDirectory($folderName);
        } catch (\Exception $ex) {
            \App\Spylog\Spylog::logError(json_encode(['error'=>'MysqlBackupUtil.removeBackupFiles 1', 'folderName' => $folderName, 'zipFileName' => $zipFileName, 'ex' => $ex]), true);
//            Log::error('MysqlBackupUtil.removeBackupFiles 1', ['folderName' => $folderName, 'zipFileName' => $zipFileName, 'ex' => $ex]);
        }
        try {
            Storage::disk('local')->delete($zipFileName);
        } catch (\Exception $ex) {
            \App\Spylog\Spylog::logError(json_encode(['error'=>'MysqlBackupUtil.removeBackupFiles 2', 'folderName' => $folderName, 'zipFileName' => $zipFileName, 'ex' => $ex]), true);
//            Log::error('MysqlBackupUtil.removeBackupFiles 2', ['folderName' => $folderName, 'zipFileName' => $zipFileName, 'ex' => $ex]);
        }
    }
    /**
     * Делает .sql бэкап файл таблицы в указанную папку
     * @param string $tableName имя таблицы
     * @param string $db имя базы
     * @param string $folderName имя папки
     */
    function createBackupForTable($tableName, $db, $folderName) {
        exec(
                $this->getMysqlDumpPath()
                . ' --host=127.0.0.1'
                . ' --protocol=tcp'
                . ' --user=root'
                . ' --password=vrotmnenogi712'
                . ' --replace=TRUE'
                . ' --port=3306'
                . ' --default-character-set=utf8'
                . ' "' . $db . '" "' . $tableName . '"'
                . ' --result-file=' . $this->getResultFilePath($tableName, $folderName)
        );
    }
    /**
     * Возвращает список таблиц в базе
     * @param string $db имя базы
     * @return type
     */
    function getTablesList($db) {
        $tables = DB::select('select table_name from information_schema.tables where table_schema=\'' . $db . '\'');
        $res = [];
        foreach ($tables as $t) {
            $res[] = $t->table_name;
        }
        return $res;
    }
    /**
     * возвращает путь до mysqldump
     * @return string
     */
    function getMysqlDumpPath() {
        if (config('app.dev')) {
            return 'D:\\xampp\\mysql\\bin\\mysqldump.exe';
        } else {
            return 'mysqldump';
        }
    }
    /**
     * возвращает путь до .sql файла
     * @param string $tableName имя таблицы
     * @param string $folderName имя папки
     * @return string путь до файла
     */
    function getResultFilePath($tableName, $folderName) {
        return $folderName . '/' . $tableName . '.sql';
    }
    /**
     * создает папку для бэкапа и возвращает путь до нее
     * @return string
     */
    function createDumpFolder($db) {
        $path = $this->getDumpsFolderPath() . 'Dump_'.$db.'_'. Carbon::now()->format('YmdHis');
        File::makeDirectory($path, 0777, true, true);
        return $path;
    }
    /**
     * возвращает путь до папки в которой создаются дампы
     * @return string
     */
    function getDumpsFolderPath() {
        return storage_path() . '/app/dumps/';
    }
    /**
     * Создает архив папки с дампом
     * @param string $folderName
     * @return boolean|string
     */
    function zipDump($folderName) {
        $dumpname = substr($folderName, strrpos($folderName, '/') + 1);
        $zipFile = $this->getDumpsFolderPath() . $dumpname . '.zip';
        $zipArchive = new ZipArchive();

        if (!$zipArchive->open($zipFile, ZIPARCHIVE::CREATE)) {
            \App\Spylog\Spylog::logError(json_encode(['error'=>"MysqlBackupUtil error on create"]),true);
            return false;
        }
        $options = ['add_path' => $dumpname . '/', 'remove_all_path' => TRUE];
        $zipArchive->addGlob($folderName . "/*.sql", GLOB_BRACE, $options);
        if (!$zipArchive->status == ZIPARCHIVE::ER_OK) {
            \App\Spylog\Spylog::logError(json_encode(['error'=>"MysqlBackupUtil Failed to write files to zip\n"]),true);
        }
        $zipArchive->close();
        return $zipFile;
    }
    /**
     * Отправляет файл на фтп
     * @param string $filepath
     */
    function sendDumpToFtp($filepath) {
        $filename = substr($filepath, strrpos($filepath, '/') + 1);
        $file = File::get($filepath);
        try{
            Storage::disk('ftp222')->put('dumps/' . $filename, $file);
        } catch (\Exception $ex) {
            \App\Spylog\Spylog::logError(json_encode(['error'=>"MysqlBackupUtil error on create",'ex'=>$ex]),true);
        }
    }
    static function clearDumpsFolder(){
        try{
            $files = Storage::disk('local')->files('dumps');
            Storage::delete($files);
            $dirs = Storage::disk('local')->directories('dumps');
            foreach($dirs as $dir){
                Storage::deleteDirectory($dir);
            }
        } catch (\  Exception $ex) {

        }
    }

}
