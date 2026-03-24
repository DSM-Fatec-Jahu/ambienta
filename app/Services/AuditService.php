<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * Records actions in the append-only audit_logs table.
 * Rows are never updated or deleted.
 *
 * Usage:
 *   service('audit')->log('auth.login', 'user', $userId);
 *   service('audit')->log('reservation.approved', 'reservation', $id, $old, $new);
 */
class AuditService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    /**
     * @param string     $action      Dot-notation event (e.g. auth.login)
     * @param string     $entityType  Entity class name (e.g. 'user', 'reservation')
     * @param int|null   $entityId    PK of the affected row
     * @param array|null $oldValues   Snapshot before
     * @param array|null $newValues   Snapshot after
     * @param int|null   $actorId     User who triggered the action (null = system)
     */
    public function log(
        string $action,
        string $entityType,
        ?int   $entityId  = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int   $actorId   = null
    ): void {
        // Resolve actor from session if not explicitly provided
        if ($actorId === null) {
            $actorId = session()->get('user_id') ?: null;
        }

        try {
            $this->db->table('audit_logs')->insert([
                'actor_id'    => $actorId,
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'old_values'  => $oldValues  !== null ? json_encode($oldValues,  JSON_UNESCAPED_UNICODE) : null,
                'new_values'  => $newValues  !== null ? json_encode($newValues,  JSON_UNESCAPED_UNICODE) : null,
                'ip_address'  => $this->getIp(),
                'user_agent'  => substr((string) service('request')->getUserAgent(), 0, 500),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Audit must never break the main flow
            log_message('error', '[AuditService] Failed to write log: ' . $e->getMessage());
        }
    }

    protected function getIp(): string
    {
        $request = service('request');
        return $request->getIPAddress();
    }
}
