<?php

if (strcmp(env('APP_ENV'), 'production') === 0) {

    return [

        'default' => 'mysql',

        'connections' => [

            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST'),
                'port' => 3306,
                'database' => env('DB_DATABASE'),
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
                'engine' => null,
                'options' => array(
                    PDO::ATTR_EMULATE_PREPARES => TRUE,
                    PDO::ATTR_PERSISTENT => TRUE
                )
            ],
            'mysqlread' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST'),
                'port' => 3306,
                'database' => env('DB_DATABASE'),
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
                'engine' => null,
                'options' => array(
                    PDO::ATTR_EMULATE_PREPARES => TRUE,
                    PDO::ATTR_PERSISTENT => TRUE
                )
            ],
        ],
        //'migrations' => 'migrations',
        'redis' => [
            'client' => 'predis',
            'default' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
                'database' => env('REDIS_DB', 0),
            ],
            'cache' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
                'database' => env('REDIS_CACHE_DB', 1),
            ],
        ]

    ];

} else {

    return [

        'default' => 'mysql',

        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST'),
                'port' => 3306,
                'database' => env('DB_DATABASE'),
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
                'engine' => null,
                'options' => array(
                    PDO::ATTR_EMULATE_PREPARES => TRUE,
                    PDO::ATTR_PERSISTENT => TRUE
                )
            ],
            'mysqlread' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST'),
                'port' => 3306,
                'database' => env('DB_DATABASE'),
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
                'engine' => null,
                'options' => array(
                    PDO::ATTR_EMULATE_PREPARES => TRUE,
                    PDO::ATTR_PERSISTENT => TRUE
                )
            ]
        ],
        //'migrations' => 'migrations',
        'redis' => [
            'client' => 'predis',
            'default' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
                'database' => env('REDIS_DB', 0),
            ],
            'cache' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
                'database' => env('REDIS_CACHE_DB', 1),
            ],
        ]
    ];
}
