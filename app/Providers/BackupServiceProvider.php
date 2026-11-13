<?php

namespace App\Providers;

use App\Services\Backup\BackupRetentionPolicy;
use App\Services\Backup\DropboxBackupClient;
use App\Services\Backup\HybridBackupCipher;
use App\Services\Backup\MySqlBackupDumper;
use App\Services\Backup\NightlyDatabaseBackupService;
use Illuminate\Support\ServiceProvider;

class BackupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HybridBackupCipher::class);
        $this->app->singleton(MySqlBackupDumper::class, function () {
            return new MySqlBackupDumper(config('backup.mysqldump_binary'));
        });

        $this->app->singleton(DropboxBackupClient::class, function () {
            $dropbox = config('backup.dropbox');

            return new DropboxBackupClient(
                (string) ($dropbox['app_key'] ?? ''),
                (string) ($dropbox['app_secret'] ?? ''),
                (string) ($dropbox['refresh_token'] ?? ''),
                (int) ($dropbox['simple_upload_max_bytes'] ?? 150 * 1024 * 1024),
                (int) ($dropbox['upload_chunk_bytes'] ?? 8 * 1024 * 1024),
            );
        });

        $this->app->singleton(BackupRetentionPolicy::class, function () {
            return new BackupRetentionPolicy(
                (int) config('backup.remote_daily_keep'),
                (int) config('backup.remote_monthly_keep_months'),
            );
        });

        $this->app->singleton(NightlyDatabaseBackupService::class, function ($app) {
            $dropbox = config('backup.dropbox');

            return new NightlyDatabaseBackupService(
                $app->make(MySqlBackupDumper::class),
                $app->make(HybridBackupCipher::class),
                $app->make(DropboxBackupClient::class),
                $app->make(BackupRetentionPolicy::class),
                (string) config('backup.local_dir'),
                (int) config('backup.local_keep'),
                (string) config('backup.public_key_path'),
                (string) ($dropbox['remote_root'] ?? '/zatca'),
            );
        });
    }
}
