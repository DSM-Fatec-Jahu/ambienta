<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEquipmentTransfers extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'institution_id'      => ['type' => 'INT', 'unsigned' => true],
            'equipment_id'        => ['type' => 'INT', 'unsigned' => true],
            'quantity'            => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 1],
            'origin_room_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'destination_room_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'transferred_by'      => ['type' => 'INT', 'unsigned' => true],
            'notes'               => ['type' => 'TEXT', 'null' => true],
            'transferred_at'      => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('equipment_id');
        $this->forge->addKey('institution_id');
        $this->forge->createTable('equipment_transfers');
    }

    public function down(): void
    {
        $this->forge->dropTable('equipment_transfers', true);
    }
}
