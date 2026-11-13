<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use RuntimeException;

final class InstallSqlMysqlDatabase
{
    private static bool $booted = false;

    private static ?string $databaseName = null;

    public static function isAvailable(): bool
    {
        try {
            $pdo = new PDO(
                'mysql:host='.env('INSTALL_SQL_MYSQL_HOST', '127.0.0.1').';port='.env('INSTALL_SQL_MYSQL_PORT', '3306'),
                env('INSTALL_SQL_MYSQL_USER', 'coffee_test'),
                env('INSTALL_SQL_MYSQL_PASSWORD', 'coffee_test'),
                [PDO::ATTR_TIMEOUT => 2]
            );

            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public static function ensureConnection(): void
    {
        if (! self::isAvailable()) {
            throw new RuntimeException('MySQL is not available for install.sql integration tests.');
        }

        if (! self::$booted) {
            self::bootFresh();

            return;
        }

        self::applyConfig(self::$databaseName ?? env('INSTALL_SQL_MYSQL_DATABASE', 'coffee_install_sql_test'));
    }

    public static function bootFresh(): void
    {
        if (! self::isAvailable()) {
            throw new RuntimeException('MySQL is not available for install.sql integration tests.');
        }

        $database = env('INSTALL_SQL_MYSQL_DATABASE', 'coffee_install_sql_test');
        self::$databaseName = $database;
        $host = env('INSTALL_SQL_MYSQL_HOST', '127.0.0.1');
        $port = env('INSTALL_SQL_MYSQL_PORT', '3306');
        $user = env('INSTALL_SQL_MYSQL_USER', 'coffee_test');
        $password = env('INSTALL_SQL_MYSQL_PASSWORD', 'coffee_test');

        $admin = new PDO("mysql:host={$host};port={$port}", $user, $password);
        $admin->exec("DROP DATABASE IF EXISTS `{$database}`");
        $admin->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $installPath = function_exists('base_path')
            ? base_path('docs/sql/install.sql')
            : dirname(__DIR__, 2).'/docs/sql/install.sql';
        $escapedPath = escapeshellarg($installPath);
        $cmd = sprintf(
            'mysql -h %s -P %s -u %s -p%s %s < %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($password),
            escapeshellarg($database),
            $escapedPath
        );
        exec($cmd, $output, $exitCode);
        if ($exitCode !== 0) {
            throw new RuntimeException('install.sql import failed: '.implode("\n", $output));
        }

        self::applyConfig($database);
        self::$booted = true;
    }

    private static function applyConfig(string $database): void
    {
        $host = env('INSTALL_SQL_MYSQL_HOST', '127.0.0.1');
        $port = env('INSTALL_SQL_MYSQL_PORT', '3306');
        $user = env('INSTALL_SQL_MYSQL_USER', 'coffee_test');
        $password = env('INSTALL_SQL_MYSQL_PASSWORD', 'coffee_test');

        putenv('DB_CONNECTION=mysql');
        $_ENV['DB_CONNECTION'] = 'mysql';
        $_SERVER['DB_CONNECTION'] = 'mysql';
        putenv('DB_URL=');
        $_ENV['DB_URL'] = '';

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $host,
            'database.connections.mysql.port' => $port,
            'database.connections.mysql.database' => $database,
            'database.connections.mysql.username' => $user,
            'database.connections.mysql.password' => $password,
        ]);

        DB::purge('mysql');
        DB::purge('sqlite');
        DB::reconnect('mysql');
    }

    public static function booted(): bool
    {
        return self::$booted;
    }
}
