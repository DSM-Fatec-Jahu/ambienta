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
        'role_requester'     => 'Professor',
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
        $institutionId  = $this->institution['id'] ?? 0;
        $pendingInvites = $this->invites->pendingForInstitution($institutionId);

        return view('admin/users/index', $this->viewData([
            'pageTitle'      => 'Usuários',
            'rolesList'      => self::ROLES,
            'pendingInvites' => $pendingInvites,
        ]));
    }

    public function data(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;

        $page   = max(1, (int) ($this->request->getGet('page')    ?? 1));
        $q      = trim($this->request->getGet('q')                ?? '');
        $role   = trim($this->request->getGet('role')             ?? '');
        $status = (int) ($this->request->getGet('status')         ?? 0);
        $limit  = in_array((int) ($this->request->getGet('limit') ?? 10), [10, 25, 50, 100])
                      ? (int) $this->request->getGet('limit') : 10;
        $offset = ($page - 1) * $limit;

        $rows  = $this->users->search($institutionId, $q, $role, $status, $limit, $offset);
        $total = $this->users->searchCount($institutionId, $q, $role, $status);

        foreach ($rows as &$r) {
            $r['id']        = (int)  $r['id'];
            $r['is_active'] = (bool) $r['is_active'];
        }
        unset($r);

        return $this->response->setJSON([
            'data'  => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => (int) ceil($total / $limit),
            'limit' => $limit,
        ]);
    }

    public function exportXlsx(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $q      = trim($this->request->getGet('q')      ?? '');
        $role   = trim($this->request->getGet('role')   ?? '');
        $status = (int) ($this->request->getGet('status') ?? 0);

        $rows = $this->users->search($institutionId, $q, $role, $status, 5000, 0);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Usuários');

        $sheet->fromArray(['Nome', 'E-mail', 'Perfil', 'SSO', 'Status'], null, 'A1');
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                       'startColor' => ['rgb' => '1E40AF']],
        ]);

        $row = 2;
        foreach ($rows as $r) {
            $sheet->fromArray([
                $r['name'],
                $r['email'],
                self::ROLES[$r['role']] ?? $r['role'],
                $r['google_id'] ? 'Google' : 'Local',
                $r['is_active'] ? 'Ativo' : 'Inativo',
            ], null, 'A' . $row);

            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':E' . $row)
                    ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }
            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="usuarios_' . date('Y-m-d') . '.xlsx"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody($content);
    }

    public function exportPdf(): void
    {
        $institutionId = $this->institution['id'] ?? 0;
        $q      = trim($this->request->getGet('q')      ?? '');
        $role   = trim($this->request->getGet('role')   ?? '');
        $status = (int) ($this->request->getGet('status') ?? 0);

        $rows = $this->users->search($institutionId, $q, $role, $status, 5000, 0);

        $html = view('admin/users/pdf_export', [
            'rows'        => $rows,
            'institution' => $this->institution,
            'rolesList'   => self::ROLES,
            'generatedAt' => date('d/m/Y H:i'),
        ]);

        $options = new \Dompdf\Options();
        $options->setChroot(ROOTPATH);
        $options->setIsRemoteEnabled(false);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('usuarios_' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
        exit;
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

    public function revokeInvite(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;

        $invite = $this->invites->find($id);

        if (!$invite || $invite['institution_id'] != $institutionId) {
            return redirect()->to(base_url('admin/usuarios'))->with('error', 'Convite não encontrado.');
        }

        if (!empty($invite['accepted_at'])) {
            return redirect()->to(base_url('admin/usuarios'))
                ->with('error', 'Este convite já foi aceito e não pode ser revogado.');
        }

        // Expire the invite immediately
        $this->invites->update($id, ['expires_at' => date('Y-m-d H:i:s')]);

        service('audit')->log('user.invite_revoked', 'user', (int) $this->currentUser()['id'], null, [
            'email' => $invite['email'],
        ]);

        return redirect()->to(base_url('admin/usuarios'))
            ->with('success', "Convite para {$invite['email']} revogado.");
    }
}
