<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Local backup storage
    |--------------------------------------------------------------------------
    */

    'local_dir' => storage_path('backups'),

    'local_keep' => (int) env('BACKUP_LOCAL_KEEP', 3),

    'public_key_path' => env('BACKUP_PUBLIC_KEY_PATH'),

    'mysqldump_binary' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),

    /*
    |--------------------------------------------------------------------------
    | Dropbox (app-folder) off-site upload
    |--------------------------------------------------------------------------
    */

    'dropbox' => [
        'app_key' => env('DROPBOX_APP_KEY'),
        'app_secret' => env('DROPBOX_APP_SECRET'),
        'refresh_token' => env('DROPBOX_REFRESH_TOKEN'),
        'simple_upload_max_bytes' => 150 * 1024 * 1024,
        'upload_chunk_bytes' => 8 * 1024 * 1024,
        'remote_root' => '/zatca',
    ],

    'remote_daily_keep' => (int) env('BACKUP_REMOTE_DAILY_KEEP', 14),

    'remote_monthly_keep_months' => (int) env('BACKUP_REMOTE_MONTHLY_KEEP_MONTHS', 12),

];
