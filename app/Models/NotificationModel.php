<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table         = 'notifications';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;

    protected $allowedFields = [
        'institution_id',
        'user_id',
        'type',
        'title',
        'body',
        'url',
        'read_at',
        'created_at',
    ];

    /**
     * Creates a notification record in the database.
     */
    public function createNotification(
        int    $institutionId,
        int    $userId,
        string $type,
        string $title,
        string $body = '',
        string $url  = ''
    ): int {
        return (int) $this->insert([
            'institution_id' => $institutionId,
            'user_id'        => $userId,
            'type'           => $type,
            'title'          => $title,
            'body'           => $body ?: null,
            'url'            => $url  ?: null,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Returns latest notifications for a user (newest first).
     */
    public function forUser(int $userId, int $limit = 20): array
    {
        return $this->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Returns count of unread notifications for a user.
     */
    public function unreadCount(int $userId): int
    {
        return (int) $this->where('user_id', $userId)
                          ->where('read_at IS NULL')
                          ->countAllResults();
    }

    /**
     * Marks a single notification as read (only if owned by the user).
     */
    public function markRead(int $id, int $userId): bool
    {
        return $this->where('id', $id)
                    ->where('user_id', $userId)
                    ->where('read_at IS NULL')
                    ->set(['read_at' => date('Y-m-d H:i:s')])
                    ->update() !== false;
    }

    /**
     * Marks all unread notifications as read for a user.
     */
    public function markAllRead(int $userId): void
    {
        $this->where('user_id', $userId)
             ->where('read_at IS NULL')
             ->set(['read_at' => date('Y-m-d H:i:s')])
             ->update();
    }
}
