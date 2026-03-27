<?php

namespace App\Services;

use App\Models\NotificationModel;
use CodeIgniter\Email\Email;

/**
 * Sends transactional notifications (e-mail + in-app DB record) for booking events.
 *
 * Usage:
 *   service('notification')->bookingCreated($booking, $user, $room);
 *   service('notification')->bookingApproved($booking, $user, $room, $reviewer);
 *   service('notification')->bookingRejected($booking, $user, $room, $notes);
 *   service('notification')->bookingCancelled($booking, $user, $room, $reason);
 *   service('notification')->waitlistAvailable($entry, $user, $room, $booking);
 *
 * E-mail is only sent when MAIL_FROM_ADDRESS is set in .env.
 * Failures are logged but never throw — notifications must never break the main flow.
 */
class NotificationService
{
    private Email             $mailer;
    private NotificationModel $notifModel;
    private string $fromAddress;
    private string $fromName;
    private string $appName;
    private string $appUrl;

    public function __construct()
    {
        $this->mailer      = \Config\Services::email();
        $this->notifModel  = new NotificationModel();
        $this->fromAddress = env('MAIL_FROM_ADDRESS', '');
        $this->fromName    = env('MAIL_FROM_NAME', 'Ambienta');
        $this->appName     = env('MAIL_FROM_NAME', 'Ambienta');
        $this->appUrl      = rtrim(env('app.baseURL', base_url()), '/');
    }

    /** Returns false and logs if e-mail sending is not configured. */
    private function isConfigured(): bool
    {
        return !empty($this->fromAddress);
    }

