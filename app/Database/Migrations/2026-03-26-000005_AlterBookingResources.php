<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sprint R1 — Alter booking_resources to add full approval/return workflow columns.
 *
 * Adds:
 *   - status          ENUM('pending','approved','rejected','returned','return_confirmed') DEFAULT 'pending'
 *   - approved_by_id  BIGINT UNSIGNED NULL
 *   - rejected_by_id  BIGINT UNSIGNED NULL
 *   - rejection_note  TEXT NULL
 *   - returned_at     DATETIME NULL
 *   - confirmed_at    DATETIME NULL
 *   - confirmed_by_id BIGINT UNSIGNED NULL
 *   - created_at      DATETIME NULL
 *   - updated_at      DATETIME NULL
 *   - notified_at     DATETIME NULL  (for ResourceReturnReminderJob deduplication — Sprint R6)
 */
class AlterBookingResources extends Migration
{
    public function up(): void
    {
        $this->db->query("
            ALTER TABLE `booking_resources`
                ADD COLUMN `status`          ENUM('pending','approved','rejected','returned','return_confirmed')
                                             NOT NULL DEFAULT 'pending'            AFTER `quantity`,
                ADD COLUMN `approved_by_id`  BIGINT UNSIGNED  NULL DEFAULT NULL    AFTER `status`,
                ADD COLUMN `rejected_by_id`  BIGINT UNSIGNED  NULL DEFAULT NULL    AFTER `approved_by_id`,
                ADD COLUMN `rejection_note`  TEXT             NULL DEFAULT NULL    AFTER `rejected_by_id`,
                ADD COLUMN `returned_at`     DATETIME         NULL DEFAULT NULL    AFTER `rejection_note`,
                ADD COLUMN `confirmed_at`    DATETIME         NULL DEFAULT NULL    AFTER `returned_at`,
                ADD COLUMN `confirmed_by_id` BIGINT UNSIGNED  NULL DEFAULT NULL    AFTER `confirmed_at`,
                ADD COLUMN `notified_at`     DATETIME         NULL DEFAULT NULL    AFTER `confirmed_by_id`,
                ADD COLUMN `created_at`      DATETIME         NULL DEFAULT NULL    AFTER `notified_at`,
                ADD COLUMN `updated_at`      DATETIME         NULL DEFAULT NULL    AFTER `created_at`
        ");

        // Populate timestamps for existing rows
        $this->db->query("UPDATE `booking_resources` SET `created_at` = NOW(), `updated_at` = NOW() WHERE `created_at` IS NULL");

        // FKs for new columns
        $this->db->query("
            ALTER TABLE `booking_resources`
                ADD CONSTRAINT `fk_booking_resources_approved_by`
                    FOREIGN KEY (`approved_by_id`) REFERENCES `users`(`id`)
                    ON UPDATE CASCADE ON DELETE SET NULL,
                ADD CONSTRAINT `fk_booking_resources_rejected_by`
                    FOREIGN KEY (`rejected_by_id`) REFERENCES `users`(`id`)
                    ON UPDATE CASCADE ON DELETE SET NULL,
                ADD CONSTRAINT `fk_booking_resources_confirmed_by`
                    FOREIGN KEY (`confirmed_by_id`) REFERENCES `users`(`id`)
                    ON UPDATE CASCADE ON DELETE SET NULL
        ");

        // Index on status for querying pending/returned items
        $this->db->query("ALTER TABLE `booking_resources` ADD KEY `idx_booking_resources_status` (`status`)");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE `booking_resources` DROP FOREIGN KEY IF EXISTS `fk_booking_resources_approved_by`");
        $this->db->query("ALTER TABLE `booking_resources` DROP FOREIGN KEY IF EXISTS `fk_booking_resources_rejected_by`");
        $this->db->query("ALTER TABLE `booking_resources` DROP FOREIGN KEY IF EXISTS `fk_booking_resources_confirmed_by`");
        $this->db->query("ALTER TABLE `booking_resources` DROP KEY IF EXISTS `idx_booking_resources_status`");

        $this->db->query("
            ALTER TABLE `booking_resources`
                DROP COLUMN IF EXISTS `status`,
                DROP COLUMN IF EXISTS `approved_by_id`,
                DROP COLUMN IF EXISTS `rejected_by_id`,
                DROP COLUMN IF EXISTS `rejection_note`,
                DROP COLUMN IF EXISTS `returned_at`,
                DROP COLUMN IF EXISTS `confirmed_at`,
                DROP COLUMN IF EXISTS `confirmed_by_id`,
                DROP COLUMN IF EXISTS `notified_at`,
                DROP COLUMN IF EXISTS `created_at`,
                DROP COLUMN IF EXISTS `updated_at`
        ");
    }
}
