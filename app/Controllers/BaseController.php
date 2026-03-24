<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected $helpers = ['url', 'form', 'html'];

    /** Institution data shared with all views. */
    protected array $institution = [];

    public function initController(
        RequestInterface  $request,
        ResponseInterface $response,
        LoggerInterface   $logger
    ): void {
        parent::initController($request, $response, $logger);
        $this->loadInstitution();
    }

    protected function loadInstitution(): void
    {
        try {
            $db  = db_connect();
            $row = $db->table('institutions')->where('deleted_at', null)->get()->getRowArray();
            $this->institution = $row ?? [];
        } catch (\Throwable) {
            $this->institution = [];
        }

        if (!empty($this->institution['settings']) && is_string($this->institution['settings'])) {
            $this->institution['settings_decoded'] = json_decode($this->institution['settings'], true) ?? [];
        } else {
            $this->institution['settings_decoded'] = [];
        }
    }

    /**
     * Returns data common to all views (institution, labels, current user).
     */
    protected function viewData(array $extra = []): array
    {
        $settings = $this->institution['settings_decoded'] ?? [];
        $user     = $this->currentUser();

        // Sidebar pending badge — only for staff roles, lazy-loaded from DB
        $pendingBadge = 0;
        $staffRoles   = ['role_technician','role_coordinator','role_vice_director','role_director','role_admin'];
        if ($user && in_array($user['role'] ?? '', $staffRoles)) {
            try {
                $pendingBadge = (int) db_connect()
                    ->table('bookings')
                    ->where('institution_id', $this->institution['id'] ?? 0)
                    ->where('status', 'pending')
                    ->where('deleted_at IS NULL')
                    ->countAllResults();
            } catch (\Throwable) {
                $pendingBadge = 0;
            }
        }

        return array_merge([
            'institution'  => $this->institution,
            'rolesLabels'  => $settings['roles_labels'] ?? [
                'role_requester'     => 'Solicitante',
                'role_technician'    => 'Resp. Técnico / Apoio',
                'role_coordinator'   => 'Coordenador',
                'role_vice_director' => 'Vice-diretor',
                'role_director'      => 'Diretor',
                'role_admin'         => 'Administrador',
            ],
            'currentUser'  => $user,
            'pendingBadge' => $pendingBadge,
        ], $extra);
    }

    protected function currentUser(): ?array
    {
        $id = session()->get('user_id');
        if (!$id) {
            return null;
        }

        return [
            'id'          => $id,
            'name'        => session()->get('user_name'),
            'email'       => session()->get('user_email'),
            'role'        => session()->get('user_role'),
            'avatar_url'  => session()->get('user_avatar'),
            'avatar_path' => session()->get('user_avatar_path'),
        ];
    }

    protected function setUserSession(array $user): void
    {
        session()->set([
            'user_id'          => $user['id'],
            'user_name'        => $user['name'],
            'user_email'       => $user['email'],
            'user_role'        => $user['role'],
            'user_avatar'      => $user['avatar_url']  ?? null,
            'user_avatar_path' => $user['avatar_path'] ?? null,
        ]);
        session()->regenerate(true);
    }

    protected function getAuthSettings(): array
    {
        return $this->institution['settings_decoded']['auth'] ?? [
            'sso_google_enabled'   => false,
            'local_login_enabled'  => true,
            'sso_allowed_domains'  => [],
        ];
    }
}
