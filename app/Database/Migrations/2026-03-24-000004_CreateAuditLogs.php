<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogs extends Migration
{
    public function up(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS audit_logs (
                id           BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
                actor_id     BIGINT UNSIGNED  NULL DEFAULT NULL COMMENT 'NULL = automated system action',
                action       VARCHAR(200)     NOT NULL,
                entity_type  VARCHAR(100)     NOT NULL,
                entity_id    BIGINT UNSIGNED  NULL DEFAULT NULL,
                old_values   JSON             NULL DEFAULT NULL,
                new_values   JSON             NULL DEFAULT NULL,
                ip_address   VARCHAR(45)      NULL DEFAULT NULL,
                user_agent   VARCHAR(500)     NULL DEFAULT NULL,
                created_at   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_audit_actor_id   (actor_id),
                KEY idx_audit_action     (action),
                KEY idx_audit_entity     (entity_type, entity_id),
                KEY idx_audit_created_at (created_at),
                CONSTRAINT fk_audit_actor FOREIGN KEY (actor_id)
                    REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Append-only compliance log; rows must never be modified or deleted'
        ");
    }

    public function down(): void
    {
        $this->forge->dropTable('audit_logs', true);
    }
}
