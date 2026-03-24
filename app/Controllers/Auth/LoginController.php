<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;

class LoginController extends BaseController
{
    protected UserModel $userModel;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);
        $this->userModel = new UserModel();
    }

    /** GET /login */
    public function index(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (session()->get('user_id')) {
            return redirect()->to(base_url('dashboard'));
        }

        $auth = $this->getAuthSettings();

        return view('auth/login', $this->viewData([
            'pageTitle'        => 'Entrar',
            'ssoEnabled'       => $auth['sso_google_enabled'] ?? false,
            'localLoginEnabled'=> $auth['local_login_enabled'] ?? true,
            'errors'           => [],
            'fieldErrors'      => [],
        ]));
    }

    /** POST /login */
    public function attempt(): \CodeIgniter\HTTP\RedirectResponse|string
    {
        $auth = $this->getAuthSettings();

        if (empty($auth['local_login_enabled'])) {
            return redirect()->to(base_url('login'))->with('error', 'Login local não está habilitado.');
        }

        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[1]',
        ];

        if (!$this->validate($rules)) {
            return view('auth/login', $this->viewData([
                'pageTitle'         => 'Entrar',
                'ssoEnabled'        => $auth['sso_google_enabled'] ?? false,
                'localLoginEnabled' => $auth['local_login_enabled'] ?? true,
                'errors'            => [],
                'fieldErrors'       => $this->validator->getErrors(),
            ]));
        }

        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $user = $this->userModel->findByEmail($email);

        // User not found
        if (!$user) {
            service('audit')->log('auth.failed', 'user', null, null, ['email' => $email], null);
            return $this->loginError($auth, 'E-mail ou senha inválidos.');
        }

        // Inactive account
        if (!$user['is_active']) {
            service('audit')->log('auth.failed', 'user', $user['id'], null, ['reason' => 'inactive']);
            return $this->loginError($auth, 'Sua conta está inativa. Contate o administrador.');
        }

        // Locked out
        if ($this->userModel->isLockedOut($user)) {
            $lockedUntil = date('H:i', strtotime($user['locked_until']));
            return $this->loginError($auth, "Conta bloqueada por tentativas excessivas. Tente novamente após {$lockedUntil}.");
        }

        // Wrong password
        if (empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
            $this->userModel->incrementLoginAttempts($user['id']);
            $remaining = max(0, 5 - ((int) $user['login_attempts'] + 1));
            service('audit')->log('auth.failed', 'user', $user['id'], null, ['reason' => 'wrong_password']);

            $msg = 'E-mail ou senha inválidos.';
            if ($remaining === 0) {
                $msg = 'Conta bloqueada por 15 minutos após 5 tentativas falhas.';
            } elseif ($remaining <= 2) {
                $msg = "E-mail ou senha inválidos. {$remaining} tentativa(s) restante(s).";
            }
            return $this->loginError($auth, $msg);
        }

        // Success
        $this->userModel->resetLoginAttempts($user['id']);
        $this->setUserSession($user);
        service('audit')->log('auth.login', 'user', $user['id']);

        return redirect()->to(base_url('dashboard'));
    }

    /** GET /logout */
    public function logout(): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId = session()->get('user_id');
        service('audit')->log('auth.logout', 'user', $userId);
        session()->destroy();
        return redirect()->to(base_url('login'))->with('success', 'Você saiu com sucesso.');
    }

    /** GET /esqueci-senha */
    public function forgotPassword(): string
    {
        return view('auth/forgot_password', $this->viewData([
            'pageTitle' => 'Recuperar Senha',
        ]));
    }

    /** POST /esqueci-senha */
    public function sendResetLink(): \CodeIgniter\HTTP\RedirectResponse|string
    {
        $email = $this->request->getPost('email');
        $user  = $this->userModel->findByEmail((string) $email);

        // Always show success message to prevent user enumeration
        if ($user && $user['is_active']) {
            $token = bin2hex(random_bytes(32));
            $this->userModel->setResetToken($user['id'], $token);

            // TODO Sprint 10: send via EmailService
            // For now, log to CI4 log for dev environment
            log_message('info', "[Password Reset] Token for {$email}: {$token}");
        }

        return redirect()->to(base_url('login'))
            ->with('success', 'Se este e-mail estiver cadastrado, você receberá as instruções em breve.');
    }

    /** GET /redefinir-senha/:token */
    public function resetPassword(string $token): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $user = $this->userModel->findByResetToken($token);
        if (!$user) {
            return redirect()->to(base_url('login'))
                ->with('error', 'Link de redefinição inválido ou expirado.');
        }

        return view('auth/reset_password', $this->viewData([
            'pageTitle' => 'Nova Senha',
            'token'     => $token,
            'errors'    => [],
        ]));
    }

    /** POST /redefinir-senha/:token */
    public function updatePassword(string $token): \CodeIgniter\HTTP\RedirectResponse|string
    {
        $user = $this->userModel->findByResetToken($token);
        if (!$user) {
            return redirect()->to(base_url('login'))
                ->with('error', 'Link de redefinição inválido ou expirado.');
        }

        $password        = $this->request->getPost('password');
        $passwordConfirm = $this->request->getPost('password_confirm');

        $errors = [];
        if (strlen((string) $password) < 8) {
            $errors[] = 'A senha deve ter no mínimo 8 caracteres.';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'As senhas não coincidem.';
        }

        if (!empty($errors)) {
            return view('auth/reset_password', $this->viewData([
                'pageTitle' => 'Nova Senha',
                'token'     => $token,
                'errors'    => $errors,
            ]));
        }

        $hash = password_hash((string) $password, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->userModel->update($user['id'], ['password_hash' => $hash]);
        $this->userModel->clearResetToken($user['id']);

        service('audit')->log('auth.password_reset', 'user', $user['id'], null, null, $user['id']);

        return redirect()->to(base_url('login'))
            ->with('success', 'Senha redefinida com sucesso. Faça login.');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function loginError(array $auth, string $message): string
    {
        return view('auth/login', $this->viewData([
            'pageTitle'         => 'Entrar',
            'ssoEnabled'        => $auth['sso_google_enabled'] ?? false,
            'localLoginEnabled' => $auth['local_login_enabled'] ?? true,
            'errors'            => [$message],
            'fieldErrors'       => [],
        ]));
    }
}
