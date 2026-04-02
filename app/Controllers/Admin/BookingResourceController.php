<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BookingResourceModel;
use App\Models\ResourceMovementModel;
use App\Models\UserModel;

/**
 * BookingResourceController — Sprint R4 / Sprint R5
 *
 * Sprint R4 (RN-R04): Painel do técnico para aprovar e rejeitar requisições.
 * Sprint R5 (RN-R05/RN-R07): Ciclo de devolução com confirmação obrigatória.
 *
 * RN-R05 — Permissões de devolução:
 *   Alocação via reserva aprovada: solicitante da reserva OU técnico pode registrar.
 *   Técnico confirma obrigatoriamente.
 *
 * Rotas:
 *   GET  /admin/recursos-reservas                            → index()
 *   POST /admin/recursos-reservas/:id/aprovar                → approve($id)
 *   POST /admin/recursos-reservas/:id/recusar                → reject($id)
 *   POST /reservas/recursos/:id/devolver                     → returnResource($id)  [auth, all roles]
 *   POST /admin/recursos-reservas/:id/confirmar-devolucao    → confirmReturn($id)
 *   POST /admin/recursos-reservas/:id/rejeitar-devolucao     → rejectReturn($id)
 */
class BookingResourceController extends BaseController
{
    private BookingResourceModel  $bookingResources;
    private ResourceMovementModel $movements;
    private UserModel             $users;

    public function __construct()
    {
        $this->bookingResources = new BookingResourceModel();
        $this->movements        = new ResourceMovementModel();
        $this->users            = new UserModel();
    }

    // ── Panel listing ─────────────────────────────────────────────────────────

    public function index(): string
    {
        $institutionId = (int) ($this->institution['id'] ?? 0);

        $pending             = $this->bookingResources->pendingForInstitution($institutionId);
        $awaitingReturn      = $this->bookingResources->awaitingReturnForInstitution($institutionId);
        $pendingConfirmation = $this->bookingResources->pendingConfirmationForInstitution($institutionId);

        return view('admin/booking_resources/index', $this->viewData([
            'pageTitle'           => 'Recursos de Reservas',
            'pending'             => $pending,
            'awaitingReturn'      => $awaitingReturn,
            'pendingConfirmation' => $pendingConfirmation,
        ]));
    }

    // ── Approve ───────────────────────────────────────────────────────────────

    public function approve(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = (int) ($this->institution['id'] ?? 0);
        $reviewer      = $this->currentUser();

        $br = $this->bookingResources->findWithDetails($id);

        if (!$br || (int) $br['institution_id'] !== $institutionId) {
            return redirect()->to(base_url('admin/recursos-reservas'))
                ->with('error', 'Requisição não encontrada.');
        }

        if ($br['status'] !== BookingResourceModel::STATUS_PENDING) {
            return redirect()->to(base_url('admin/recursos-reservas'))
                ->with('error', 'Esta requisição não está mais pendente.');
        }

        $now = date('Y-m-d H:i:s');

        // Update booking_resource status — RN-R04: booking status is NOT touched
        $this->bookingResources->update($id, [
            'status'         => BookingResourceModel::STATUS_APPROVED,
            'approved_by_id' => $reviewer['id'],
            'updated_at'     => $now,
        ]);

        // Record movement: booking_checkout
        $this->movements->recordBookingCheckout(
            $institutionId,
            (int) $br['resource_id'],
            (int) $br['booking_id'],
            (int) $br['quantity'],
            (int) $reviewer['id']
        );

        // Audit
        service('audit')->log('booking_resource.approved', 'booking_resource', $id, null, [
            'booking_id'  => $br['booking_id'],
            'resource_id' => $br['resource_id'],
            'reviewer_id' => $reviewer['id'],
        ]);

        // Notify requester — RN-R04 / RF-R06
        $requester = $this->users->find((int) $br['requester_id']);
        if ($requester) {
            $resource = db_connect()->table('resources')->where('id', $br['resource_id'])->get()->getRowArray();
            $booking  = db_connect()->table('bookings')->where('id', $br['booking_id'])->get()->getRowArray();
            if ($resource && $booking) {
                service('notification')->resourceApproved($booking, $requester, $resource, (int) $br['quantity']);
            }
        }

        return redirect()->to(base_url('admin/recursos-reservas'))
            ->with('success', 'Recurso aprovado com sucesso.');
    }

    // ── Reject ────────────────────────────────────────────────────────────────