    /** Core email send helper. */
    private function send(string $to, string $subject, string $body): bool
    {
        if (!$this->isConfigured()) {
            log_message('info', "[NotificationService] E-mail not sent (MAIL_FROM_ADDRESS not set): {$subject}");
            return false;
        }

        try {
            $this->mailer->clear();
            $this->mailer->setFrom($this->fromAddress, $this->fromName);
            $this->mailer->setTo($to);
            $this->mailer->setSubject($subject);
            $this->mailer->setMessage($body);
            $this->mailer->setMailType('html');

            if (!$this->mailer->send(false)) {
                log_message('error', '[NotificationService] Send failed: ' . $this->mailer->printDebugger(['headers']));
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            log_message('error', '[NotificationService] Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Saves an in-app notification to the database.
     * Silent — never throws.
     */
    private function saveNotification(
        array  $user,
        string $type,
        string $title,
        string $body,
        string $url = ''
    ): void {
        try {
            $institutionId = (int) ($user['institution_id'] ?? 0);
            if (!$institutionId) {
                // Try to resolve from session/institution context
                $institutionId = (int) (session()->get('institution_id') ?? 0);
            }
            $this->notifModel->createNotification(
                $institutionId,
                (int) $user['id'],
                $type,
                $title,
                $body,
                $url
            );
        } catch (\Throwable $e) {
            log_message('error', '[NotificationService] DB save failed: ' . $e->getMessage());
        }
    }

    // ── Booking lifecycle notifications ──────────────────────────────────────

    /** Notifica o solicitante que sua reserva foi criada e está pendente. */
    public function bookingCreated(array $booking, array $user, array $room): bool
    {
        $subject = "[{$this->appName}] Reserva recebida — aguardando aprovação";
        $url     = $this->appUrl . '/reservas/' . $booking['id'];
        $body    = view('emails/booking_created', [
            'appName' => $this->appName,
            'appUrl'  => $this->appUrl,
            'booking' => $booking,
            'user'    => $user,
            'room'    => $room,
        ]);

        $this->saveNotification(
            $user,
            'booking.created',
            "Reserva recebida — aguardando aprovação",
            "Sua reserva \"{$booking['title']}\" foi criada e aguarda aprovação.",
            $url
        );

        return $this->send($user['email'], $subject, $body);
    }

    /** Notifica o solicitante que sua reserva foi aprovada. */
    public function bookingApproved(array $booking, array $user, array $room, ?array $reviewer = null): bool
    {
        $subject = "[{$this->appName}] Reserva aprovada — {$booking['title']}";
        $url     = $this->appUrl . '/reservas/' . $booking['id'];
        $body    = view('emails/booking_approved', [
            'appName'  => $this->appName,
            'appUrl'   => $this->appUrl,
            'booking'  => $booking,
            'user'     => $user,
            'room'     => $room,
            'reviewer' => $reviewer,
        ]);

        $this->saveNotification(
            $user,
            'booking.approved',
            "Reserva aprovada",
            "Sua reserva \"{$booking['title']}\" foi aprovada.",
            $url
        );

        return $this->send($user['email'], $subject, $body);
    }

    /** Notifica o solicitante que sua reserva foi recusada. */
    public function bookingRejected(array $booking, array $user, array $room, string $notes): bool
    {
        $subject = "[{$this->appName}] Reserva recusada — {$booking['title']}";
        $url     = $this->appUrl . '/reservas/' . $booking['id'];
        $body    = view('emails/booking_rejected', [
            'appName' => $this->appName,
            'appUrl'  => $this->appUrl,
            'booking' => $booking,
            'user'    => $user,
            'room'    => $room,
            'notes'   => $notes,
        ]);

        $this->saveNotification(
            $user,
            'booking.rejected',
            "Reserva recusada",
            "Sua reserva \"{$booking['title']}\" foi recusada. Motivo: {$notes}",
            $url
        );

        return $this->send($user['email'], $subject, $body);
    }

    /** Notifica o solicitante que sua reserva foi cancelada. */
    public function bookingCancelled(array $booking, array $user, array $room, string $reason): bool
    {
        $subject = "[{$this->appName}] Reserva cancelada — {$booking['title']}";
        $url     = $this->appUrl . '/reservas/' . $booking['id'];
        $body    = view('emails/booking_cancelled', [
            'appName' => $this->appName,
            'appUrl'  => $this->appUrl,
            'booking' => $booking,
            'user'    => $user,
            'room'    => $room,
            'reason'  => $reason,
        ]);

        $this->saveNotification(
            $user,
            'booking.cancelled',
            "Reserva cancelada",
            "Sua reserva \"{$booking['title']}\" foi cancelada.",
            $url
        );

        return $this->send($user['email'], $subject, $body);
    }

    /** Envia lembrete D-1 ao solicitante sobre reserva aprovada agendada para amanhã. */
    public function bookingReminder(array $booking, array $user, array $room): bool
    {
        $subject = "[{$this->appName}] Lembrete: sua reserva \"{$booking['title']}\" é amanhã";
        $url     = $this->appUrl . '/reservas/' . $booking['id'];
        $body    = view('emails/booking_reminder', [
            'appName' => $this->appName,
            'appUrl'  => $this->appUrl,
            'booking' => $booking,
            'user'    => $user,
            'room'    => $room,
        ]);

        $this->saveNotification(
            $user,
            'booking.reminder',
            "Lembrete de reserva",
            "Sua reserva \"{$booking['title']}\" está agendada para amanhã.",
            $url
        );

        return $this->send($user['email'], $subject, $body);
    }

    /** Solicita avaliação de reserva realizada ontem. */
    public function bookingReviewRequest(array $booking, array $user, array $room): bool
    {
        $subject = "[{$this->appName}] Como foi \"{$booking['title']}\"? Avalie o ambiente";
        $url     = $this->appUrl . '/reservas/' . $booking['id'];
        $body    = view('emails/booking_review_request', [
            'appName' => $this->appName,
            'appUrl'  => $this->appUrl,
            'booking' => $booking,
            'user'    => $user,
            'room'    => $room,
        ]);

        $this->saveNotification(
            $user,
            'booking.review_request',
            "Avalie sua reserva",
            "Como foi \"{$booking['title']}\"? Compartilhe sua experiência.",
            $url
        );

        return $this->send($user['email'], $subject, $body);
    }

    /** Notifica o solicitante que sua reserva foi cancelada por ausência de check-in. */
    public function bookingAutoCancel(array $booking, array $user, array $room): bool
    {
        $subject = "[{$this->appName}] Reserva cancelada por ausência — {$booking['title']}";
        $url     = $this->appUrl . '/reservas/' . $booking['id'];
        $body    = view('emails/booking_auto_cancel', [
            'appName' => $this->appName,
            'appUrl'  => $this->appUrl,
            'booking' => $booking,
            'user'    => $user,
            'room'    => $room,
        ]);

        $this->saveNotification(
            $user,
            'booking.auto_cancel',
            "Reserva cancelada por ausência",
            "Sua reserva \"{$booking['title']}\" foi cancelada automaticamente por falta de check-in.",
            $url
        );

        return $this->send($user['email'], $subject, $body);
    }

    // ── Resource request notifications (Sprint R4 — RN-R04) ──────────────────

    /**
     * Notifica todos os técnicos ativos da instituição sobre nova requisição de recurso.
     * RF-R06 (parcial) — disparado ao criar a reserva com recursos solicitados.
     */
    public function resourceRequested(
        array $booking,
        array $requester,
        array $resource,
        int   $quantity
    ): void {
        $url = $this->appUrl . '/admin/recursos-reservas';
        $resourceLabel = $resource['name']
            . (!empty($resource['code']) ? ' (' . $resource['code'] . ')' : '');
        $title   = "Nova requisição de recurso pendente";
        $message = "{$requester['name']} solicitou {$quantity}× {$resourceLabel} na reserva \"{$booking['title']}\".";

        $technicians = db_connect()->table('users')
            ->where('institution_id', $booking['institution_id'])
            ->where('is_active', 1)
            ->whereIn('role', ['role_technician', 'role_coordinator', 'role_vice_director', 'role_director', 'role_admin'])
            ->get()->getResultArray();

        foreach ($technicians as $tech) {
            try {
                $this->saveNotification($tech, 'booking_resource.requested', $title, $message, $url);
            } catch (\Throwable $e) {
                log_message('error', '[NotificationService] resourceRequested DB error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Notifica o solicitante que sua requisição de recurso foi aprovada.
     * RF-R06 — disparado pelo técnico ao aprovar (RN-R04).
     */
    public function resourceApproved(
        array $booking,
        array $requester,
        array $resource,
        int   $quantity
    ): bool {
        $resourceLabel = $resource['name']
            . (!empty($resource['code']) ? ' (' . $resource['code'] . ')' : '');
        $subject = "[{$this->appName}] Recurso aprovado — {$resourceLabel}";
        $url     = $this->appUrl . '/reservas/' . $booking['id'];
        $message = "{$quantity}× {$resourceLabel} foi aprovado para sua reserva \"{$booking['title']}\".";

        $this->saveNotification(
            $requester,
            'booking_resource.approved',
            "Recurso aprovado",
            $message,
            $url
        );

        if (!$this->isConfigured()) {
            return false;
        }

        $body = "<p>Olá, {$requester['name']}.</p>"
            . "<p>O recurso <strong>" . htmlspecialchars($resourceLabel, ENT_QUOTES) . "</strong> (qtd: {$quantity}) "
            . "foi <strong>aprovado</strong> para sua reserva "
            . "<strong>" . htmlspecialchars($booking['title'], ENT_QUOTES) . "</strong>.</p>"
            . "<p><a href=\"{$url}\">Ver reserva</a></p>";

        return $this->send($requester['email'], $subject, $body);
    }

    /**
     * Notifica o solicitante que sua requisição de recurso foi rejeitada.
     * RF-R06 / RN-R04 — motivo obrigatório informado pelo técnico.
     */
    public function resourceRejected(
        array  $booking,
        array  $requester,
        array  $resource,
        int    $quantity,
        string $reason
    ): bool {
        $resourceLabel = $resource['name']
            . (!empty($resource['code']) ? ' (' . $resource['code'] . ')' : '');
        $subject = "[{$this->appName}] Recurso recusado — {$resourceLabel}";
        $url     = $this->appUrl . '/reservas/' . $booking['id'];
        $message = "{$quantity}× {$resourceLabel} foi recusado para sua reserva \"{$booking['title']}\". Motivo: {$reason}";

        $this->saveNotification(
            $requester,
            'booking_resource.rejected',
            "Recurso recusado",
            $message,
            $url
        );

        if (!$this->isConfigured()) {
            return false;
        }

        $body = "<p>Olá, {$requester['name']}.</p>"
            . "<p>O recurso <strong>" . htmlspecialchars($resourceLabel, ENT_QUOTES) . "</strong> (qtd: {$quantity}) "
            . "foi <strong>recusado</strong> para sua reserva "
            . "<strong>" . htmlspecialchars($booking['title'], ENT_QUOTES) . "</strong>.</p>"
            . "<p><strong>Motivo:</strong> " . htmlspecialchars($reason, ENT_QUOTES) . "</p>"
            . "<p><a href=\"{$url}\">Ver reserva</a></p>";

        return $this->send($requester['email'], $subject, $body);
    }

    // ── Resource return notifications (Sprint R5 — RN-R05/RN-R07) ───────────

    /**
     * Notifica todos os técnicos ativos da instituição que uma devolução foi registrada
     * e aguarda confirmação física. RF-R06 / RN-R07.
     */
    public function resourceReturnRegistered(
        array $booking,
        array $returnedBy,
        array $resource,
        int   $quantity
    ): void {
        $url = $this->appUrl . '/admin/recursos-reservas';
        $resourceLabel = $resource['name']
            . (!empty($resource['code']) ? ' (' . $resource['code'] . ')' : '');
        $title   = "Devolução registrada — confirmar recebimento";
        $message = "{$returnedBy['name']} registrou a devolução de {$quantity}× {$resourceLabel} "
            . "da reserva \"{$booking['title']}\". Confirme o recebimento físico.";

        $technicians = db_connect()->table('users')
            ->where('institution_id', $booking['institution_id'])
            ->where('is_active', 1)
            ->whereIn('role', ['role_technician', 'role_coordinator', 'role_vice_director', 'role_director', 'role_admin'])
            ->get()->getResultArray();

        foreach ($technicians as $tech) {
            try {
                $this->saveNotification($tech, 'booking_resource.returned', $title, $message, $url);
            } catch (\Throwable $e) {
                log_message('error', '[NotificationService] resourceReturnRegistered DB error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Notifica o solicitante que a devolução foi confirmada pelo técnico.
     * RF-R06 / RN-R07.
     */
    public function returnConfirmed(
        array $booking,
        array $requester,
        array $resource,
        int   $quantity
    ): bool {
        $resourceLabel = $resource['name']
            . (!empty($resource['code']) ? ' (' . $resource['code'] . ')' : '');
        $subject = "[{$this->appName}] Devolução confirmada — {$resourceLabel}";
        $url     = $this->appUrl . '/reservas/' . $booking['id'];
        $message = "Devolução de {$quantity}× {$resourceLabel} confirmada pelo técnico "
            . "para a reserva \"{$booking['title']}\".";

        $this->saveNotification(
            $requester,
            'booking_resource.return_confirmed',
            "Devolução confirmada",
            $message,
            $url
        );

        if (!$this->isConfigured()) {
            return false;
        }

        $body = "<p>Olá, {$requester['name']}.</p>"
            . "<p>A devolução de <strong>" . htmlspecialchars($resourceLabel, ENT_QUOTES) . "</strong> "
            . "(qtd: {$quantity}) referente à reserva "
            . "<strong>" . htmlspecialchars($booking['title'], ENT_QUOTES) . "</strong> "
            . "foi <strong>confirmada</strong> pelo técnico.</p>"
            . "<p><a href=\"{$url}\">Ver reserva</a></p>";

        return $this->send($requester['email'], $subject, $body);
    }

    /**
     * Notifica o solicitante que a devolução foi rejeitada pelo técnico.
     * RF-R06 / RN-R07 — status reverte para 'approved'; motivo obrigatório.
     */
    public function returnRejected(
        array  $booking,
        array  $requester,
        array  $resource,
        int    $quantity,
        string $reason
    ): bool {
        $resourceLabel = $resource['name']
            . (!empty($resource['code']) ? ' (' . $resource['code'] . ')' : '');
        $subject = "[{$this->appName}] Devolução rejeitada — {$resourceLabel}";
        $url     = $this->appUrl . '/reservas/' . $booking['id'];
        $message = "A devolução de {$quantity}× {$resourceLabel} da reserva "
            . "\"{$booking['title']}\" foi rejeitada. Motivo: {$reason}";

        $this->saveNotification(
            $requester,
            'booking_resource.return_rejected',
            "Devolução rejeitada",
            $message,
            $url
        );

        if (!$this->isConfigured()) {
            return false;
        }

        $body = "<p>Olá, {$requester['name']}.</p>"
            . "<p>A devolução de <strong>" . htmlspecialchars($resourceLabel, ENT_QUOTES) . "</strong> "
            . "(qtd: {$quantity}) da reserva "
            . "<strong>" . htmlspecialchars($booking['title'], ENT_QUOTES) . "</strong> "
            . "foi <strong>rejeitada</strong> pelo técnico.</p>"
            . "<p><strong>Motivo:</strong> " . htmlspecialchars($reason, ENT_QUOTES) . "</p>"
            . "<p>Por favor, regularize a situação e registre a devolução novamente.</p>"
            . "<p><a href=\"{$url}\">Ver reserva</a></p>";

        return $this->send($requester['email'], $subject, $body);
    }

    // ── Resource return overdue (RN-R09) ─────────────────────────────────────

    /**
     * RN-R09 — Notifica técnicos e solicitante sobre recurso com devolução pendente vencida.
     * Chamado pelo ResourceReturnReminders command. Nunca lança exceção.
     */
    public function resourceReturnOverdue(
        array $booking,
        array $requester,
        array $resource,
        int   $quantity
    ): void {
        $adminUrl      = $this->appUrl . '/admin/recursos-reservas';
        $bookingUrl    = $this->appUrl . '/reservas/' . $booking['id'];
        $resourceLabel = $resource['name']
            . (!empty($resource['code']) ? ' (' . $resource['code'] . ')' : '');

        $techTitle   = "Devolução pendente — prazo vencido";
        $techMessage = "{$quantity}× {$resourceLabel} da reserva \"{$booking['title']}\" "
            . "não foi devolvido após o encerramento. Verifique o painel de devoluções.";

        // Notify all active staff
        $technicians = db_connect()->table('users')
            ->where('institution_id', $booking['institution_id'])
            ->where('is_active', 1)
            ->whereIn('role', ['role_technician', 'role_coordinator', 'role_vice_director', 'role_director', 'role_admin'])
            ->get()->getResultArray();

        foreach ($technicians as $tech) {
            try {
                $this->saveNotification($tech, 'booking_resource.return_overdue', $techTitle, $techMessage, $adminUrl);
            } catch (\Throwable $e) {
                log_message('error', '[NotificationService] resourceReturnOverdue tech DB: ' . $e->getMessage());
            }

            if ($this->isConfigured() && !empty($tech['email'])) {
                $techSubject = "[{$this->appName}] Devolução pendente — prazo vencido";
                $techBody    = "<p>Olá, {$tech['name']}.</p>"
                    . "<p>O recurso <strong>" . htmlspecialchars($resourceLabel, ENT_QUOTES) . "</strong> "
                    . "(qtd: {$quantity}) da reserva "
                    . "<strong>" . htmlspecialchars($booking['title'], ENT_QUOTES) . "</strong> "
                    . "não foi devolvido após o encerramento do período reservado "
                    . "pelo solicitante <strong>" . htmlspecialchars($requester['name'], ENT_QUOTES) . "</strong>.</p>"
                    . "<p><a href=\"{$adminUrl}\">Acessar painel de devoluções</a></p>";
                try {
                    $this->send($tech['email'], $techSubject, $techBody);
                } catch (\Throwable $e) {
                    log_message('error', '[NotificationService] resourceReturnOverdue tech email: ' . $e->getMessage());
                }
            }
        }

        // Notify requester (in-app)
        $requesterMessage = "Você tem um recurso pendente de devolução: {$quantity}× {$resourceLabel} "
            . "da reserva \"{$booking['title']}\". Regularize o quanto antes para poder criar novas reservas.";
        try {
            $this->saveNotification(
                $requester,
                'booking_resource.return_overdue',
                "Devolução pendente — prazo vencido",
                $requesterMessage,
                $bookingUrl
            );
        } catch (\Throwable $e) {
            log_message('error', '[NotificationService] resourceReturnOverdue requester DB: ' . $e->getMessage());
        }

        // E-mail to requester if SMTP configured
        if ($this->isConfigured() && !empty($requester['email'])) {
            $subject = "[{$this->appName}] Devolução pendente — {$resourceLabel}";
            $body    = "<p>Olá, {$requester['name']}.</p>"
                . "<p>O recurso <strong>" . htmlspecialchars($resourceLabel, ENT_QUOTES) . "</strong> "
                . "(qtd: {$quantity}) da reserva "
                . "<strong>" . htmlspecialchars($booking['title'], ENT_QUOTES) . "</strong> "
                . "não foi devolvido após o encerramento do período.</p>"
                . "<p>Por favor, registre a devolução o quanto antes para poder criar novas reservas.</p>"
                . "<p><a href=\"{$bookingUrl}\">Ver reserva</a></p>";
            try {
                $this->send($requester['email'], $subject, $body);
            } catch (\Throwable $e) {
                log_message('error', '[NotificationService] resourceReturnOverdue email: ' . $e->getMessage());
            }
        }
    }

    // ── Waitlist notification ─────────────────────────────────────────────────

    /**
     * Notifica o próximo usuário na lista de espera que o slot ficou disponível.
     *
     * @param array $entry    Waitlist entry row
     * @param array $user     The waiting user
     * @param array $room     Room data
     * @param array $booking  The freed booking (for date/time reference)
     */
    public function waitlistAvailable(array $entry, array $user, array $room, array $booking): bool
    {
        $subject = "[{$this->appName}] Vaga disponível — {$room['name']}";
        $url     = $this->appUrl . '/reservas/nova?room_id=' . $room['id']
                   . '&date=' . $booking['date']
                   . '&start_time=' . $booking['start_time']
                   . '&end_time=' . $booking['end_time'];
        $body    = view('emails/waitlist_available', [
            'appName' => $this->appName,
            'appUrl'  => $this->appUrl,
            'entry'   => $entry,
            'user'    => $user,
            'room'    => $room,
            'booking' => $booking,
            'bookUrl' => $url,
        ]);

        $this->saveNotification(
            $user,
            'waitlist.available',
            "Vaga disponível em {$room['name']}",
            "Uma vaga no horário " . substr($booking['start_time'], 0, 5)
                . "–" . substr($booking['end_time'], 0, 5)
                . " de " . date('d/m/Y', strtotime($booking['date']))
                . " está disponível em {$room['name']}.",
            $url
        );

        return $this->send($user['email'], $subject, $body);
    }

    // ── Account / Auth notifications ─────────────────────────────────────────

    /**
     * Sends an email invite to a new user with a link to accept and set up their account.
     * No in-app notification (user doesn't have an account yet).
     */
    public function userInvited(array $invite, string $inviterName, array $institution): bool
    {
        $subject  = "[{$this->appName}] Você foi convidado para acessar o sistema";
        $acceptUrl = $this->appUrl . '/convite/' . $invite['token'];

        $body = view('emails/user_invited', [
            'appName'      => $this->appName,
            'appUrl'       => $this->appUrl,
            'invite'       => $invite,
            'inviterName'  => $inviterName,
            'institution'  => $institution,
            'acceptUrl'    => $acceptUrl,
        ]);

        return $this->send($invite['email'], $subject, $body);
    }

    /**
     * Sends a password reset link to the user.
     * No in-app notification (user may be unable to log in).
     */
    public function passwordReset(array $user, string $token, array $institution): bool
    {
        $subject  = "[{$this->appName}] Redefinição de senha";
        $resetUrl = $this->appUrl . '/redefinir-senha/' . $token;

        $body = view('emails/password_reset', [
            'appName'  => $this->appName,
            'appUrl'   => $this->appUrl,
            'user'     => $user,
            'resetUrl' => $resetUrl,
        ]);

        return $this->send($user['email'], $subject, $body);
    }
}
