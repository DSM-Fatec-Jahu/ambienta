<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserInvites extends Migration
{
    public function up(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS user_invites (
                id              BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
                institution_id  BIGINT UNSIGNED  NOT NULL,
                invited_by      BIGINT UNSIGNED  NOT NULL,
                email           VARCHAR(320)     NOT NULL,
                role            ENUM('role_requester','role_technician','role_coordinator',
                                     'role_vice_director','role_director','role_admin')
                                                 NOT NULL DEFAULT 'role_requester',
                token           VARCHAR(64)      NOT NULL,
                expires_at      DATETIME         NOT NULL,
                accepted_at     DATETIME         NULL DEFAULT NULL,
                created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_user_invites_token (token),
                KEY idx_user_invites_email       (email),
                KEY idx_user_invites_institution (institution_id),
                CONSTRAINT fk_user_invites_institution
                    FOREIGN KEY (institution_id) REFERENCES institutions(id)
                    ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT fk_user_invites_invited_by
                    FOREIGN KEY (invited_by) REFERENCES users(id)
                    ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $this->forge->dropTable('user_invites', true);
    }
}
