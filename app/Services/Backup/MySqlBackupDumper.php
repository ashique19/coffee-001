<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Process;
use RuntimeException;

final class MySqlBackupDumper
{
    public function __construct(
        private readonly string $mysqldumpBinary,
    ) {}

    /**
     * @param  array{host: string, port: string, database: string, username: string, password: string, unix_socket?: string}  $config
     */
    public function dumpToFile(array $config, string $sqlOutputPath): void
    {
        $cnfPath = $this->writeTemporaryCnf($config);

        try {
            $command = [
                $this->mysqldumpBinary,
                '--defaults-extra-file='.$cnfPath,
                '--single-transaction',
                '--routines',
                '--no-tablespaces',
            ];

            if (! empty($config['unix_socket'])) {
                $command[] = '--socket='.$config['unix_socket'];
            } else {
                $command[] = '--host='.$config['host'];
                $command[] = '--port='.$config['port'];
            }

            $command[] = $config['database'];

            $result = Process::timeout(3600)->run($command);

            if (! $result->successful()) {
                throw new RuntimeException(trim($result->errorOutput()) !== ''
                    ? 'mysqldump failed.'
                    : 'mysqldump failed with exit code '.$result->exitCode().'.');
            }

            if (file_put_contents($sqlOutputPath, $result->output()) === false) {
                throw new RuntimeException('Unable to write SQL dump file.');
            }
        } finally {
            if (is_file($cnfPath)) {
                @unlink($cnfPath);
            }
        }
    }

    /**
     * @param  array{host: string, port: string, database: string, username: string, password: string, unix_socket?: string}  $config
     */
    private function writeTemporaryCnf(array $config): string
    {
        $path = tempnam(sys_get_temp_dir(), 'zatca-mysql-');
        if ($path === false) {
            throw new RuntimeException('Unable to allocate temporary MySQL defaults file.');
        }

        $lines = [
            '[client]',
            'user='.($config['username'] ?? ''),
            'password='.($config['password'] ?? ''),
        ];

        if (! empty($config['unix_socket'])) {
            $lines[] = 'socket='.$config['unix_socket'];
        } else {
            $lines[] = 'host='.($config['host'] ?? '127.0.0.1');
            $lines[] = 'port='.($config['port'] ?? '3306');
        }

        if (file_put_contents($path, implode("\n", $lines)."\n") === false) {
            throw new RuntimeException('Unable to write MySQL defaults file.');
        }

        if (! @chmod($path, 0600)) {
            throw new RuntimeException('Unable to set permissions on MySQL defaults file.');
        }

        return $path;
    }
}
