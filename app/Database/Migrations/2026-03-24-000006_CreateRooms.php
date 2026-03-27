<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRooms extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                       => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'institution_id'           => ['type' => 'INT', 'unsigned' => true],
            'building_id'              => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'name'                     => ['type' => 'VARCHAR', 'constraint' => 200],
            'code'                     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'capacity'                 => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'floor'                    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'description'              => ['type' => 'TEXT', 'null' => true],
            'allows_equipment_lending' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'image_path'               => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'is_active'                => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'deleted_at'               => ['type' => 'DATETIME', 'null' => true],
            'created_at'               => ['type' => 'DATETIME', 'null' => true],
            'updated_at'               => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('institution_id');
        $this->forge->addKey('building_id');
        $this->forge->createTable('rooms', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('rooms', true);
    }
}
