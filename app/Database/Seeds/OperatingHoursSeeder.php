<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class OperatingHoursSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $rows = [
            // 0=Sunday: closed, extra confirmation
            ['institution_id' => 1, 'day_of_week' => 0, 'is_open' => 0, 'open_time' => null, 'close_time' => null, 'requires_extra_confirmation' => 1, 'created_at' => $now, 'updated_at' => $now],
            // 1=Monday: open 07:00-23:00
            ['institution_id' => 1, 'day_of_week' => 1, 'is_open' => 1, 'open_time' => '07:00:00', 'close_time' => '23:00:00', 'requires_extra_confirmation' => 0, 'created_at' => $now, 'updated_at' => $now],
            // 2=Tuesday
            ['institution_id' => 1, 'day_of_week' => 2, 'is_open' => 1, 'open_time' => '07:00:00', 'close_time' => '23:00:00', 'requires_extra_confirmation' => 0, 'created_at' => $now, 'updated_at' => $now],
            // 3=Wednesday
            ['institution_id' => 1, 'day_of_week' => 3, 'is_open' => 1, 'open_time' => '07:00:00', 'close_time' => '23:00:00', 'requires_extra_confirmation' => 0, 'created_at' => $now, 'updated_at' => $now],
            // 4=Thursday
            ['institution_id' => 1, 'day_of_week' => 4, 'is_open' => 1, 'open_time' => '07:00:00', 'close_time' => '23:00:00', 'requires_extra_confirmation' => 0, 'created_at' => $now, 'updated_at' => $now],
            // 5=Friday
            ['institution_id' => 1, 'day_of_week' => 5, 'is_open' => 1, 'open_time' => '07:00:00', 'close_time' => '23:00:00', 'requires_extra_confirmation' => 0, 'created_at' => $now, 'updated_at' => $now],
            // 6=Saturday: open 07:00-17:00, extra confirmation
            ['institution_id' => 1, 'day_of_week' => 6, 'is_open' => 1, 'open_time' => '07:00:00', 'close_time' => '17:00:00', 'requires_extra_confirmation' => 1, 'created_at' => $now, 'updated_at' => $now],
        ];

        $this->db->table('operating_hours')->insertBatch($rows);
    }
}
