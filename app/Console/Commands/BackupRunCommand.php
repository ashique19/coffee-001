<?php

namespace App\Console\Commands;

use App\Services\Backup\NightlyDatabaseBackupService;
use Illuminate\Console\Command;
use Throwable;

class BackupRunCommand extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Dump MySQL, encrypt with hybrid public-key cipher, upload to Dropbox, apply retention.';

    public function handle(NightlyDatabaseBackupService $backup): int
    {
        try {
            $result = $backup->run();

            $this->info(sprintf(
                'backup ok remote=%s bytes=%d sha256=%s sql_bytes=%d duration_ms=%d local=%s',
                $result['remote_path'],
                $result['encrypted_bytes'],
                $result['sha256'],
                $result['sql_bytes'],
                $result['duration_ms'],
                $result['local_path'],
            ));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('backup failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
