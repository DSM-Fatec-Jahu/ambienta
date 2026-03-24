<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBuildings extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'institution_id' => ['type' => 'INT', 'unsigned' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 200],
            'code'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'description'    => ['type' => 'TEXT', 'null' => true],
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('institution_id');
        $this->forge->createTable('buildings');
    }

    public function down(): void
    {
        $this->forge->dropTable('buildings', true);
    }
}
