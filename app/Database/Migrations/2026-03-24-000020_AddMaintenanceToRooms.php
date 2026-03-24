<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class AddMaintenanceToRooms extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('rooms', [
            'maintenance_mode' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after'   => 'is_active',
            ],
            'maintenance_until' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null,
                'after' => 'maintenance_mode',
            ],
            'maintenance_reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'default'    => null,
                'after'      => 'maintenance_until',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('rooms', ['maintenance_mode', 'maintenance_until', 'maintenance_reason']);
    }
}
