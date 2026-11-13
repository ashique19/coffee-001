-- Fancy Coffee single-client install (MySQL 8+ / MariaDB 10.4+)
-- Generated from consolidated Laravel migrations via scripts/build-install-sql.php
-- Prefer `php artisan migrate` on greenfield hosts that can run Artisan.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE IF NOT EXISTS `migrations`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `migration` VARCHAR(255) NOT NULL,
  `batch` BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `cache`(
  `key` VARCHAR(255) NOT NULL,
  `value` TEXT NOT NULL,
  `expiration` BIGINT NOT NULL,
  primary key(`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `cache_expiration_index` on `cache`(`expiration`) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `cache_locks`(
  `key` VARCHAR(255) NOT NULL,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` BIGINT NOT NULL,
  primary key(`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `cache_locks_expiration_index` on `cache_locks`(`expiration`) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `jobs`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `queue` VARCHAR(255) NOT NULL,
  `payload` TEXT NOT NULL,
  `attempts` BIGINT NOT NULL,
  `reserved_at` BIGINT,
  `available_at` BIGINT NOT NULL,
  `created_at` BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `jobs_queue_index` on `jobs`(`queue`) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `job_batches`(
  `id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` BIGINT NOT NULL,
  `pending_jobs` BIGINT NOT NULL,
  `failed_jobs` BIGINT NOT NULL,
  `failed_job_ids` TEXT NOT NULL,
  `options` TEXT,
  `cancelled_at` BIGINT,
  `created_at` BIGINT NOT NULL,
  `finished_at` BIGINT,
  primary key(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `failed_jobs`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(255) NOT NULL,
  `connection` VARCHAR(255) NOT NULL,
  `queue` VARCHAR(255) NOT NULL,
  `payload` TEXT NOT NULL,
  `exception` TEXT NOT NULL,
  `failed_at` datetime not null default CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `failed_jobs_connection_queue_failed_at_index` on `failed_jobs`(
  `connection`,
  `queue`,
  `failed_at`
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE UNIQUE INDEX `failed_jobs_uuid_unique` on `failed_jobs`(`uuid`) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `business_settings`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `settings` JSON NOT NULL,
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `sessions`(
  `id` VARCHAR(255) NOT NULL,
  `user_id` BIGINT,
  `ip_address` VARCHAR(255),
  `user_agent` TEXT,
  `payload` TEXT NOT NULL,
  `last_activity` BIGINT NOT NULL,
  primary key(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `sessions_user_id_index` on `sessions`(`user_id`) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `sessions_last_activity_index` on `sessions`(`last_activity`) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `password_reset_tokens`(
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` datetime,
  primary key(`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `branches`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `name_ar` VARCHAR(255),
  `is_active` tinyint(1) not null default '1',
  `settings` JSON,
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE UNIQUE INDEX `branches_code_unique` on `branches`(`code`) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `staff_users`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `branch_id` BIGINT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `email_verified_at` datetime,
  `password` VARCHAR(255) NOT NULL,
  `is_active` tinyint(1) not null default '1',
  `remember_token` VARCHAR(255),
  `created_at` datetime,
  `updated_at` datetime,
  foreign key(`branch_id`) references `branches`(`id`) on delete set null
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE UNIQUE INDEX `staff_users_email_unique` on `staff_users`(`email`) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `customers`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(255),
  `email` VARCHAR(255),
  `vat_number` VARCHAR(255),
  `loyalty_points` BIGINT NOT NULL default '0',
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE UNIQUE INDEX `customers_phone_unique` on `customers`(`phone`) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `product_categories`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `branch_id` BIGINT,
  `name_en` VARCHAR(255) NOT NULL,
  `name_ar` VARCHAR(255),
  `name_bn` VARCHAR(255),
  `icon_key` VARCHAR(255),
  `description_en` TEXT,
  `description_ar` TEXT,
  `description_bn` TEXT,
  `sort_order` BIGINT NOT NULL default '0',
  `is_active` tinyint(1) not null default '1',
  `created_at` datetime,
  `updated_at` datetime,
  foreign key(`branch_id`) references `branches`(`id`) on delete set null
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `product_categories_branch_id_is_active_index` on `product_categories`(
  `branch_id`,
  `is_active`
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `products`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `branch_id` BIGINT,
  `product_category_id` BIGINT,
  `sku` VARCHAR(255),
  `item_type` VARCHAR(255) NOT NULL default 'item',
  `name_en` VARCHAR(255) NOT NULL,
  `name_ar` VARCHAR(255),
  `name_bn` VARCHAR(255),
  `image_url` VARCHAR(255),
  `description_en` TEXT,
  `description_ar` TEXT,
  `description_bn` TEXT,
  `price_ex_vat` DECIMAL(12,2) NOT NULL,
  `vat_rate` DECIMAL(12,2) NOT NULL default '15',
  `sort_order` BIGINT NOT NULL default '0',
  `is_active` tinyint(1) not null default '1',
  `show_on_homepage` tinyint(1) not null default '0',
  `show_on_menu` tinyint(1) not null default '1',
  `created_at` datetime,
  `updated_at` datetime,
  foreign key(`branch_id`) references `branches`(`id`) on delete set null,
  foreign key(`product_category_id`) references `product_categories`(`id`) on delete set null
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `products_branch_id_is_active_index` on `products`(
  `branch_id`,
  `is_active`
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `products_show_on_homepage_is_active_index` on `products`(
  `show_on_homepage`,
  `is_active`
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `products_show_on_menu_is_active_index` on `products`(
  `show_on_menu`,
  `is_active`
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `product_modifiers`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name_en` VARCHAR(255) NOT NULL,
  `name_ar` VARCHAR(255),
  `price_ex_vat` DECIMAL(12,2) NOT NULL default '0',
  `vat_rate` DECIMAL(12,2) NOT NULL default '15',
  `is_active` tinyint(1) not null default '1',
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `product_product_modifier`(
  `product_id` BIGINT NOT NULL,
  `product_modifier_id` BIGINT NOT NULL,
  foreign key(`product_id`) references `products`(`id`) on delete cascade,
  foreign key(`product_modifier_id`) references `product_modifiers`(`id`) on delete cascade,
  primary key(`product_id`, `product_modifier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `invoices`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `branch_id` BIGINT,
  `document_number` VARCHAR(255) NOT NULL,
  `document_type` VARCHAR(255) NOT NULL,
  `status` VARCHAR(255) NOT NULL default 'draft',
  `referenced_invoice_id` BIGINT,
  `source_type` VARCHAR(255),
  `source_id` BIGINT,
  `icv` BIGINT,
  `invoice_hash` VARCHAR(255),
  `previous_invoice_hash` VARCHAR(255),
  `ubl_xml` TEXT,
  `qr_tlv_base64` TEXT,
  `payload` TEXT,
  `issued_at` datetime,
  `created_at` datetime,
  `updated_at` datetime,
  foreign key(`branch_id`) references `branches`(`id`) on delete set null,
  foreign key(`referenced_invoice_id`) references `invoices`(`id`) on delete set null
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `invoices_source_type_source_id_index` on `invoices`(
  `source_type`,
  `source_id`
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `invoices_status_issued_at_index` on `invoices`(
  `status`,
  `issued_at`
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE UNIQUE INDEX `invoices_document_number_unique` on `invoices`(
  `document_number`
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `invoice_audit_logs`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` BIGINT NOT NULL,
  `event` VARCHAR(255) NOT NULL,
  `context` TEXT,
  `created_at` datetime,
  `updated_at` datetime,
  foreign key(`invoice_id`) references `invoices`(`id`) on delete cascade
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pos_orders`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `branch_id` BIGINT NOT NULL,
  `customer_id` BIGINT,
  `cashier_id` BIGINT,
  `order_number` VARCHAR(255) NOT NULL,
  `status` VARCHAR(255) NOT NULL default 'open',
  `payment_method` VARCHAR(255),
  `subtotal_ex_vat` DECIMAL(12,2) NOT NULL default '0',
  `discount_ex_vat` DECIMAL(12,2) NOT NULL default '0',
  `promo_discount_ex_vat` DECIMAL(12,2) NOT NULL default '0',
  `loyalty_discount_ex_vat` DECIMAL(12,2) NOT NULL default '0',
  `vat_total` DECIMAL(12,2) NOT NULL default '0',
  `total_inc_vat` DECIMAL(12,2) NOT NULL default '0',
  `loyalty_points_redeemed` BIGINT NOT NULL default '0',
  `line_items` TEXT NOT NULL,
  `metadata` TEXT,
  `invoice_id` BIGINT,
  `refund_credit_note_id` BIGINT,
  `paid_at` datetime,
  `cancelled_at` datetime,
  `created_at` datetime,
  `updated_at` datetime,
  foreign key(`branch_id`) references `branches`(`id`),
  foreign key(`customer_id`) references `customers`(`id`) on delete set null,
  foreign key(`cashier_id`) references `staff_users`(`id`) on delete set null,
  foreign key(`invoice_id`) references `invoices`(`id`) on delete set null,
  foreign key(`refund_credit_note_id`) references `invoices`(`id`) on delete set null
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `pos_orders_branch_id_status_index` on `pos_orders`(
  `branch_id`,
  `status`
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE UNIQUE INDEX `pos_orders_order_number_unique` on `pos_orders`(
  `order_number`
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `loyalty_transactions`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `customer_id` BIGINT NOT NULL,
  `pos_order_id` BIGINT,
  `points_delta` BIGINT NOT NULL,
  `reason` VARCHAR(255),
  `created_at` datetime,
  `updated_at` datetime,
  foreign key(`customer_id`) references `customers`(`id`) on delete cascade,
  foreign key(`pos_order_id`) references `pos_orders`(`id`) on delete set null
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `ingredients`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `branch_id` BIGINT,
  `name_en` VARCHAR(255) NOT NULL,
  `name_ar` VARCHAR(255),
  `name_bn` VARCHAR(255),
  `unit` VARCHAR(255) NOT NULL default 'g',
  `quantity_on_hand` DECIMAL(12,2) NOT NULL default '0',
  `reorder_level` DECIMAL(12,2) NOT NULL default '0',
  `low_stock_threshold` DECIMAL(12,2) NOT NULL default '0',
  `is_active` tinyint(1) not null default '1',
  `created_at` datetime,
  `updated_at` datetime,
  foreign key(`branch_id`) references `branches`(`id`) on delete set null
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `inventory_movements`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `branch_id` BIGINT NOT NULL,
  `ingredient_id` BIGINT NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `reason` VARCHAR(255),
  `quantity_delta` DECIMAL(12,2) NOT NULL,
  `reference_type` VARCHAR(255),
  `reference_id` BIGINT,
  `user_id` BIGINT,
  `pos_order_id` BIGINT,
  `created_at` datetime,
  `updated_at` datetime,
  foreign key(`branch_id`) references `branches`(`id`) on delete cascade,
  foreign key(`ingredient_id`) references `ingredients`(`id`) on delete cascade,
  foreign key(`user_id`) references `staff_users`(`id`) on delete set null,
  foreign key(`pos_order_id`) references `pos_orders`(`id`) on delete set null
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `product_ingredients`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `product_id` BIGINT NOT NULL,
  `ingredient_id` BIGINT NOT NULL,
  `quantity_per_unit` DECIMAL(12,2) NOT NULL,
  `created_at` datetime,
  `updated_at` datetime,
  foreign key(`product_id`) references `products`(`id`) on delete cascade,
  foreign key(`ingredient_id`) references `ingredients`(`id`) on delete cascade
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE UNIQUE INDEX `product_ingredients_product_id_ingredient_id_unique` on `product_ingredients`(
  `product_id`,
  `ingredient_id`
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `shifts`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `branch_id` BIGINT NOT NULL,
  `staff_user_id` BIGINT NOT NULL,
  `started_at` datetime not null,
  `opening_cash_float` DECIMAL(12,2),
  `closing_cash_counted` DECIMAL(12,2),
  `close_notes` TEXT,
  `ended_at` datetime,
  `metadata` TEXT,
  `created_at` datetime,
  `updated_at` datetime,
  foreign key(`branch_id`) references `branches`(`id`) on delete cascade,
  foreign key(`staff_user_id`) references `staff_users`(`id`) on delete cascade
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `business_settings_audit_logs`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `staff_user_id` BIGINT,
  `tab` VARCHAR(255) NOT NULL,
  `snapshot` TEXT,
  `created_at` datetime,
  `updated_at` datetime,
  foreign key(`staff_user_id`) references `staff_users`(`id`) on delete set null
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `permissions`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `guard_name` VARCHAR(255) NOT NULL,
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE UNIQUE INDEX `permissions_name_guard_name_unique` on `permissions`(
  `name`,
  `guard_name`
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `roles`(
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `guard_name` VARCHAR(255) NOT NULL,
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE UNIQUE INDEX `roles_name_guard_name_unique` on `roles`(
  `name`,
  `guard_name`
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `model_has_permissions`(
  `permission_id` BIGINT NOT NULL,
  `model_type` VARCHAR(255) NOT NULL,
  `model_id` BIGINT NOT NULL,
  foreign key(`permission_id`) references `permissions`(`id`) on delete cascade,
  primary key(`permission_id`, `model_id`, `model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `model_has_permissions_model_id_model_type_index` on `model_has_permissions`(
  `model_id`,
  `model_type`
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `model_has_roles`(
  `role_id` BIGINT NOT NULL,
  `model_type` VARCHAR(255) NOT NULL,
  `model_id` BIGINT NOT NULL,
  foreign key(`role_id`) references `roles`(`id`) on delete cascade,
  primary key(`role_id`, `model_id`, `model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `model_has_roles_model_id_model_type_index` on `model_has_roles`(
  `model_id`,
  `model_type`
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `role_has_permissions`(
  `permission_id` BIGINT NOT NULL,
  `role_id` BIGINT NOT NULL,
  foreign key(`permission_id`) references `permissions`(`id`) on delete cascade,
  foreign key(`role_id`) references `roles`(`id`) on delete cascade,
  primary key(`permission_id`, `role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO migrations (migration, batch) VALUES
('0001_01_01_000001_create_cache_table', 1),
('0001_01_01_000002_create_jobs_table', 1),
('0001_01_01_000010_single_client_application_schema', 1),
('0001_01_01_000020_create_permission_tables', 1);

INSERT IGNORE INTO business_settings (id, name, settings, created_at, updated_at) VALUES
(1, 'Fancy Coffee', JSON_OBJECT('theme', JSON_OBJECT('layout_key', 'cafe')), NOW(), NOW());

INSERT IGNORE INTO branches (id, code, name, name_ar, is_active, created_at, updated_at) VALUES
(1, 'jad-main', 'Jeddah Main', 'جدة الرئيسي', 1, NOW(), NOW());

SET FOREIGN_KEY_CHECKS=1;