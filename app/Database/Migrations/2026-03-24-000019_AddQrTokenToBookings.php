<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddQrTokenToBookings extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('bookings', [
            'qr_token' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
                'unique'     => true,
                'after'      => 'checkin_at',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('bookings', 'qr_token');
    }
}
