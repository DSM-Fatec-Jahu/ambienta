<?php

namespace App\Controllers;

use App\Models\NotificationModel;

class NotificationsController extends BaseController
{
    private NotificationModel $notifModel;

    public function __construct()
    {
        $this->notifModel = new NotificationModel();
    }

    /** GET /notificacoes */
    public function index(): string
    {
        $user  = $this->currentUser();
        $items = $this->notifModel->forUser((int) $user['id'], 50);

        // Mark all as read when user opens the page
        $this->notifModel->markAllRead((int) $user['id']);

        return view('notifications/index', $this->viewData([
            'pageTitle' => 'Notificações',
            'items'     => $items,
        ]));
    }
}
