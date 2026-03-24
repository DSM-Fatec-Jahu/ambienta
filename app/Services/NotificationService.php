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
}
