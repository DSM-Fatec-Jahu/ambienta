<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Restricts access to users with specific roles.
 *
 * Usage in routes:
 *   $routes->get('admin/path', 'Controller::method', ['filter' => 'role:role_admin,role_director']);
 *
 * Arguments are the allowed roles (comma-separated).
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): mixed
    {
        $allowedRoles = $arguments ?? [];

        if (empty($allowedRoles)) {
            return null;
        }

        $userRole = session()->get('user_role');

        if (!$userRole || !in_array($userRole, $allowedRoles, true)) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON(['error' => 'Acesso não autorizado.']);
            }

            return redirect()->to(base_url('dashboard'))
                ->with('error', 'Você não tem permissão para acessar esta página.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }
}
