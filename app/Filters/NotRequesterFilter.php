<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Blocks role_requester from accessing sensitive write routes.
 *
 * Applied to: recurring reservation creation, approval/rejection,
 * buildings/rooms/equipment management.
 *
 * Per DRS RN11 and RN12: role_requester NEVER approves or creates recurring
 * reservations. This check is absolute and verified server-side.
 */
class NotRequesterFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): mixed
    {
        $userRole = session()->get('user_role');

        // Must be authenticated first
        if (!$userRole) {
            return redirect()->to(base_url('login'))->with('warning', 'Faça login para continuar.');
        }

        if ($userRole === 'role_requester') {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON(['error' => 'Acesso não autorizado.']);
            }

            return redirect()->to(base_url('dashboard'))
                ->with('error', 'Você não tem permissão para realizar esta ação.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }
}
