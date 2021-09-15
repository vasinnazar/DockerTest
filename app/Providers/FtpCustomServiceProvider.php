<?php

namespace App\Providers;

use Storage;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Sftp\SftpAdapter;

class FtpCustomServiceProvider extends ServiceProvider {

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot() {
        Storage::extend('sftp', function($app, $config) {
//            $client = new DropboxClient(
//                $config['accessToken'], $config['clientIdentifier']
//            );
//
//            return new Filesystem(new DropboxAdapter($client));
            $adapter = new SftpAdapter($config);

            return new Filesystem($adapter);
        });
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register() {
        //
    }

}
