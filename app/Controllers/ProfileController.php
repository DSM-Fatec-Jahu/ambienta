<?php

namespace App\Controllers;

use App\Models\UserModel;

class ProfileController extends BaseController
{
    private UserModel $users;

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
}
