<?php

namespace App\Utils;

use Storage;

class SslUtil {

    static function createUserCertificate($user) {
        $filename = 'user_' . $user->id;
        $certsFolder = '/var/www/armff.ru/storage/app/ssl/users/' . $filename . '/';
        $p12_filename = str_replace(' ','_',$user->name);
        $p12_filepath = $certsFolder . $p12_filename;
        $clientFilepath = $certsFolder . $filename;
        $rootFilePath = '/var/www/armff.ru/storage/app/ssl/ca/root';
//        $p12Password = 'h12iP;32xZKug.poi_Y';
        $p12Password = 'h12iP32x~ZKugpoiY';
        $serial_number = '1024';
        $storageFolderPath = 'ssl/users/' . $filename;
        if (Storage::disk('local')->exists($storageFolderPath)) {
            if (Storage::disk('local')->exists($storageFolderPath . '/' . $p12_filename . '.p12')) {
                return $p12_filepath . '.p12';
            } else {
                Storage::deleteDirectory($storageFolderPath);
            }
        }
        Storage::disk('local')->makeDirectory('ssl/users/' . $filename);

        $com1 = HelperUtil::startShellProcess('openssl genrsa -out ' . $clientFilepath . '.key 2048');
        if ($com1 === FALSE) {
            return FALSE;
        }
        $com2 = HelperUtil::startShellProcess('openssl req -new -key ' . $clientFilepath . '.key -subj "/C=RU/ST=NA/L=Kemerovo/O=Finterra/OU=' . $user->id . '/CN=' . $user->name . '" -out ' . $clientFilepath . '.csr');
        if ($com2 === FALSE) {
            return FALSE;
        }
        $com3 = HelperUtil::startShellProcess('openssl x509 -req -in ' . $clientFilepath . '.csr -CA ' . $rootFilePath . '.pem -CAkey ' . $rootFilePath . '.key -CAcreateserial -out ' . $clientFilepath . '.pem -days 3650 -set_serial ' . $serial_number);
        if ($com3 === FALSE) {
            return FALSE;
        }
        $com4 = HelperUtil::startShellProcess('openssl pkcs12 -export -in ' . $clientFilepath . '.pem -inkey ' . $clientFilepath . '.key -certfile ' . $rootFilePath . '.pem -out ' . $p12_filepath . '.p12 -password pass:' . $p12Password);
        if ($com4 === FALSE) {
            return FALSE;
        }
        return $p12_filepath . '.p12';
    }

}