    public function reject(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = (int) ($this->institution['id'] ?? 0);
        $reviewer      = $this->currentUser();

        $br = $this->bookingResources->findWithDetails($id);

        if (!$br || (int) $br['institution_id'] !== $institutionId) {
            return redirect()->to(base_url('admin/recursos-reservas'))
                ->with('error', 'Requisição não encontrada.');
        }

        if ($br['status'] !== BookingResourceModel::STATUS_PENDING) {
            return redirect()->to(base_url('admin/recursos-reservas'))
                ->with('error', 'Esta requisição não está mais pendente.');
        }

        $reason = trim($this->request->getPost('rejection_note') ?? '');
        if (empty($reason)) {
            return redirect()->to(base_url('admin/recursos-reservas'))
                ->with('error', 'O motivo da recusa é obrigatório.');
        }

        $now = date('Y-m-d H:i:s');

        // Update booking_resource status — RN-R04: booking status is NOT touched
        $this->bookingResources->update($id, [
            'status'         => BookingResourceModel::STATUS_REJECTED,
            'rejected_by_id' => $reviewer['id'],
            'rejection_note' => $reason,
            'updated_at'     => $now,
        ]);

        // Audit
        service('audit')->log('booking_resource.rejected', 'booking_resource', $id, null, [
            'booking_id'     => $br['booking_id'],
            'resource_id'    => $br['resource_id'],
            'reviewer_id'    => $reviewer['id'],
            'rejection_note' => $reason,
        ]);

        // Notify requester with reason — RN-R04 / RF-R06
        $requester = $this->users->find((int) $br['requester_id']);
        if ($requester) {
            $resource = db_connect()->table('resources')->where('id', $br['resource_id'])->get()->getRowArray();
            $booking  = db_connect()->table('bookings')->where('id', $br['booking_id'])->get()->getRowArray();
            if ($resource && $booking) {
                service('notification')->resourceRejected($booking, $requester, $resource, (int) $br['quantity'], $reason);
            }
        }

        return redirect()->to(base_url('admin/recursos-reservas'))
            ->with('success', 'Recurso recusado e solicitante notificado.');
    }

    // ── Return resource (RN-R05) ──────────────────────────────────────────────

    /**
     * POST /reservas/recursos/:id/devolver
     *
     * RN-R05: solicitante da reserva OU qualquer staff pode registrar devolução.
     * Accessible to all authenticated users — permission checked inside.
     */
    public function returnResource(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = (int) ($this->institution['id'] ?? 0);
        $user          = $this->currentUser();

        $br = $this->bookingResources->findWithDetails($id);

        if (!$br || (int) $br['institution_id'] !== $institutionId) {
            return redirect()->back()->with('error', 'Recurso não encontrado.');
        }

        // RN-R05: only booking requester OR staff may register return
        $isStaff    = $user['role'] !== 'role_requester';
        $isRequester = (int) $br['requester_id'] === (int) $user['id'];

        if (!$isStaff && !$isRequester) {
            return redirect()->back()->with('error', 'Sem permissão para registrar esta devolução.');
        }

        if ($br['status'] !== BookingResourceModel::STATUS_APPROVED) {
            return redirect()->back()->with('error', 'Este recurso não está no estado "aprovado".');
        }

        // RN-R06: booking must have ended (no automatic return on end — user must explicitly call this)
        $bookingEnded = $this->isBookingEnded($br);
        // Staff can force return even if booking hasn't ended (forced return scenario)
        if (!$isStaff && !$bookingEnded) {
            return redirect()->back()
                ->with('error', 'A devolução só pode ser registrada após o encerramento da reserva.');
        }

        $now   = date('Y-m-d H:i:s');
        $notes = trim($this->request->getPost('notes') ?? '') ?: null;

        $this->bookingResources->update($id, [
            'status'         => BookingResourceModel::STATUS_RETURNED,
            'returned_at'    => $now,
            'returned_by_id' => $user['id'],
            'updated_at'     => $now,
        ]);

        // Record movement: booking_return
        $this->movements->recordBookingReturn(
            $institutionId,
            (int) $br['resource_id'],
            (int) $br['booking_id'],
            (int) $br['quantity'],
            (int) $user['id'],
            $notes
        );

        // Audit
        service('audit')->log('booking_resource.returned', 'booking_resource', $id, null, [
            'booking_id'    => $br['booking_id'],
            'resource_id'   => $br['resource_id'],
            'returned_by'   => $user['id'],
            'forced'        => $isStaff && !$bookingEnded ? 1 : 0,
        ]);

        // Notify technicians that a return needs confirmation — RN-R07 / RF-R06
        $resource = db_connect()->table('resources')->where('id', $br['resource_id'])->get()->getRowArray();
        $booking  = db_connect()->table('bookings')->where('id', $br['booking_id'])->get()->getRowArray();
        if ($resource && $booking) {
            service('notification')->resourceReturnRegistered($booking, $user, $resource, (int) $br['quantity']);
        }

        $backUrl = base_url('reservas/' . $br['booking_id']);
        return redirect()->to($backUrl)
            ->with('success', 'Devolução registrada. Aguardando confirmação do técnico.');
    }

    // ── Confirm return (RN-R07) ───────────────────────────────────────────────

