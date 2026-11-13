<?php

namespace App\Services\Backup;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use RuntimeException;

final class NightlyDatabaseBackupService
{
    public function __construct(
        private readonly MySqlBackupDumper $dumper,
        private readonly HybridBackupCipher $cipher,
        private readonly DropboxBackupClient $dropbox,
        private readonly BackupRetentionPolicy $retention,
        private readonly string $localDir,
        private readonly int $localKeep,
        private readonly string $publicKeyPath,
        private readonly string $remoteRoot,
    ) {}

    /**
     * @return array{
     *     remote_path: string,
     *     sha256: string,
     *     encrypted_bytes: int,
     *     sql_bytes: int,
     *     duration_ms: int,
     *     local_path: string
     * }
     */
    public function run(?CarbonImmutable $now = null): array
    {
        $started = hrtime(true);
        $now ??= CarbonImmutable::now();

        $this->ensureLocalDir();

        $driver = Config::get('database.default');
        $connection = Config::get('database.connections.'.$driver);
        if (! is_array($connection) || ! in_array($connection['driver'] ?? '', ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('backup:run requires a MySQL/MariaDB default connection.');
        }

        $publicKey = $this->readPublicKey();

        $tmpBase = $this->localDir.'/tmp-'.$now->format('YmdHis');
        $sqlPath = $tmpBase.'.sql';
        $gzPath = $tmpBase.'.sql.gz';
        $encPath = $this->localDir.'/'.basename($tmpBase).'.sql.gz.enc';

        try {
            $this->dumper->dumpToFile([
                'host' => (string) ($connection['host'] ?? '127.0.0.1'),
                'port' => (string) ($connection['port'] ?? '3306'),
                'database' => (string) ($connection['database'] ?? ''),
                'username' => (string) ($connection['username'] ?? ''),
                'password' => (string) ($connection['password'] ?? ''),
                'unix_socket' => (string) ($connection['unix_socket'] ?? ''),
            ], $sqlPath);

            $sqlBytes = filesize($sqlPath);
            if ($sqlBytes === false) {
                throw new RuntimeException('Unable to measure SQL dump size.');
            }

            $this->gzipFile($sqlPath, $gzPath);

            $encMeta = $this->cipher->encryptFile($gzPath, $encPath, $publicKey);

            $remotePath = $this->retention->buildRemotePath($now);
            $this->dropbox->upload($encPath, $remotePath);

            $this->applyRemoteRetention($now);
            $this->applyLocalRetention();

            return [
                'remote_path' => $remotePath,
                'sha256' => $encMeta['sha256'],
                'encrypted_bytes' => $encMeta['bytes'],
                'sql_bytes' => $sqlBytes,
                'duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
                'local_path' => $encPath,
            ];
        } finally {
            foreach ([$sqlPath, $gzPath] as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }
    }

    private function ensureLocalDir(): void
    {
        if (! is_dir($this->localDir)) {
            if (! @mkdir($this->localDir, 0700, true) && ! is_dir($this->localDir)) {
                throw new RuntimeException('Unable to create local backup directory.');
            }
        }

        if (! @chmod($this->localDir, 0700)) {
            throw new RuntimeException('Unable to set permissions on local backup directory.');
        }
    }

    private function readPublicKey(): string
    {
        if ($this->publicKeyPath === '' || ! is_readable($this->publicKeyPath)) {
            throw new RuntimeException('BACKUP_PUBLIC_KEY_PATH is missing or unreadable.');
        }

        $pem = file_get_contents($this->publicKeyPath);
        if ($pem === false || trim($pem) === '') {
            throw new RuntimeException('Backup public key file is empty.');
        }

        return $pem;
    }

    private function gzipFile(string $source, string $dest): void
    {
        $in = fopen($source, 'rb');
        $out = gzopen($dest, 'wb9');
        if ($in === false || $out === false) {
            throw new RuntimeException('Unable to open files for gzip compression.');
        }

        while (! feof($in)) {
            $chunk = fread($in, 1024 * 1024);
            if ($chunk === false) {
                fclose($in);
                gzclose($out);
                throw new RuntimeException('Unable to read SQL dump during gzip.');
            }
            gzwrite($out, $chunk);
        }

        fclose($in);
        gzclose($out);
    }

    private function applyRemoteRetention(?CarbonImmutable $now): void
    {
        $entries = $this->dropbox->listBackups($this->remoteRoot);
        $paths = array_values(array_map(fn (array $row) => $row['path'], $entries));
        $delete = $this->retention->remotePathsToDelete($paths, $now);

        foreach ($delete as $path) {
            $this->dropbox->delete($path);
        }
    }

    private function applyLocalRetention(): void
    {
        $files = glob($this->localDir.'/*.sql.gz.enc') ?: [];
        $delete = $this->retention->localPathsToDelete($files, $this->localKeep);

        foreach ($delete as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
