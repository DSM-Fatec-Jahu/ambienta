<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOperatingHours extends Migration
{
    public function up(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS operating_hours (
                id                          BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
                institution_id              BIGINT UNSIGNED  NOT NULL,
                day_of_week                 TINYINT UNSIGNED NOT NULL COMMENT '0=Sunday...6=Saturday',
                is_open                     TINYINT(1)       NOT NULL DEFAULT 1,
                open_time                   TIME             NULL DEFAULT NULL,
                close_time                  TIME             NULL DEFAULT NULL,
                requires_extra_confirmation TINYINT(1)       NOT NULL DEFAULT 0,
                created_at                  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at                  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_operating_hours_institution_day (institution_id, day_of_week),
                CONSTRAINT fk_operating_hours_institution FOREIGN KEY (institution_id)
                    REFERENCES institutions(id) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $this->forge->dropTable('operating_hours', true);
    }
}
