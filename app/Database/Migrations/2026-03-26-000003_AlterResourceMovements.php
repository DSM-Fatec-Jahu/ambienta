<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sprint R1 — Alter resource_movements to match the full schema from DRS Módulo Recursos v1.0 §3.3.
 *
 * Adds:
 *   - movement_type ENUM(...) NOT NULL
 *   - booking_id    BIGINT UNSIGNED NULL FK→bookings
 *   - confirmed_by_id BIGINT UNSIGNED NULL FK→users
 *   - moved_at      DATETIME NOT NULL (populated from transferred_at for existing rows)
 *
 * Renames:
 *   - transferred_at → moved_at (via ADD + UPDATE + DROP approach for compatibility)
 *
 * Notes:
 *   - existing rows (old equipment transfers) get movement_type = 'room_allocation'
 */
class AlterResourceMovements extends Migration
{
    public function up(): void
    {
        $cols = array_column(
            $this->db->query("SHOW COLUMNS FROM `resource_movements`")->getResultArray(),
            'Field'
        );

        // Step 1: Add new columns only if they don't already exist (idempotent after partial run)
        if (!in_array('movement_type', $cols)) {
            $this->db->query("ALTER TABLE `resource_movements`
                ADD COLUMN `movement_type` VARCHAR(30) NULL DEFAULT NULL AFTER `resource_id`");
        }
        if (!in_array('booking_id', $cols)) {
            $this->db->query("ALTER TABLE `resource_movements`
                ADD COLUMN `booking_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `destination_room_id`");
        }
        if (!in_array('confirmed_by_id', $cols)) {
            $this->db->query("ALTER TABLE `resource_movements`
                ADD COLUMN `confirmed_by_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `handler_id`");
        }
        if (!in_array('moved_at', $cols)) {
            $this->db->query("ALTER TABLE `resource_movements`
                ADD COLUMN `moved_at` DATETIME NULL DEFAULT NULL");
        }

        // Step 2: Populate movement_type for existing rows
        $this->db->query("UPDATE `resource_movements` SET `movement_type` = 'room_allocation' WHERE `movement_type` IS NULL");

        // Step 3: Copy transferred_at → moved_at only if transferred_at still exists
        if (in_array('transferred_at', $cols)) {
            $this->db->query("UPDATE `resource_movements` SET `moved_at` = `transferred_at` WHERE `moved_at` IS NULL");
        }

        // Step 4: Make movement_type NOT NULL
        $this->db->query("ALTER TABLE `resource_movements` MODIFY COLUMN `movement_type` VARCHAR(30) NOT NULL");

        // Step 5: Drop the old transferred_at column if it still exists
        if (in_array('transferred_at', $cols)) {
            $this->db->query("ALTER TABLE `resource_movements` DROP COLUMN `transferred_at`");
        }

        // Step 6: Add FK for booking_id (skip if already exists)
        try {
            $this->db->query("
                ALTER TABLE `resource_movements`
                    ADD CONSTRAINT `fk_resource_movements_booking`
                        FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`)
                        ON UPDATE CASCADE ON DELETE SET NULL
            ");
        } catch (\Exception $e) {
            // Already exists — safe to ignore
        }

        // Step 7: Add FK for confirmed_by_id (skip if already exists)
        try {
            $this->db->query("
                ALTER TABLE `resource_movements`
                    ADD CONSTRAINT `fk_resource_movements_confirmed_by`
                        FOREIGN KEY (`confirmed_by_id`) REFERENCES `users`(`id`)
                        ON UPDATE CASCADE ON DELETE SET NULL
            ");
        } catch (\Exception $e) {
            // Already exists — safe to ignore
        }

        // Step 8: Add index on movement_type (skip if already exists)
        try {
            $this->db->query("ALTER TABLE `resource_movements` ADD KEY `idx_movement_type` (`movement_type`)");
        } catch (\Exception $e) {
            // Already exists — safe to ignore
        }
    }

    public function down(): void
    {
        // Drop new FKs and indexes
        $this->db->query("ALTER TABLE `resource_movements` DROP FOREIGN KEY IF EXISTS `fk_resource_movements_booking`");
        $this->db->query("ALTER TABLE `resource_movements` DROP FOREIGN KEY IF EXISTS `fk_resource_movements_confirmed_by`");
        $this->db->query("ALTER TABLE `resource_movements` DROP KEY IF EXISTS `idx_movement_type`");

        // Restore transferred_at column
        $this->db->query("ALTER TABLE `resource_movements` ADD COLUMN `transferred_at` DATETIME NULL DEFAULT NULL");
        $this->db->query("UPDATE `resource_movements` SET `transferred_at` = `moved_at`");

        // Drop added columns
        $this->db->query("
            ALTER TABLE `resource_movements`
                DROP COLUMN IF EXISTS `movement_type`,
                DROP COLUMN IF EXISTS `booking_id`,
                DROP COLUMN IF EXISTS `confirmed_by_id`,
                DROP COLUMN IF EXISTS `moved_at`
        ");
    }
}
