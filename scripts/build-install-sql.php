<?php

$schemaPath = __DIR__.'/../database/schema/sqlite-schema.sql';
$outPath = __DIR__.'/../docs/sql/install.sql';

$sql = file_get_contents($schemaPath);
$header = <<<SQL
-- Fancy Coffee single-client install (idempotent-friendly)
-- Generated from consolidated Laravel migrations. Prefer `php artisan migrate` on greenfield.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

SQL;

$footer = <<<SQL

INSERT IGNORE INTO migrations (migration, batch) VALUES
('0001_01_01_000010_single_client_application_schema', 1),
('0001_01_01_000020_create_permission_tables', 1);

INSERT IGNORE INTO business_settings (id, name, settings, created_at, updated_at) VALUES
(1, 'Fancy Coffee', JSON_OBJECT('theme', JSON_OBJECT('layout_key', 'cafe')), NOW(), NOW());

INSERT IGNORE INTO branches (id, code, name, name_ar, is_active, created_at, updated_at) VALUES
(1, 'jad-main', 'Jeddah Main', 'جدة الرئيسي', 1, NOW(), NOW());

SET FOREIGN_KEY_CHECKS=1;
SQL;

if (! is_dir(dirname($outPath))) {
    mkdir(dirname($outPath), 0755, true);
}

file_put_contents($outPath, $header.$sql.$footer);
echo "Wrote {$outPath}\n";
