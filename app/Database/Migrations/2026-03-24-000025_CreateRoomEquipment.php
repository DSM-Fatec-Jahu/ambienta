<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRoomEquipment extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'institution_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'room_id'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'equipment_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'quantity'       => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 1],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['room_id', 'equipment_id'], 'uq_room_equipment');
        $this->forge->addKey('institution_id');

        $this->forge->createTable('room_equipment', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('room_equipment', true);
    }
}
