<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\UserInviteModel;

class UsersController extends BaseController
{
    private UserModel       $users;
    private UserInviteModel $invites;

    private const ROLES = [
        'role_requester'     => 'Solicitante',
        'role_technician'    => 'Resp. Técnico / Apoio',
        'role_coordinator'   => 'Coordenador',
        'role_vice_director' => 'Vice-diretor',
        'role_director'      => 'Diretor',
        'role_admin'         => 'Administrador',
    ];

    public function __construct()
    {
        $this->users   = new UserModel();
        $this->invites = new UserInviteModel();
    }

    public function index(): string
    {
        $institutionId = $this->institution['id'] ?? 0;

        $search = $this->request->getGet('q');

        $query = $this->users->where('institution_id', $institutionId);

        if ($search) {
            $query->groupStart()
                ->like('name', $search)
                ->orLike('email', $search)
                ->groupEnd();
        }

        $items = $query->orderBy('name', 'ASC')->findAll();

        return view('admin/users/index', $this->viewData([
            'pageTitle'  => 'Usuários',
            'items'      => $items,
            'rolesList'  => self::ROLES,
            'search'     => $search,
        ]));
    }

    public function updateRole(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $user          = $this->users->where('institution_id', $institutionId)->find($id);

        if (!$user) {
            return redirect()->to(base_url('admin/usuarios'))->with('error', 'Usuário não encontrado.');
        }

        $role = $this->request->getPost('role');
        if (!array_key_exists($role, self::ROLES)) {
            return redirect()->to(base_url('admin/usuarios'))->with('error', 'Perfil inválido.');
        }

        // Protect the only admin
        $currentUser = $this->currentUser();
        if ($id === (int) $currentUser['id'] && $role !== 'role_admin') {
            return redirect()->to(base_url('admin/usuarios'))
                ->with('error', 'Você não pode alterar seu próprio perfil de administrador.');
        }

        $this->users->update($id, ['role' => $role]);

        return redirect()->to(base_url('admin/usuarios'))
            ->with('success', "Perfil de {$user['name']} atualizado para «" . self::ROLES[$role] . "».");
    }

    public function toggleActive(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $user          = $this->users->where('institution_id', $institutionId)->find($id);

        if (!$user) {
            return redirect()->to(base_url('admin/usuarios'))->with('error', 'Usuário não encontrado.');
        }

        $currentUser = $this->currentUser();
        if ($id === (int) $currentUser['id']) {
            return redirect()->to(base_url('admin/usuarios'))
                ->with('error', 'Você não pode desativar sua própria conta.');
        }

        $newStatus = $user['is_active'] ? 0 : 1;
        $this->users->update($id, ['is_active' => $newStatus]);

        $label = $newStatus ? 'ativado' : 'desativado';
        return redirect()->to(base_url('admin/usuarios'))
            ->with('success', "Usuário {$user['name']} {$label} com sucesso.");
    }

    public function invite(): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $currentUser   = $this->currentUser();

        $email = trim($this->request->getPost('email') ?? '');
        $role  = $this->request->getPost('role') ?? 'role_requester';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->to(base_url('admin/usuarios'))->with('error', 'E-mail inválido.');
        }

        if (!array_key_exists($role, self::ROLES)) {
            return redirect()->to(base_url('admin/usuarios'))->with('error', 'Perfil inválido.');
        }

        // Check if user already exists in this institution
        $existing = $this->users
            ->where('institution_id', $institutionId)
            ->where('email', $email)
            ->first();

        if ($existing) {
            return redirect()->to(base_url('admin/usuarios'))
                ->with('error', "Já existe um usuário cadastrado com o e-mail {$email}.");
        }

        $invite = $this->invites->createInvite($institutionId, (int) $currentUser['id'], $email, $role);

        service('notification')->userInvited($invite, $currentUser['name'], $this->institution);
        service('audit')->log('user.invite_sent', 'user', (int) $currentUser['id'], null, [
            'email' => $email,
            'role'  => $role,
        ]);

        return redirect()->to(base_url('admin/usuarios'))
            ->with('success', "Convite enviado para {$email}.");
    }
}
