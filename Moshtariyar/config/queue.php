<?php

return [

    /*
    | ═══════════════════════════════════════════════════════
    | پیش‌ران پیش‌فرض صف
    | ═══════════════════════════════════════════════════════
    |
    | گزینه‌ها: database (پیش‌فرض) | redis (سرعت بالا)
    |
    | ◉ database: روی هر cPanel کار می‌کند، نیازی به نصب ندارد.
    |    برای ۵۰۰+ کار در دقیقه مناسب است.
    |
    | ◉ redis: برای ۵۰۰۰+ کار در دقیقه. نیاز به نصب Redis دارد.
    |    با تغییر QUEUE_CONNECTION=redis در .env فعال می‌شود.
    |
    */
    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    | ═══════════════════════════════════════════════════════
    | اتصال‌های صف
    | ═══════════════════════════════════════════════════════
    */
    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver'       => 'database',
            'connection'   => env('DB_QUEUE_CONNECTION'),
            'table'        => env('DB_QUEUE_TABLE', 'jobs'),
            'queue'        => env('DB_QUEUE', 'default'),
            'retry_after'  => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        /*
        | ── Redis — برای سامانه‌های پرترافیک ──
        | سرعت پردازش صف با Redis تا ۱۰ برابر سریع‌تر از دیتابیس است
        */
        'redis' => [
            'driver'       => 'redis',
            'connection'   => 'default',
            'queue'        => env('REDIS_QUEUE', 'default'),
            'retry_after'  => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for'    => null,
            'after_commit' => false,
        ],

    ],

    /*
    | ═══════════════════════════════════════════════════════
    | بسته‌بندی کارها (Batching)
    | ═══════════════════════════════════════════════════════
    */
    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table'    => 'job_batches',
    ],

    /*
    | ═══════════════════════════════════════════════════════
    | کارهای شکست‌خورده
    | ═══════════════════════════════════════════════════════
    */
    'failed' => [
        'driver'   => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table'    => 'failed_jobs',
    ],

];