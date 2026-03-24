<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(InstitutionSeeder::class);
        $this->call(AdminUserSeeder::class);
        $this->call(OperatingHoursSeeder::class);
    }
}
