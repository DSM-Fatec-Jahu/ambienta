<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsers extends Migration
{
    public function up(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS users (
                id                         BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
                institution_id             BIGINT UNSIGNED  NOT NULL,
                name                       VARCHAR(200)     NOT NULL,
                email                      VARCHAR(320)     NOT NULL,
                cellphone                  VARCHAR(20)      NULL DEFAULT NULL,
                password_hash              VARCHAR(255)     NULL DEFAULT NULL,
                google_id                  VARCHAR(100)     NULL DEFAULT NULL,
                avatar_url                 VARCHAR(500)     NULL DEFAULT NULL,
                role                       ENUM('role_requester','role_technician','role_coordinator','role_vice_director','role_director','role_admin')
                                                            NOT NULL DEFAULT 'role_requester',
                is_active                  TINYINT(1)       NOT NULL DEFAULT 1,
                login_attempts             TINYINT UNSIGNED NOT NULL DEFAULT 0,
                locked_until               DATETIME         NULL DEFAULT NULL,
                password_reset_token       VARCHAR(100)     NULL DEFAULT NULL,
                password_reset_expires_at  DATETIME         NULL DEFAULT NULL,
                last_login_at              DATETIME         NULL DEFAULT NULL,
                created_at                 DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at                 DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at                 DATETIME         NULL DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_users_email     (email),
                UNIQUE KEY uq_users_google_id (google_id),
                KEY idx_users_institution_id  (institution_id),
                KEY idx_users_role            (role),
                KEY idx_users_is_active       (is_active),
                CONSTRAINT fk_users_institution FOREIGN KEY (institution_id)
                    REFERENCES institutions(id) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $this->forge->dropTable('users', true);
    }
}
