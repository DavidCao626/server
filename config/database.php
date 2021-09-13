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

    'default' => envOverload('DB_CONNECTION', 'mysql'),

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

        'testing' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ],

        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => envOverload('DB_DATABASE', base_path('database/database.sqlite')),
            'prefix'   => envOverload('DB_PREFIX', ''),
        ],

        'mysql' => [
            'driver'    => 'mysql',
            'host'      => envOverload('DB_HOST', '127.0.0.1'),
            'port'      => envOverload('DB_PORT', 3310),
            'database'  => envOverload('DB_DATABASE', 'eoffice10'),
            'username'  => envOverload('DB_USERNAME', 'root'),
            'password'  => envOverload('DB_PASSWORD', 'weoffice10'),
            'charset'   => envOverload('DB_CHARSET', 'utf8'),
            'collation' => envOverload('DB_COLLATION', 'utf8_general_ci'),
            'prefix'    => envOverload('DB_PREFIX', ''),
            'timezone'  => envOverload('DB_TIMEZONE', '+08:00'),
            'strict'    => envOverload('DB_STRICT_MODE', false),
            'options'   => array(
                PDO::ATTR_PERSISTENT => true,
            )
        ],
        'mysql_check' => [
            'driver'    => 'mysql',
            'host'      => envOverload('DB_HOST', '127.0.0.1'),
            'port'      => envOverload('DB_PORT', 3308),
            'database'  => envOverload('DB_DATABASE', 'eoffice10'),
            'username'  => envOverload('DB_USERNAME', 'root'),
            'password'  => envOverload('DB_PASSWORD', ''),
            'charset'   => envOverload('DB_CHARSET', 'utf8'),
            'collation' => envOverload('DB_COLLATION', 'utf8_general_ci'),
            'prefix'    => envOverload('DB_PREFIX', ''),
            'timezone'  => envOverload('DB_TIMEZONE', '+08:00'),
            'strict'    => envOverload('DB_STRICT_MODE', false),
            'options'   => array(
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_EMULATE_PREPARES  => true
            )
        ],
        'mysql_eoffice9' => [
            'driver'    => 'mysql',
            'host'      => envOverload('DB_HOST_EOFFICE9', 'localhost'),
            'port'      => envOverload('DB_PORT_EOFFICE9', 3310),
            'database'  => envOverload('DB_DATABASE_EOFFICE9', 'mysql_eoffice9'),
            'username'  => envOverload('DB_USERNAME_EOFFICE9', 'root'),
            'password'  => envOverload('DB_PASSWORD_EOFFICE9', 'weoffice10'),
            'charset'   => envOverload('DB_CHARSET', 'utf8'),
            'collation' => envOverload('DB_COLLATION', 'utf8_general_ci'),
            'prefix'    => envOverload('DB_PREFIX', ''),
            'timezone'  => envOverload('DB_TIMEZONE', '+00:00'),
            'strict'    => envOverload('DB_STRICT_MODE', false),
        ],

        'pgsql' => [
            'driver'   => 'pgsql',
            'host'     => envOverload('DB_HOST', 'localhost'),
            'port'     => envOverload('DB_PORT', 5432),
            'database' => envOverload('DB_DATABASE', 'forge'),
            'username' => envOverload('DB_USERNAME', 'forge'),
            'password' => envOverload('DB_PASSWORD', ''),
            'charset'  => envOverload('DB_CHARSET', 'utf8'),
            'prefix'   => envOverload('DB_PREFIX', ''),
            'schema'   => envOverload('DB_SCHEMA', 'public'),
        ],

        'sqlsrv' => [
            'driver'   => 'sqlsrv',
            'host'     => envOverload('DB_HOST', 'localhost'),
            'database' => envOverload('DB_DATABASE', 'forge'),
            'username' => envOverload('DB_USERNAME', 'forge'),
            'password' => envOverload('DB_PASSWORD', ''),
            'charset'  => envOverload('DB_CHARSET', 'utf8'),
            'prefix'   => envOverload('DB_PREFIX', ''),
        ],
        //连接外部数据库配置，动态赋值
        'external_database' => [
            
        ],
        'machine_database' => [
            
        ],

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
        'cluster' => envOverload('REDIS_CLUSTER', false),

        'default' => [
            'host'     => envOverload('REDIS_HOST', '127.0.0.1'),
            'port'     => envOverload('REDIS_PORT', 6379),
            'database' => envOverload('REDIS_DATABASE', 0),
            'password' => envOverload('REDIS_PASSWORD', null),
            'read_write_timeout' => 0
        ],

    ],

];
