<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'auth' => [
                'sso_google_enabled'      => true,
                'sso_google_client_id'    => '',
                'sso_google_client_secret'=> '',
                'sso_allowed_domains'     => ['fatecjahu.edu.br'],
                'local_login_enabled'     => true,
            ],
            'smtp' => [
                'host'       => '',
                'port'       => 587,
                'encryption' => 'tls',
                'username'   => '',
                'password'   => '',
                'from_email' => '',
                'from_name'  => '',
            ],
            'reservations' => [
                'max_advance_days_individual' => 15,
                'cancellation_min_hours'      => 24,
                'default_requires_approval'   => true,
            ],
            'roles_labels' => [
                'role_requester'     => 'Solicitante',
                'role_technician'    => 'Resp. Técnico / Apoio',
                'role_coordinator'   => 'Coordenador',
                'role_vice_director' => 'Vice-diretor',
                'role_director'      => 'Diretor',
                'role_admin'         => 'Administrador',
            ],
        ];

        $this->db->table('institutions')->insert([
            'id'         => 1,
            'name'       => 'Minha Instituição',
            'slug'       => 'minha-instituicao',
            'logo_path'  => null,
            'settings'   => json_encode($settings),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
