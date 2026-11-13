<?php

namespace App\Console\Commands;

use App\Services\Backup\HybridBackupCipher;
use Illuminate\Console\Command;
use Throwable;

class BackupDecryptCommand extends Command
{
    protected $signature = 'backup:decrypt {file : Path to .sql.gz.enc backup file} {--private-key= : PEM path (default stdin not supported)}';

    protected $description = 'Decrypt a hybrid backup to .sql.gz (run on owner machine with the private key).';

    public function handle(HybridBackupCipher $cipher): int
    {
        $encPath = $this->argument('file');
        $keyPath = $this->option('private-key');

        if (! is_string($encPath) || ! is_readable($encPath)) {
            $this->error('Encrypted backup file is missing or unreadable.');

            return self::FAILURE;
        }

        if (! is_string($keyPath) || $keyPath === '' || ! is_readable($keyPath)) {
            $this->error('Provide --private-key= path to the RSA private PEM.');

            return self::FAILURE;
        }

        $pem = file_get_contents($keyPath);
        if ($pem === false || trim($pem) === '') {
            $this->error('Private key file is empty.');

            return self::FAILURE;
        }

        $outPath = preg_replace('/\.enc$/', '', $encPath) ?? $encPath.'.gz';

        try {
            $cipher->decryptFile($encPath, $outPath, $pem);
            $this->info('Decrypted gzip written to: '.$outPath);
            $this->line('Gunzip then import: gzip -dc '.basename($outPath).' | mysql -u ... database_name');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Decrypt failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
