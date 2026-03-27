<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookings extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'institution_id'   => ['type' => 'INT', 'unsigned' => true],
            'user_id'          => ['type' => 'INT', 'unsigned' => true],
            'room_id'          => ['type' => 'INT', 'unsigned' => true],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 300],
            'description'      => ['type' => 'TEXT', 'null' => true],
            'date'             => ['type' => 'DATE'],
            'start_time'       => ['type' => 'TIME'],
            'end_time'         => ['type' => 'TIME'],
            'attendees_count'  => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 1],
            'status'           => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected', 'cancelled', 'absent'],
                'default'    => 'pending',
            ],
            'reviewed_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'reviewed_at'      => ['type' => 'DATETIME', 'null' => true],
            'review_notes'     => ['type' => 'TEXT', 'null' => true],
            'cancelled_at'     => ['type' => 'DATETIME', 'null' => true],
            'cancelled_reason' => ['type' => 'TEXT', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['institution_id', 'room_id', 'date']);
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->createTable('bookings', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('bookings', true);
    }
}
