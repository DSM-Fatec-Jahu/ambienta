<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sprint R1 — Rename equipment-related tables and columns to resource terminology.
 *
 * Fully idempotent: skips any step whose source object is already gone or whose
 * target already exists. Safe to run on a database that was bootstrapped directly
 * with the new names OR on one that still has the old names.
 *
 * Renames (only when source exists and target does not):
 *   equipment            → resources
 *   equipment_transfers  → resource_movements
 *   room_equipment       → room_resources
 *   booking_equipment    → booking_resources
 *
 * Column renames (only when old column still present):
 *   resource_movements: equipment_id → resource_id
 *   room_resources:     equipment_id → resource_id
 *   booking_resources:  equipment_id → resource_id
 *
 * Key renames (only when old key present):
 *   room_resources: uq_room_equipment → uq_room_resource
 */
class RenameEquipmentToResources extends Migration
{
    // ── helpers ──────────────────────────────────────────────────────────────

    private function tableExists(string $table): bool
    {
        return (bool) $this->db->query("SHOW TABLES LIKE '{$table}'")->getRow();
    }

    private function columnExists(string $table, string $column): bool
    {
        $cols = array_column(
            $this->db->query("SHOW COLUMNS FROM `{$table}`")->getResultArray(),
            'Field'
        );
        return in_array($column, $cols, true);
    }

    private function indexExists(string $table, string $keyName): bool
    {
        return !empty(
            $this->db->query(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = '{$keyName}'"
            )->getResultArray()
        );
    }

    // ── up ───────────────────────────────────────────────────────────────────

    public function up(): void
    {
        // ── 1. Rename tables (skip if source absent or target already exists) ──

        if ($this->tableExists('equipment') && !$this->tableExists('resources')) {
            $this->db->query('RENAME TABLE `equipment` TO `resources`');
        }

        if ($this->tableExists('equipment_transfers') && !$this->tableExists('resource_movements')) {
            $this->db->query('RENAME TABLE `equipment_transfers` TO `resource_movements`');
        }

        if ($this->tableExists('room_equipment') && !$this->tableExists('room_resources')) {
            $this->db->query('RENAME TABLE `room_equipment` TO `room_resources`');
        }

        if ($this->tableExists('booking_equipment') && !$this->tableExists('booking_resources')) {
            $this->db->query('RENAME TABLE `booking_equipment` TO `booking_resources`');
        }

        // ── 2. resource_movements: rename equipment_id → resource_id ──────────

        if ($this->columnExists('resource_movements', 'equipment_id')) {
            $this->db->query("
                ALTER TABLE `resource_movements`
                    CHANGE COLUMN `equipment_id` `resource_id` BIGINT UNSIGNED NOT NULL
            ");
        }

        // ── 3. room_resources: rename equipment_id → resource_id ─────────────

        if ($this->columnExists('room_resources', 'equipment_id')) {
            $this->db->query("
                ALTER TABLE `room_resources`
                    CHANGE COLUMN `equipment_id` `resource_id` BIGINT UNSIGNED NOT NULL
            ");
        }

        // Rename unique key uq_room_equipment → uq_room_resource if still old name
        if ($this->indexExists('room_resources', 'uq_room_equipment')) {
            $this->db->query("
                ALTER TABLE `room_resources`
                    DROP KEY `uq_room_equipment`,
                    ADD UNIQUE KEY `uq_room_resource` (`room_id`, `resource_id`)
            ");
        }

        // ── 4. booking_resources: rename equipment_id → resource_id ──────────

        if ($this->columnExists('booking_resources', 'equipment_id')) {
            $this->db->query("
                ALTER TABLE `booking_resources`
                    CHANGE COLUMN `equipment_id` `resource_id` BIGINT UNSIGNED NOT NULL
            ");
        }

        // Composite index on booking_resources (idempotent via try/catch)
        try {
            $this->db->query('ALTER TABLE `booking_resources` DROP KEY `booking_id`');
        } catch (\Exception $e) { /* may already be gone */ }

        if (!$this->indexExists('booking_resources', 'booking_resource_idx')) {
            try {
                $this->db->query('ALTER TABLE `booking_resources` ADD KEY `booking_resource_idx` (`booking_id`, `resource_id`)');
            } catch (\Exception $e) { /* already exists */ }
        }
    }

    // ── down ─────────────────────────────────────────────────────────────────

    public function down(): void
    {
        // ── 4. booking_resources → booking_equipment ─────────────────────────
        try {
            $this->db->query('ALTER TABLE `booking_resources` DROP KEY `booking_resource_idx`');
        } catch (\Exception $e) { /* may not exist */ }
        try {
            $this->db->query('ALTER TABLE `booking_resources` ADD KEY `booking_id` (`booking_id`)');
        } catch (\Exception $e) { /* may already exist */ }

        if ($this->columnExists('booking_resources', 'resource_id')) {
            $this->db->query("
                ALTER TABLE `booking_resources`
                    CHANGE COLUMN `resource_id` `equipment_id` BIGINT UNSIGNED NOT NULL
            ");
        }

        // ── 3. room_resources → room_equipment ───────────────────────────────
        if ($this->indexExists('room_resources', 'uq_room_resource')) {
            $this->db->query("
                ALTER TABLE `room_resources`
                    DROP KEY `uq_room_resource`,
                    ADD UNIQUE KEY `uq_room_equipment` (`room_id`, `resource_id`)
            ");
        }

        if ($this->columnExists('room_resources', 'resource_id')) {
            $this->db->query("
                ALTER TABLE `room_resources`
                    CHANGE COLUMN `resource_id` `equipment_id` BIGINT UNSIGNED NOT NULL
            ");
        }

        // ── 2. resource_movements → equipment_transfers ───────────────────────
        if ($this->columnExists('resource_movements', 'resource_id')) {
            $this->db->query("
                ALTER TABLE `resource_movements`
                    CHANGE COLUMN `resource_id` `equipment_id` BIGINT UNSIGNED NOT NULL
            ");
        }

        // ── 1. Rename tables back ─────────────────────────────────────────────
        if ($this->tableExists('booking_resources') && !$this->tableExists('booking_equipment')) {
            $this->db->query('RENAME TABLE `booking_resources` TO `booking_equipment`');
        }
        if ($this->tableExists('room_resources') && !$this->tableExists('room_equipment')) {
            $this->db->query('RENAME TABLE `room_resources` TO `room_equipment`');
        }
        if ($this->tableExists('resource_movements') && !$this->tableExists('equipment_transfers')) {
            $this->db->query('RENAME TABLE `resource_movements` TO `equipment_transfers`');
        }
        if ($this->tableExists('resources') && !$this->tableExists('equipment')) {
            $this->db->query('RENAME TABLE `resources` TO `equipment`');
        }
    }
}
