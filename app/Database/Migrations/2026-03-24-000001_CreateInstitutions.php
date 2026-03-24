<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInstitutions extends Migration
{
    public function up(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS institutions (
                id          BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
                name        VARCHAR(200)     NOT NULL,
                slug        VARCHAR(100)     NOT NULL,
                logo_path   VARCHAR(500)     NULL DEFAULT NULL,
                settings    JSON             NOT NULL,
                created_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at  DATETIME         NULL DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_institutions_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $this->forge->dropTable('institutions', true);
    }
}
