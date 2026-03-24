<?php

namespace App\Controllers;

use App\Models\UserModel;

class ProfileController extends BaseController
{
    private UserModel $users;

    private const AVATAR_DIR      = FCPATH . 'uploads/avatars/';
    private const AVATAR_MAX_KB   = 2048;   // 2 MB
    private const AVATAR_ALLOWED  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function __construct()
    {
        $this->users = new UserModel();
    }

    public function index(): string
    {
        $user = $this->users->find($this->currentUser()['id']);

        return view('profile/index', $this->viewData([
            'pageTitle'   => 'Minha Conta',
            'profileUser' => $user,
        ]));
    }

    public function updateInfo(): \CodeIgniter\HTTP\RedirectResponse
    {
        $id   = (int) $this->currentUser()['id'];
        $user = $this->users->find($id);

        if (!$user) {
            return redirect()->to(base_url('perfil'))->with('error', 'Usuário não encontrado.');
        }

        $rules = [
            'name'      => 'required|max_length[200]',
            'cellphone' => 'permit_empty|max_length[30]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->users->update($id, [
            'name'      => $this->request->getPost('name'),
            'cellphone' => $this->request->getPost('cellphone') ?: null,
        ]);

        // Update session name
        session()->set('user_name', $this->request->getPost('name'));

        return redirect()->to(base_url('perfil'))
            ->with('success', 'Dados atualizados com sucesso.');
    }

    public function updatePassword(): \CodeIgniter\HTTP\RedirectResponse
    {
        $id   = (int) $this->currentUser()['id'];
        $user = $this->users->find($id);

        if (!$user) {
            return redirect()->to(base_url('perfil'))->with('error', 'Usuário não encontrado.');
        }

        // Google-only accounts have no local password
        if (empty($user['password_hash'])) {
            return redirect()->to(base_url('perfil'))
                ->with('error', 'Sua conta usa autenticação Google. Não é possível definir senha local por aqui.');
        }

        $currentPw  = $this->request->getPost('current_password') ?? '';
        $newPw      = $this->request->getPost('new_password')      ?? '';
        $confirmPw  = $this->request->getPost('confirm_password')  ?? '';

        if (!password_verify($currentPw, $user['password_hash'])) {
            return redirect()->back()->with('error', 'Senha atual incorreta.');
        }

        if (strlen($newPw) < 8) {
            return redirect()->back()->with('error', 'A nova senha deve ter pelo menos 8 caracteres.');
        }

        if ($newPw !== $confirmPw) {
            return redirect()->back()->with('error', 'A confirmação de senha não confere.');
        }

        $this->users->update($id, [
            'password_hash' => password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        service('audit')->log('user.password_changed', 'user', $id);

        return redirect()->to(base_url('perfil'))
            ->with('success', 'Senha alterada com sucesso.');
    }

    public function uploadAvatar(): \CodeIgniter\HTTP\RedirectResponse
    {
        $id   = (int) $this->currentUser()['id'];
        $user = $this->users->find($id);

        if (!$user) {
            return redirect()->to(base_url('perfil'))->with('error', 'Usuário não encontrado.');
        }

        $file = $this->request->getFile('avatar');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return redirect()->to(base_url('perfil'))->with('error', 'Nenhum arquivo enviado ou arquivo inválido.');
        }

        if (!in_array($file->getMimeType(), self::AVATAR_ALLOWED, true)) {
            return redirect()->to(base_url('perfil'))->with('error', 'Formato não suportado. Use JPEG, PNG, WebP ou GIF.');
        }

        if ($file->getSizeByUnit('kb') > self::AVATAR_MAX_KB) {
            return redirect()->to(base_url('perfil'))->with('error', 'Arquivo muito grande. Limite: 2 MB.');
        }

        // Ensure directory exists
        if (!is_dir(self::AVATAR_DIR)) {
            mkdir(self::AVATAR_DIR, 0755, true);
        }

        // Remove old local avatar
        if (!empty($user['avatar_path'])) {
            $old = FCPATH . 'uploads/avatars/' . basename($user['avatar_path']);
            if (is_file($old)) {
                @unlink($old);
            }
        }

        $newName = 'user_' . $id . '_' . time() . '.' . $file->getExtension();
        $file->move(self::AVATAR_DIR, $newName);

        $this->users->update($id, ['avatar_path' => $newName]);
        session()->set('user_avatar_path', $newName);

        service('audit')->log('user.avatar_updated', 'user', $id);

        return redirect()->to(base_url('perfil'))->with('success', 'Foto de perfil atualizada.');
    }

    public function deleteAvatar(): \CodeIgniter\HTTP\RedirectResponse
    {
        $id   = (int) $this->currentUser()['id'];
        $user = $this->users->find($id);

        if (!empty($user['avatar_path'])) {
            $path = FCPATH . 'uploads/avatars/' . basename($user['avatar_path']);
            if (is_file($path)) {
                @unlink($path);
            }
            $this->users->update($id, ['avatar_path' => null]);
            session()->set('user_avatar_path', null);
        }

        return redirect()->to(base_url('perfil'))->with('success', 'Foto de perfil removida.');
    }
}
