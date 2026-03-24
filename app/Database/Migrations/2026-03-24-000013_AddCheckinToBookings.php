<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCheckinToBookings extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('bookings', [
            'checkin_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'default'    => null,
                'after'      => 'cancelled_reason',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('bookings', 'checkin_at');
    }
}
