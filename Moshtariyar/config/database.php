<?php

return [

    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [

        'mysql' => [
            'driver'         => 'mysql',
            'host'           => env('DB_HOST', '127.0.0.1'),
            'port'           => env('DB_PORT', '3306'),
            'database'       => env('DB_DATABASE', 'crm'),
            'username'       => env('DB_USERNAME', 'root'),
            'password'       => env('DB_PASSWORD', ''),
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_unicode_ci',
            'prefix'         => '',
            'strict'         => true,
            'engine'         => 'InnoDB',
            /*
            | ═══════════════════════════════════════════════
            | بهینه‌سازی برای سرعت بالا
            | ═══════════════════════════════════════════════
            */
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA          => env('MYSQL_ATTR_SSL_CA'),
                PDO::MYSQL_ATTR_COMPRESS        => true,
                PDO::ATTR_EMULATE_PREPARES      => false,
                PDO::ATTR_PERSISTENT            => env('DB_PERSISTENT', true),
            ]) : [],
            'modes' => [
                'STRICT_TRANS_TABLES',
                'NO_ZERO_IN_DATE',
                'NO_ZERO_DATE',
                'ERROR_FOR_DIVISION_BY_ZERO',
                'NO_ENGINE_SUBSTITUTION',
            ],
        ],

    ],

    'migrations' => [
        'table'                  => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    | ═══════════════════════════════════════════════════════
    | اتصال‌های Redis
    | ═══════════════════════════════════════════════════════
    */
    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => 'redis',
            'prefix'  => env('REDIS_PREFIX', 'crm_'),
        ],

        'default' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', 0),
        ],

        /*
        | ── اتصال اختصاصی برای کش ──
        | جدا کردن دیتابیس Redis کش از صف مانع تداخل می‌شود
        */
        'cache' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', 1),
        ],

    ],

];