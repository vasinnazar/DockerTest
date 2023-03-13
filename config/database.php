<?php

return [

    /*
      |--------------------------------------------------------------------------
      | PDO Fetch Style
      |--------------------------------------------------------------------------
      |
      | By default, database results will be returned as instances of the PHP
      | stdClass object; however, you may desire to retrieve records in an
      | array format for simplicity. Here you can tweak the fetch style.
      |
     */

    'fetch' => PDO::FETCH_CLASS,
    /*
      |--------------------------------------------------------------------------
      | Default Database Connection Name
      |--------------------------------------------------------------------------
      |
      | Here you may specify which of the database connections below you wish
      | to use as your default connection for all database work. Of course
      | you may use many connections at once using the Database library.
      |
     */
    'default' => 'mysql',
    /*
      |--------------------------------------------------------------------------
      | Database Connections
      |--------------------------------------------------------------------------
      |
      | Here are each of the database connections setup for your application.
      | Of course, examples of configuring each database platform that is
      | supported by Laravel is shown below to make development simple.
      |
      |
      | All database work in Laravel is done through the PHP PDO facilities
      | so make sure you have the driver for your particular database of
      | choice installed on your machine before you begin development.
      |
     */
    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => storage_path() . '/database.sqlite',
            'prefix' => '',
        ],
        'mysql' => array(
            'read' => array(
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
            ),
            'write' => array(
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
            ),
            'driver' => 'mysql',
            'database' => env('DB_DATABASE', 'debtors'),
            'username' => env('DB_USERNAME','alex'),
            'password' => env('DB_PASSWORD','12345'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'options'    => [PDO::MYSQL_ATTR_LOCAL_INFILE=>true],
        ),
        'debtors' => array(
            'read' => array(
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
            ),
            'write' => array(
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
            ),
            'driver' => 'mysql',
            'database' => env('DB_DATABASE', 'debtors'),
            'username' => env('DB_USERNAME','alex'),
            'password' => env('DB_PASSWORD','12345'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ),
        'armf' => array(
            'read' => array(
                'host' => env('ARM_DB_HOST', '127.0.0.1'),
                'port' => env('ARM_DB_PORT', '3306'),
            ),
            'write' => array(
                'host' => env('ARM_DB_HOST', '127.0.0.1'),
                'port' => env('ARM_DB_PORT', '3306'),
            ),
            'driver' => 'mysql',
            'database' => env('ARM_DB_DATABASE', 'armf2'),
            'username' => env('ARM_DB_USERNAME','alex'),
            'password' => env('ARM_DB_PASSWORD','12345'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ),
        'arm' => array(
            'read' => array(
                'host' => env('ARM_DB_HOST', '127.0.0.1'),
                'port' => env('ARM_DB_PORT', '3306'),
            ),
            'write' => array(
                'host' => env('ARM_DB_HOST', '127.0.0.1'),
                'port' => env('ARM_DB_PORT', '3306'),
            ),
            'driver' => 'mysql',
            'database' => env('ARM_DB_DATABASE', 'armf2'),
            'username' => env('ARM_DB_USERNAME','alex'),
            'password' => env('ARM_DB_PASSWORD','12345'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ),
        'arm_replica' => array(
            'read' => array(
                'host' => env('ARM_DB_HOST', '127.0.0.1'),
                'port' => env('ARM_DB_PORT', '3306'),
            ),
            'write' => array(
                'host' => env('ARM_DB_HOST', '127.0.0.1'),
                'port' => env('ARM_DB_PORT', '3306'),
            ),
            'driver' => 'mysql',
            'database' => env('ARM_DB_DATABASE', 'armf2'),
            'username' => env('ARM_DB_USERNAME','alex'),
            'password' => env('ARM_DB_PASSWORD','12345'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ),
        'arm115' => array(
            'read' => array(
                'host' => env('ARM_115_DB_HOST', '127.0.0.1'),
                'port' => env('ARM_115_DB_PORT', '3306'),
            ),
            'write' => array(
                'host' => env('ARM_115_DB_HOST', '127.0.0.1'),
                'port' => env('ARM_115_DB_PORT', '3306'),
            ),
            'driver' => 'mysql',
            'database' => env('ARM_115_DB_DATABASE', 'armf'),
            'username' => env('ARM_115_DB_USERNAME','alex'),
            'password' => env('ARM_115_DB_PASSWORD','12345'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ),
        'oldevents' => array(
            'read' => array(
                'host' => env('OLDEVENTS_DB_HOST', '127.0.0.1'),
                'port' => env('OLDEVENTS_DB_PORT', '3306'),
            ),
            'write' => array(
                'host' => env('OLDEVENTS_DB_HOST', '127.0.0.1'),
                'port' => env('OLDEVENTS_DB_PORT', '3306'),
            ),
            'driver' => 'mysql',
            'database' => env('OLDEVENTS_DB_DATABASE', 'oldevents'),
            'username' => env('OLDEVENTS_DB_USERNAME','alex'),
            'password' => env('OLDEVENTS_DB_PASSWORD','12345'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ),
        'mysql_stat' => array(
            'read' => array(
                'host' => env('ARM_DB_HOST', '127.0.0.1'),
                'port' => env('ARM_DB_PORT', '3306'),
            ),
            'write' => array(
                'host' => env('ARM_DB_HOST', '127.0.0.1'),
                'port' => env('ARM_DB_PORT', '3306'),
            ),
            'driver' => 'mysql',
            'database' => env('ARM_DB_DATABASE', 'armf2'),
            'username' => env('ARM_DB_USERNAME','alex'),
            'password' => env('ARM_DB_PASSWORD','12345'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ),
        'spylogsDB' => array(
            'read' => array(
                'host' => env('SPYLOG_DB_HOST', '127.0.0.1'),
                'port' => env('SPYLOG_DB_PORT', '3306'),
            ),
            'write' => array(
                'host' => env('SPYLOG_DB_HOST', '127.0.0.1'),
                'port' => env('SPYLOG_DB_PORT', '3306'),
            ),
            'driver' => 'mysql',
            'database' => env('SPYLOG_DB_DATABASE', 'spylogdb'),
            'username' => env('SPYLOG_DB_USERNAME','alex'),
            'password' => env('SPYLOG_DB_PASSWORD','12345'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        )
    ],

    /*
      |--------------------------------------------------------------------------
      | Migration Repository Table
      |--------------------------------------------------------------------------
      |
      | This table keeps track of all the migrations that have already run for
      | your application. Using this information, we can determine which of
      | the migrations on disk haven't actually been run in the database.
      |
     */
    'migrations' => 'migrations',
    /*
      |--------------------------------------------------------------------------
      | Redis Databases
      |--------------------------------------------------------------------------
      |
      | Redis is an open source, fast, and advanced key-value store that also
      | provides a richer set of commands than a typical key-value systems
      | such as APC or Memcached. Laravel makes it easy to dig right in.
      |
     */
    'redis' => [

        'client' => 'predis',

        'options' => [
            'cluster' => 'redis',
        ],

        'clusters' => [
            'default' => [
                [
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'password' => env('REDIS_PASSWORD', null),
                    'port' => env('REDIS_PORT', 6379),
                    'database' => 0,
                ],
            ],
        ],

    ],
];
