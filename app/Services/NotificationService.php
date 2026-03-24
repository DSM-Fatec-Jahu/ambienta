<?php

namespace App\Services;

use CodeIgniter\Email\Email;

/**
 * Sends transactional e-mails related to the booking lifecycle.
 *
 * Usage:
 *   service('notification')->bookingCreated($booking, $user, $room);
 *   service('notification')->bookingApproved($booking, $user, $room, $reviewer);
 *   service('notification')->bookingRejected($booking, $user, $room, $reviewer, $notes);
 *   service('notification')->bookingCancelled($booking, $user, $room);
 *
 * E-mail is only sent when MAIL_FROM_ADDRESS is set in .env.
 * Failures are logged but never throw — email must never break the main flow.
 */
class NotificationService
{
    private Email  $mailer;
    private string $fromAddress;
    private string $fromName;
    private string $appName;
    private string $appUrl;

    public function __construct()
    {
        $this->mailer      = \Config\Services::email();
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

    /** Core send helper. */
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

    // ── Booking lifecycle notifications ──────────────────────────────────────

    /** Notifica o solicitante que sua reserva foi criada e está pendente. */
    public function bookingCreated(array $booking, array $user, array $room): bool
    {
        $subject = "[{$this->appName}] Reserva recebida — aguardando aprovação";
        $body    = view('emails/booking_created', [
            'appName' => $this->appName,
            'appUrl'  => $this->appUrl,
            'booking' => $booking,
            'user'    => $user,
            'room'    => $room,
        ]);

        return $this->send($user['email'], $subject, $body);
    }

    /** Notifica o solicitante que sua reserva foi aprovada. */
    public function bookingApproved(array $booking, array $user, array $room, ?array $reviewer = null): bool
    {
        $subject = "[{$this->appName}] ✅ Reserva aprovada — {$booking['title']}";
        $body    = view('emails/booking_approved', [
            'appName'  => $this->appName,
            'appUrl'   => $this->appUrl,
            'booking'  => $booking,
            'user'     => $user,
            'room'     => $room,
            'reviewer' => $reviewer,
        ]);

        return $this->send($user['email'], $subject, $body);
    }

    /** Notifica o solicitante que sua reserva foi recusada. */
    public function bookingRejected(array $booking, array $user, array $room, string $notes): bool
    {
        $subject = "[{$this->appName}] ❌ Reserva recusada — {$booking['title']}";
        $body    = view('emails/booking_rejected', [
            'appName' => $this->appName,
            'appUrl'  => $this->appUrl,
            'booking' => $booking,
            'user'    => $user,
            'room'    => $room,
            'notes'   => $notes,
        ]);

        return $this->send($user['email'], $subject, $body);
    }

    /** Notifica o solicitante que sua reserva foi cancelada. */
    public function bookingCancelled(array $booking, array $user, array $room, string $reason): bool
    {
        $subject = "[{$this->appName}] Reserva cancelada — {$booking['title']}";
        $body    = view('emails/booking_cancelled', [
            'appName' => $this->appName,
            'appUrl'  => $this->appUrl,
            'booking' => $booking,
            'user'    => $user,
            'room'    => $room,
            'reason'  => $reason,
        ]);

        return $this->send($user['email'], $subject, $body);
    }

    /** Envia lembrete D-1 ao solicitante sobre reserva aprovada agendada para amanhã. */
    public function bookingReminder(array $booking, array $user, array $room): bool
    {
        $subject = "[{$this->appName}] Lembrete: sua reserva \"{$booking['title']}\" é amanhã";
        $body    = view('emails/booking_reminder', [
            'appName' => $this->appName,
            'appUrl'  => $this->appUrl,
            'booking' => $booking,
            'user'    => $user,
            'room'    => $room,
        ]);

        return $this->send($user['email'], $subject, $body);
    }

    /** Solicita avaliação de reserva realizada ontem. */
    public function bookingReviewRequest(array $booking, array $user, array $room): bool
    {
        $subject = "[{$this->appName}] Como foi \"{$booking['title']}\"? Avalie o ambiente";
        $body    = view('emails/booking_review_request', [
            'appName' => $this->appName,
            'appUrl'  => $this->appUrl,
            'booking' => $booking,
            'user'    => $user,
            'room'    => $room,
        ]);

        return $this->send($user['email'], $subject, $body);
    }
}
