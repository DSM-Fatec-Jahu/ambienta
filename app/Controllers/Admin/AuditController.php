<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class AuditController extends BaseController
{
    private const PER_PAGE = 50;

    public function index(): string
    {
        $institutionId = $this->institution['id'] ?? 0;
        $db            = db_connect();

        $action     = trim($this->request->getGet('action')      ?? '');
        $entityType = trim($this->request->getGet('entity_type') ?? '');
        $actorId    = (int) ($this->request->getGet('actor_id')  ?? 0);
        $dateFrom   = trim($this->request->getGet('date_from')   ?? '');
        $dateTo     = trim($this->request->getGet('date_to')     ?? '');

        $query = $db->table('audit_logs al')
            ->select('al.*, u.name AS actor_name, u.email AS actor_email')
            ->join('users u', 'u.id = al.actor_id', 'left')
            ->where('u.institution_id', $institutionId)
            ->orderBy('al.id', 'DESC');

        // Also include system events (actor_id IS NULL) for completeness
        // But scope to institution users only — leave system logs
        // We'll use OR grouping to also show nulls
        // Actually: filter only logs from users in this institution OR actor is NULL
        $query = $db->table('audit_logs al')
            ->select('al.*, u.name AS actor_name, u.email AS actor_email')
            ->join('users u', 'u.id = al.actor_id AND u.institution_id = ' . (int)$institutionId, 'left')
            ->groupStart()
                ->where('u.institution_id', $institutionId)
                ->orWhere('al.actor_id IS NULL')
            ->groupEnd()
            ->orderBy('al.id', 'DESC');

        if ($action) {
            $query->like('al.action', $action);
        }
        if ($entityType) {
            $query->where('al.entity_type', $entityType);
        }
        if ($actorId) {
            $query->where('al.actor_id', $actorId);
        }
        if ($dateFrom) {
            $query->where('al.created_at >=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('al.created_at <=', $dateTo . ' 23:59:59');
        }

        $total = $query->countAllResults(false);
        $page  = max(1, (int) ($this->request->getGet('page') ?? 1));
        $rows  = $query->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)
                       ->get()->getResultArray();

        // Distinct actors for filter dropdown
        $actors = $db->table('audit_logs al')
            ->select('u.id, u.name')
            ->join('users u', 'u.id = al.actor_id', 'inner')
            ->where('u.institution_id', $institutionId)
            ->groupBy('u.id')
            ->orderBy('u.name')
            ->get()->getResultArray();

        return view('admin/audit/index', $this->viewData([
            'pageTitle'  => 'Auditoria',
            'rows'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => self::PER_PAGE,
            'actors'     => $actors,
            'filters'    => compact('action', 'entityType', 'actorId', 'dateFrom', 'dateTo'),
        ]));
    }
}
