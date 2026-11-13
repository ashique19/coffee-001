<?php

namespace Tests\Support;

final class InstallSqlSchemaParity
{
    /**
     * @return array<string, list<string>>
     */
    public static function sqliteColumnsByTable(): array
    {
        return self::parseCreateTables(base_path('database/schema/sqlite-schema.sql'));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function installColumnsByTable(): array
    {
        return self::parseCreateTables(base_path('docs/sql/install.sql'));
    }

    /**
     * @return list<string> human-readable mismatch lines
     */
    public static function mismatchDescriptions(): array
    {
        $sqlite = self::sqliteColumnsByTable();
        $install = self::installColumnsByTable();
        $tables = array_unique(array_merge(array_keys($sqlite), array_keys($install)));
        sort($tables);

        $lines = [];
        foreach ($tables as $table) {
            $a = $sqlite[$table] ?? [];
            $b = $install[$table] ?? [];
            if ($a === [] && $b === []) {
                continue;
            }
            if ($a === []) {
                $lines[] = "{$table}: missing CREATE TABLE in sqlite-schema.sql";

                continue;
            }
            if ($b === []) {
                $lines[] = "{$table}: missing CREATE TABLE in docs/sql/install.sql";

                continue;
            }
            $missingInInstall = array_values(array_diff($a, $b));
            $extraInInstall = array_values(array_diff($b, $a));
            if ($missingInInstall !== []) {
                $lines[] = "{$table}: install.sql missing columns: ".implode(', ', $missingInInstall);
            }
            if ($extraInInstall !== []) {
                $lines[] = "{$table}: install.sql extra columns: ".implode(', ', $extraInInstall);
            }
        }

        return $lines;
    }

    /**
     * @return array<string, list<string>>
     */
  private static function parseCreateTables(string $path): array
    {
        $sql = file_get_contents($path);
        if (! is_string($sql)) {
            return [];
        }

        preg_match_all(
            '/CREATE TABLE IF NOT EXISTS [`"](\w+)[`"]\s*\((.*?)\)\s*(?:ENGINE|;)/is',
            $sql,
            $matches,
            PREG_SET_ORDER
        );

        $tables = [];
        foreach ($matches as $match) {
            $table = $match[1];
            $body = $match[2];
            $cols = [];
            foreach (preg_split("/,\s*\n/", $body) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || preg_match('/^(primary key|foreign key|unique|constraint|check|CREATE)/i', $line)) {
                    continue;
                }
                if (preg_match('/^[`"]?(\w+)[`"]?/i', $line, $col)) {
                    $cols[] = strtolower($col[1]);
                }
            }
            $tables[$table] = $cols;
        }

        return $tables;
    }
}
