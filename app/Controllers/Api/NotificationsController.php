<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\NotificationModel;

/**
 * Authenticated JSON API for in-app notifications.
 *
 * GET  /api/notificacoes            → index()       – unread count + latest 20
 * POST /api/notificacoes/todas-lidas → markAllRead() – mark all read (must be before :num)
 * POST /api/notificacoes/:id/lida   → markRead($id) – mark one read
 */
class NotificationsController extends BaseController
{
    private NotificationModel $notifModel;

    public function __construct()
    {
        $this->notifModel = new NotificationModel();
    }

    public function index(): \CodeIgniter\HTTP\ResponseInterface
    {
        $user  = $this->currentUser();
        $items = $this->notifModel->forUser((int) $user['id'], 20);

        // Format for JSON
        $formatted = array_map(function (array $n): array {
            return [
                'id'         => (int) $n['id'],
                'type'       => $n['type'],
                'title'      => $n['title'],
                'body'       => $n['body'] ?? '',
                'url'        => $n['url'] ?? '',
                'read'       => !empty($n['read_at']),
                'created_at' => $n['created_at'],
                'ago'        => $this->timeAgo($n['created_at']),
            ];
        }, $items);

        return $this->response->setJSON([
            'unread' => $this->notifModel->unreadCount((int) $user['id']),
            'items'  => $formatted,
        ]);
    }

    public function markRead(int $id): \CodeIgniter\HTTP\ResponseInterface
    {
        $user = $this->currentUser();
        $this->notifModel->markRead($id, (int) $user['id']);

        return $this->response->setJSON([
            'ok'     => true,
            'unread' => $this->notifModel->unreadCount((int) $user['id']),
        ]);
    }

    public function markAllRead(): \CodeIgniter\HTTP\ResponseInterface
    {
        $user = $this->currentUser();
        $this->notifModel->markAllRead((int) $user['id']);

        return $this->response->setJSON(['ok' => true, 'unread' => 0]);
    }

    // ── Helper ──────────────────────────────────────────────────────

    private function timeAgo(?string $datetime): string
    {
        if (!$datetime) {
            return '';
        }
        $diff = time() - strtotime($datetime);

        if ($diff < 60)        return 'agora mesmo';
        if ($diff < 3600)      return (int)($diff / 60) . ' min atrás';
        if ($diff < 86400)     return (int)($diff / 3600) . 'h atrás';
        if ($diff < 604800)    return (int)($diff / 86400) . 'd atrás';
        return date('d/m/Y', strtotime($datetime));
    }
}
