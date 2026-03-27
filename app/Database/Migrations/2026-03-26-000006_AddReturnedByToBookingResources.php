<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sprint R5 — RN-R05/RN-R07: track who registered the return in booking_resources.
 *
 * Adds:
 *   - returned_by_id  BIGINT UNSIGNED NULL  — user who called returnResource()
 */
class AddReturnedByToBookingResources extends Migration
{
    public function up(): void
    {
        $this->db->query("
            ALTER TABLE `booking_resources`
                ADD COLUMN `returned_by_id` BIGINT UNSIGNED NULL DEFAULT NULL
                AFTER `returned_at`
        ");

        try {
            $this->db->query("
                ALTER TABLE `booking_resources`
                    ADD CONSTRAINT `fk_booking_resources_returned_by`
                        FOREIGN KEY (`returned_by_id`) REFERENCES `users`(`id`)
                        ON UPDATE CASCADE ON DELETE SET NULL
            ");
        } catch (\Exception $e) {
            // FK may already exist or table may not support it
        }
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE `booking_resources` DROP FOREIGN KEY IF EXISTS `fk_booking_resources_returned_by`");
        $this->db->query("ALTER TABLE `booking_resources` DROP COLUMN IF EXISTS `returned_by_id`");
    }
}
