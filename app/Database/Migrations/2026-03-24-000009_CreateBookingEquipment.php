<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingEquipment extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'booking_id'   => ['type' => 'INT', 'unsigned' => true],
            'equipment_id' => ['type' => 'INT', 'unsigned' => true],
            'quantity'     => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['booking_id', 'equipment_id']);
        $this->forge->createTable('booking_equipment');
    }

    public function down(): void
    {
        $this->forge->dropTable('booking_equipment', true);
    }
}
