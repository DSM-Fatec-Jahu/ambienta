<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRecurrenceToBookings extends Migration
{
    public function up(): void
    {
        $this->db->query("
            ALTER TABLE `bookings`
                ADD COLUMN `recurrence_type`      ENUM('none','daily','weekly') NOT NULL DEFAULT 'none'
                    AFTER `attendees_count`,
                ADD COLUMN `recurrence_end_date`  DATE        NULL DEFAULT NULL
                    AFTER `recurrence_type`,
                ADD COLUMN `recurrence_parent_id` INT UNSIGNED NULL DEFAULT NULL
                    AFTER `recurrence_end_date`,
                ADD INDEX `idx_bookings_recurrence_parent` (`recurrence_parent_id`)
        ");
    }

    public function down(): void
    {
        $this->db->query("
            ALTER TABLE `bookings`
                DROP INDEX  `idx_bookings_recurrence_parent`,
                DROP COLUMN `recurrence_parent_id`,
                DROP COLUMN `recurrence_end_date`,
                DROP COLUMN `recurrence_type`
        ");
    }
}
