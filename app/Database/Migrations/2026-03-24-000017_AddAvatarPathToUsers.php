<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAvatarPathToUsers extends Migration
{
    public function up(): void
    {
        $this->db->query("
            ALTER TABLE users
            ADD COLUMN avatar_path VARCHAR(300) NULL DEFAULT NULL
                AFTER avatar_url
        ");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE users DROP COLUMN avatar_path");
    }
}