    /**
     * POST /admin/recursos-reservas/:id/confirmar-devolucao
     *
     * RN-R07: technician confirms physical return. Status → return_confirmed.
     */
    public function confirmReturn(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = (int) ($this->institution['id'] ?? 0);
        $technician    = $this->currentUser();

        $br = $this->bookingResources->findWithDetails($id);

        if (!$br || (int) $br['institution_id'] !== $institutionId) {
            return redirect()->to(base_url('admin/recursos-reservas'))
                ->with('error', 'Recurso não encontrado.');
        }

        if ($br['status'] !== BookingResourceModel::STATUS_RETURNED) {
            return redirect()->to(base_url('admin/recursos-reservas'))
                ->with('error', 'Este recurso não está aguardando confirmação de devolução.');
        }

        $now   = date('Y-m-d H:i:s');
        $notes = trim($this->request->getPost('notes') ?? '') ?: null;

        $this->bookingResources->update($id, [
            'status'          => BookingResourceModel::STATUS_RETURN_CONFIRMED,
            'confirmed_at'    => $now,
            'confirmed_by_id' => $technician['id'],
            'updated_at'      => $now,
        ]);

        // Record movement: return_confirmed
        $this->movements->recordReturnConfirmed(
            $institutionId,
            (int) $br['resource_id'],
            (int) $br['booking_id'],
            (int) $br['quantity'],
            (int) $technician['id'],
            $notes
        );

        // Audit
        service('audit')->log('booking_resource.return_confirmed', 'booking_resource', $id, null, [
            'booking_id'   => $br['booking_id'],
            'resource_id'  => $br['resource_id'],
            'confirmed_by' => $technician['id'],
        ]);

        // Notify requester — RF-R06
        $requester = $this->users->find((int) $br['requester_id']);
        if ($requester) {
            $resource = db_connect()->table('resources')->where('id', $br['resource_id'])->get()->getRowArray();
            $booking  = db_connect()->table('bookings')->where('id', $br['booking_id'])->get()->getRowArray();
            if ($resource && $booking) {
                service('notification')->returnConfirmed($booking, $requester, $resource, (int) $br['quantity']);
            }
        }

        return redirect()->to(base_url('admin/recursos-reservas'))
            ->with('success', 'Devolução confirmada com sucesso.');
    }

    // ── Reject return (RN-R07) ────────────────────────────────────────────────

    /**
     * POST /admin/recursos-reservas/:id/rejeitar-devolucao
     *
     * RN-R07: technician rejects the return claim. Status reverts to approved.
     * Notifies the requester.
     */
    public function rejectReturn(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = (int) ($this->institution['id'] ?? 0);
        $technician    = $this->currentUser();

        $br = $this->bookingResources->findWithDetails($id);

        if (!$br || (int) $br['institution_id'] !== $institutionId) {
            return redirect()->to(base_url('admin/recursos-reservas'))
                ->with('error', 'Recurso não encontrado.');
        }

        if ($br['status'] !== BookingResourceModel::STATUS_RETURNED) {
            return redirect()->to(base_url('admin/recursos-reservas'))
                ->with('error', 'Este recurso não está aguardando confirmação de devolução.');
        }

        $reason = trim($this->request->getPost('rejection_note') ?? '');
        if (empty($reason)) {
            return redirect()->to(base_url('admin/recursos-reservas'))
                ->with('error', 'O motivo da rejeição da devolução é obrigatório.');
        }

        $now = date('Y-m-d H:i:s');

        // Revert to approved — RN-R07
        $this->bookingResources->update($id, [
            'status'         => BookingResourceModel::STATUS_APPROVED,
            'returned_at'    => null,
            'returned_by_id' => null,
            'rejection_note' => $reason,
            'updated_at'     => $now,
        ]);

        // Record movement: return_rejected
        $this->movements->recordReturnRejected(
            $institutionId,
            (int) $br['resource_id'],
            (int) $br['booking_id'],
            (int) $br['quantity'],
            (int) $technician['id'],
            $reason
        );

        // Audit
        service('audit')->log('booking_resource.return_rejected', 'booking_resource', $id, null, [
            'booking_id'     => $br['booking_id'],
            'resource_id'    => $br['resource_id'],
            'rejected_by'    => $technician['id'],
            'rejection_note' => $reason,
        ]);

        // Notify requester — RF-R06
        $requester = $this->users->find((int) $br['requester_id']);
        if ($requester) {
            $resource = db_connect()->table('resources')->where('id', $br['resource_id'])->get()->getRowArray();
            $booking  = db_connect()->table('bookings')->where('id', $br['booking_id'])->get()->getRowArray();
            if ($resource && $booking) {
                service('notification')->returnRejected($booking, $requester, $resource, (int) $br['quantity'], $reason);
            }
        }

        return redirect()->to(base_url('admin/recursos-reservas'))
            ->with('success', 'Devolução rejeitada. Solicitante notificado.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Returns true if the booking's end datetime is in the past.
     */
    private function isBookingEnded(array $br): bool
    {
        $endDatetime = ($br['booking_date'] ?? '') . ' ' . ($br['booking_end'] ?? '23:59:59');
        return strtotime($endDatetime) <= time();
    }
}
