<?php

return [

    /*
      |--------------------------------------------------------------------------
      | Default Filesystem Disk
      |--------------------------------------------------------------------------
      |
      | Here you may specify the default filesystem disk that should be used
      | by the framework. A "local" driver, as well as a variety of cloud
      | based drivers are available for your choosing. Just store away!
      |
      | Supported: "local", "s3", "rackspace"
      |
     */

//    'default' => 'local',
//    'default' => 'ftp125',
    'default' => 'ftp31',
    /*
      |--------------------------------------------------------------------------
      | Default Cloud Filesystem Disk
      |--------------------------------------------------------------------------
      |
      | Many applications store files both locally and in the cloud. For this
      | reason, you may specify a default "cloud" driver here. This driver
      | will be bound as the Cloud disk implementation in the container.
      |
     */
    'cloud' => 's3',
    /*
      |--------------------------------------------------------------------------
      | Filesystem Disks
      |--------------------------------------------------------------------------
      |
      | Here you may configure as many filesystem "disks" as you wish, and you
      | may even configure multiple disks of the same driver. Defaults have
      | been setup for each driver as an example of the required options.
      |
     */
    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path() . '/app',
//            'root' => '//192.168.1.123/images/0000000000',
//            'root' => '//192.168.1.74/Share',
//            'root' => '//192.168.1.222/arm$',
//            'username'=>'Администратор',
//            'password'=>'iatianymatonv'
//            'username' => 'arm',
//            'password' => 'armarm'
        ],
        'storage' => [
            'driver' => 'local',
            'root' => storage_path(),
        ],
        's3' => [
            'driver' => 's3',
            'key' => 'your-key',
            'secret' => 'your-secret',
            'region' => 'your-region',
            'bucket' => 'your-bucket',
        ],
        'rackspace' => [
            'driver' => 'rackspace',
            'username' => 'your-username',
            'key' => 'your-key',
            'container' => 'your-container',
            'endpoint' => 'https://identity.api.rackspacecloud.com/v2.0/',
            'region' => 'IAD',
            'url_type' => 'publicURL'
        ],
        'sftp' => [
            'driver' => 'sftp',
            'host' => '192.168.1.31',
            'port' => 21,
            'timeout' => 20,
            'username' => 'Admin',
            'password' => 'iatianymatonv',
            'passive' => true,
            'agent' => true,
            'directoryPerm' => 0755
        ],
        'ftp' => [
            'driver' => 'ftp',
            'host' => '192.168.1.31',
//            'host' => '192.168.1.222',
            'root' => '/armff.ru/local/storage/app',
//            'root' => 'ARM$',
            'port' => 21,
            'timeout' => 20,
            'username' => 'Admin',
//            'username' => 'arm',
            'password' => 'iatianymatonv',
//            'password' => 'armarm',
            'passive' => true
        ],
        'ftp31_999' => [
            'driver' => 'ftp',
            'host' => '192.168.1.31',
            'root' => '/999',
            'port' => 21,
            'timeout' => 20,
            'username' => 'Admin',
            'password' => 'iatianymatonv',
            'passive' => true
        ],
        'ftp31_111' => [
            'driver' => 'ftp',
            'host' => '192.168.1.31',
            'root' => '/images4',
            'port' => 21,
            'timeout' => 20,
            'username' => 'Admin',
            'password' => 'iatianymatonv',
            'passive' => true
        ],
        'ftp31' => [
            'driver' => 'ftp',
            'host' => '192.168.1.31',
            'root' => '/222',
            'port' => 21,
            'timeout' => 20,
            'username' => 'Admin',
            'password' => 'iatianymatonv',
            'passive' => true
        ],
        'ftp222' => [
            'driver' => 'ftp',
            'host' => '192.168.1.222',
            'root' => '/ARM$',
            'port' => 21,
            'timeout' => 20,
            'username' => 'arm',
            'password' => '7n8ODsY~?H5QAxr',
            'passive' => true
        ],
        'ftp125' => [
            'driver' => 'ftp',
            'host' => '192.168.1.125',
            'root' => '/mnt/RAID10/Doc/foto_clientov',
            'port' => 21,
            'timeout' => 20,
            'username' => 'root',
            'password' => 'Kvisachaderah_25',
            'passive' => false
        ],
    ],
];
