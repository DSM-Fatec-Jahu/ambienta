<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Default admin password: Admin@1234 (change after first login)
        $passwordHash = password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 12]);

        $this->db->table('users')->insert([
            'institution_id' => 1,
            'name'           => 'Administrador',
            'email'          => 'admin@ambienta.local',
            'password_hash'  => $passwordHash,
            'role'           => 'role_admin',
            'is_active'      => 1,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
    }
}
