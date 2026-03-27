<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sprint R1 — Alter resources table (renamed from equipment).
 *
 * Adds:
 *   - category VARCHAR(80) NULL
 *   - created_by_id BIGINT UNSIGNED NULL FK→users
 *   - updated_by_id BIGINT UNSIGNED NULL FK→users
 *
 * Modifies:
 *   - code: VARCHAR(20) → VARCHAR(50), ensure UNIQUE
 *
 * Adds constraint:
 *   - chk_qty_patrimonio: code IS NULL OR quantity_total = 1
 */
class AlterResourcesTable extends Migration
{
    public function up(): void
    {
        $this->db->query("
            ALTER TABLE `resources`
                ADD COLUMN  `category`       VARCHAR(80)      NULL DEFAULT NULL AFTER `description`,
                ADD COLUMN  `created_by_id`  BIGINT UNSIGNED  NULL DEFAULT NULL AFTER `is_active`,
                ADD COLUMN  `updated_by_id`  BIGINT UNSIGNED  NULL DEFAULT NULL AFTER `created_by_id`,
                MODIFY COLUMN `code`         VARCHAR(50)      NULL DEFAULT NULL
        ");

        // Add unique index on code (if not already present; code may already be unique from original migration)
        // We check existence to be idempotent
        $indexes = $this->db->query("SHOW INDEX FROM `resources` WHERE Column_name = 'code'")->getResultArray();
        $hasUniqueOnCode = false;
        foreach ($indexes as $idx) {
            if ($idx['Non_unique'] == 0) {
                $hasUniqueOnCode = true;
                break;
            }
        }
        if (!$hasUniqueOnCode) {
            $this->db->query("ALTER TABLE `resources` ADD UNIQUE KEY `uq_resources_code` (`code`)");
        }

        // Add CHECK constraint (supported in MySQL 8.0.16+ and MariaDB 10.2.1+)
        // Use a try/catch to handle older versions gracefully
        try {
            $this->db->query("
                ALTER TABLE `resources`
                    ADD CONSTRAINT `chk_qty_patrimonio`
                        CHECK (`code` IS NULL OR `quantity_total` = 1)
            ");
        } catch (\Exception $e) {
            // Older MySQL/MariaDB versions that don't support CHECK constraints — skip silently.
            // Validation is enforced at application level.
            log_message('info', 'AlterResourcesTable: chk_qty_patrimonio constraint not added (not supported): ' . $e->getMessage());
        }

        // FK: created_by_id → users
        $this->db->query("
            ALTER TABLE `resources`
                ADD CONSTRAINT `fk_resources_created_by`
                    FOREIGN KEY (`created_by_id`) REFERENCES `users`(`id`)
                    ON UPDATE CASCADE ON DELETE SET NULL
        ");

        // FK: updated_by_id → users
        $this->db->query("
            ALTER TABLE `resources`
                ADD CONSTRAINT `fk_resources_updated_by`
                    FOREIGN KEY (`updated_by_id`) REFERENCES `users`(`id`)
                    ON UPDATE CASCADE ON DELETE SET NULL
        ");
    }

    public function down(): void
    {
        // Drop FKs
        $this->db->query("ALTER TABLE `resources` DROP FOREIGN KEY IF EXISTS `fk_resources_created_by`");
        $this->db->query("ALTER TABLE `resources` DROP FOREIGN KEY IF EXISTS `fk_resources_updated_by`");

        // Drop CHECK constraint (MariaDB uses DROP CONSTRAINT, not DROP CHECK)
        try {
            $this->db->query("ALTER TABLE `resources` DROP CONSTRAINT `chk_qty_patrimonio`");
        } catch (\Exception $e) {
            // Constraint may not exist (was not added on older DB versions)
        }

        // Revert code column width
        $this->db->query("ALTER TABLE `resources` MODIFY COLUMN `code` VARCHAR(20) NULL DEFAULT NULL");

        // Drop added columns
        $this->db->query("
            ALTER TABLE `resources`
                DROP COLUMN IF EXISTS `category`,
                DROP COLUMN IF EXISTS `created_by_id`,
                DROP COLUMN IF EXISTS `updated_by_id`
        ");
    }
}
