<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingWaitlist extends Migration
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
            ],
            'date' => [
                'type' => 'DATE',
            ],
            'starts_at' => [
                'type'       => 'VARCHAR',
                'constraint' => 8,
                'comment'    => 'HH:MM:SS',
            ],
            'ends_at' => [
                'type'       => 'VARCHAR',
                'constraint' => 8,
                'comment'    => 'HH:MM:SS',
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'notes' => [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
            ],
            'notified_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'comment' => 'Set when the user is notified that the slot is available',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('institution_id');
        $this->forge->addKey(['room_id', 'date', 'starts_at', 'ends_at']);
        $this->forge->addKey('user_id');
        // One entry per user per slot
        $this->forge->addUniqueKey(['room_id', 'date', 'starts_at', 'ends_at', 'user_id']);

        $this->forge->createTable('booking_waitlist', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('booking_waitlist');
    }
}
