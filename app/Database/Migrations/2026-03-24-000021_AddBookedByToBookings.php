<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class AddBookedByToBookings extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('bookings', [
            'booked_by_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'user_id',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('bookings', 'booked_by_user_id');
    }
}
