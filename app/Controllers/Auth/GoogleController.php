<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;
use League\OAuth2\Client\Provider\Google;

class GoogleController extends BaseController
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

    /** GET /auth/google  – Redirects user to Google consent screen */
    public function redirect(): \CodeIgniter\HTTP\RedirectResponse
    {
        $auth = $this->getAuthSettings();

        if (empty($auth['sso_google_enabled'])) {
            return redirect()->to(base_url('login'))->with('error', 'Login com Google não está habilitado.');
        }

        $provider = $this->buildProvider($auth);
        $authUrl  = $provider->getAuthorizationUrl([
            'scope' => ['openid', 'email', 'profile'],
        ]);

        // Store CSRF state in session
        session()->set('oauth2_state', $provider->getState());

        return redirect()->to($authUrl);
    }

    /** GET /auth/google/callback */
    public function callback(): \CodeIgniter\HTTP\RedirectResponse
    {
        $auth = $this->getAuthSettings();

        if (empty($auth['sso_google_enabled'])) {
            return redirect()->to(base_url('login'))->with('error', 'Login com Google não está habilitado.');
        }

        $state = $this->request->getGet('state');
        $code  = $this->request->getGet('code');

        // Validate OAuth2 state parameter (CSRF)
        if (!$state || $state !== session()->get('oauth2_state')) {
            session()->remove('oauth2_state');
            service('audit')->log('auth.failed', 'user', null, null, ['reason' => 'oauth_state_mismatch']);
            return redirect()->to(base_url('login'))->with('error', 'Erro de segurança. Tente novamente.');
        }

        session()->remove('oauth2_state');

        if (!$code) {
            return redirect()->to(base_url('login'))->with('error', 'Autenticação com Google cancelada.');
        }

        try {
            $provider    = $this->buildProvider($auth);
            $token       = $provider->getAccessToken('authorization_code', ['code' => $code]);
            $googleUser  = $provider->getResourceOwner($token);

            $email  = $googleUser->getEmail();
            $domain = substr(strrchr((string) $email, '@'), 1);

            // Domain validation
            $allowedDomains = $auth['sso_allowed_domains'] ?? [];
            if (!empty($allowedDomains) && !in_array($domain, $allowedDomains, true)) {
                service('audit')->log('auth.failed', 'user', null, null, [
                    'reason' => 'domain_not_allowed',
                    'domain' => $domain,
                ]);
                return redirect()->to(base_url('login'))
                    ->with('error', "O domínio @{$domain} não está autorizado para este sistema.");
            }

            $institutionId = (int) ($this->institution['id'] ?? 1);

            $user = $this->userModel->upsertFromGoogle([
                'google_id'  => $googleUser->getId(),
                'name'       => $googleUser->getName(),
                'email'      => $email,
                'avatar_url' => $googleUser->getAvatar(),
            ], $institutionId);

            if (!$user['is_active']) {
                service('audit')->log('auth.failed', 'user', $user['id'], null, ['reason' => 'inactive']);
                return redirect()->to(base_url('login'))
                    ->with('error', 'Sua conta está inativa. Contate o administrador.');
            }

            $this->userModel->resetLoginAttempts($user['id']);
            $this->setUserSession($user);
            service('audit')->log('auth.login', 'user', $user['id'], null, ['method' => 'google_sso']);

            return redirect()->to(base_url('dashboard'));

        } catch (\Exception $e) {
            log_message('error', '[GoogleSSO] ' . $e->getMessage());
            return redirect()->to(base_url('login'))
                ->with('error', 'Erro ao autenticar com Google. Tente novamente.');
        }
    }

    // ── Private ──────────────────────────────────────────────────────

    private function buildProvider(array $auth): Google
    {
        return new Google([
            'clientId'     => $auth['sso_google_client_id']     ?: env('GOOGLE_CLIENT_ID'),
            'clientSecret' => $auth['sso_google_client_secret'] ?: env('GOOGLE_CLIENT_SECRET'),
            'redirectUri'  => env('GOOGLE_REDIRECT_URI', base_url('auth/google/callback')),
        ]);
    }
}
