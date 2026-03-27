<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sprint R1 — Rename equipment-related tables and columns to resource terminology.
 *
 * Renames:
 *   equipment            → resources
 *   equipment_transfers  → resource_movements
 *   room_equipment       → room_resources
 *   booking_equipment    → booking_resources
 *
 * Column renames (in each table):
 *   resource_movements:  equipment_id → resource_id
 *   room_resources:      equipment_id → resource_id
 *   booking_resources:   equipment_id → resource_id
 */
class RenameEquipmentToResources extends Migration
{
    public function up(): void
    {
        // ── 1. Rename tables ──────────────────────────────────────────────────
        $this->db->query('RENAME TABLE `equipment` TO `resources`');
        $this->db->query('RENAME TABLE `equipment_transfers` TO `resource_movements`');
        $this->db->query('RENAME TABLE `room_equipment` TO `room_resources`');
        $this->db->query('RENAME TABLE `booking_equipment` TO `booking_resources`');

        // ── 2. resource_movements: rename equipment_id → resource_id ──────────
        $this->db->query("
            ALTER TABLE `resource_movements`
                CHANGE COLUMN `equipment_id` `resource_id` BIGINT UNSIGNED NOT NULL
        ");

        // ── 3. room_resources: rename equipment_id → resource_id + rename unique key
        $this->db->query("
            ALTER TABLE `room_resources`
                CHANGE COLUMN `equipment_id` `resource_id` BIGINT UNSIGNED NOT NULL
        ");

        // Rename the unique key
        $this->db->query("
            ALTER TABLE `room_resources`
                DROP KEY `uq_room_equipment`,
                ADD UNIQUE KEY `uq_room_resource` (`room_id`, `resource_id`)
        ");

        // ── 4. booking_resources: rename equipment_id → resource_id ──────────
        $this->db->query("
            ALTER TABLE `booking_resources`
                CHANGE COLUMN `equipment_id` `resource_id` BIGINT UNSIGNED NOT NULL
        ");

        // Update the composite index on booking_resources (idempotent)
        try {
            $this->db->query("ALTER TABLE `booking_resources` DROP KEY `booking_id`");
        } catch (\Exception $e) { /* may already be gone */ }
        try {
            $this->db->query("ALTER TABLE `booking_resources` ADD KEY `booking_resource_idx` (`booking_id`, `resource_id`)");
        } catch (\Exception $e) { /* already exists */ }
    }

    public function down(): void
    {
        // ── 4. booking_resources → booking_equipment ─────────────────────────
        // Restore original index state
        try {
            $this->db->query("ALTER TABLE `booking_resources` DROP KEY `booking_resource_idx`");
        } catch (\Exception $e) { /* may not exist */ }
        try {
            $this->db->query("ALTER TABLE `booking_resources` ADD KEY `booking_id` (`booking_id`)");
        } catch (\Exception $e) { /* may already exist */ }
        $this->db->query("
            ALTER TABLE `booking_resources`
                CHANGE COLUMN `resource_id` `equipment_id` BIGINT UNSIGNED NOT NULL
        ");

        // ── 3. room_resources → room_equipment ───────────────────────────────
        $this->db->query("
            ALTER TABLE `room_resources`
                DROP KEY `uq_room_resource`,
                ADD UNIQUE KEY `uq_room_equipment` (`room_id`, `resource_id`)
        ");
        $this->db->query("
            ALTER TABLE `room_resources`
                CHANGE COLUMN `resource_id` `equipment_id` BIGINT UNSIGNED NOT NULL
        ");

        // ── 2. resource_movements → equipment_transfers ───────────────────────
        $this->db->query("
            ALTER TABLE `resource_movements`
                CHANGE COLUMN `resource_id` `equipment_id` BIGINT UNSIGNED NOT NULL
        ");

        // ── 1. Rename tables back ─────────────────────────────────────────────
        $this->db->query('RENAME TABLE `booking_resources` TO `booking_equipment`');
        $this->db->query('RENAME TABLE `room_resources` TO `room_equipment`');
        $this->db->query('RENAME TABLE `resource_movements` TO `equipment_transfers`');
        $this->db->query('RENAME TABLE `resources` TO `equipment`');
    }
}
