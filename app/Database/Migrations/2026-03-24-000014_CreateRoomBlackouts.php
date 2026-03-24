<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRoomBlackouts extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'institution_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'room_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'comment'    => 'NULL = bloqueia todos os ambientes da instituição',
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'reason' => [
                'type'       => 'TEXT',
                'null'       => true,
                'default'    => null,
            ],
            'starts_at' => [
                'type' => 'DATETIME',
            ],
            'ends_at' => [
                'type' => 'DATETIME',
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('institution_id');
        $this->forge->addKey('room_id');
        $this->forge->addKey(['starts_at', 'ends_at']);

        $this->forge->createTable('room_blackouts');
    }

    public function down(): void
    {
        $this->forge->dropTable('room_blackouts');
    }
}
