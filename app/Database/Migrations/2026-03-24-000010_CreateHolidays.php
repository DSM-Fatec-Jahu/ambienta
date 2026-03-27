<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHolidays extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'institution_id' => ['type' => 'INT', 'unsigned' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 200],
            'date'           => ['type' => 'DATE'],
            'is_recurring'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0,
                                 'comment' => 'Se 1, repete todo ano neste dia/mês (ano ignorado)'],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['institution_id', 'date']);
        $this->forge->createTable('holidays', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('holidays', true);
    }
}
