<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sprint R1 — Alter room_resources to add allocation tracking columns.
 *
 * Adds:
 *   - allocated_by_id BIGINT UNSIGNED NULL FK→users
 *   - allocated_at    DATETIME NULL (populated from created_at for existing rows)
 */
class AlterRoomResources extends Migration
{
    public function up(): void
    {
        $this->db->query("
            ALTER TABLE `room_resources`
                ADD COLUMN `allocated_by_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `quantity`,
                ADD COLUMN `allocated_at`    DATETIME         NULL DEFAULT NULL AFTER `allocated_by_id`
        ");

        // Populate allocated_at from existing created_at data
        $this->db->query("UPDATE `room_resources` SET `allocated_at` = COALESCE(`created_at`, NOW())");

        // FK: allocated_by_id → users
        $this->db->query("
            ALTER TABLE `room_resources`
                ADD CONSTRAINT `fk_room_resources_allocated_by`
                    FOREIGN KEY (`allocated_by_id`) REFERENCES `users`(`id`)
                    ON UPDATE CASCADE ON DELETE SET NULL
        ");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE `room_resources` DROP FOREIGN KEY IF EXISTS `fk_room_resources_allocated_by`");
        $this->db->query("
            ALTER TABLE `room_resources`
                DROP COLUMN IF EXISTS `allocated_by_id`,
                DROP COLUMN IF EXISTS `allocated_at`
        ");
    }
}
