<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingComments extends Migration
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
            'booking_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'body' => [
                'type'       => 'TEXT',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('booking_id');
        $this->forge->addKey('institution_id');

        $this->forge->createTable('booking_comments', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('booking_comments', true);
    }
}
