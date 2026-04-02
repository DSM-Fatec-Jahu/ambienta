<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * BookingResourceModel — manages the `booking_resources` table.
 *
 * Sprint R4 — RN-R04: approval flow is independent of the booking.
 *
 * Status lifecycle:
 *   pending → approved  (technician approves)
 *   pending → rejected  (technician rejects, rejection_note required)
 *   approved → returned (requester registers return — Sprint R5)
 *   returned → return_confirmed | return reverted to approved (technician — Sprint R5)
 */
class BookingResourceModel extends Model
{
    protected $table      = 'booking_resources';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'booking_id',
        'resource_id',
        'quantity',
        'status',
        'approved_by_id',
        'rejected_by_id',
        'rejection_note',
        'returned_at',
        'returned_by_id',
        'confirmed_at',
        'confirmed_by_id',
        'notified_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = false;

    // ── Status constants ──────────────────────────────────────────────────────

    public const STATUS_PENDING          = 'pending';
    public const STATUS_APPROVED         = 'approved';
    public const STATUS_REJECTED         = 'rejected';
    public const STATUS_RETURNED         = 'returned';
    public const STATUS_RETURN_CONFIRMED = 'return_confirmed';

    // ── Queries ───────────────────────────────────────────────────────────────

    /**
     * Returns all pending resource requests for an institution, with booking,
     * resource and requester details joined in.
     */
    public function pendingForInstitution(int $institutionId): array
    {
        return $this->db->table('booking_resources br')
            ->select([
                'br.id',
                'br.booking_id',
                'br.resource_id',
                'br.quantity',
                'br.status',
                'br.created_at',
                'r.name  AS resource_name',
                'r.code  AS resource_code',
                'bk.title        AS booking_title',
                'bk.date         AS booking_date',
                'bk.start_time   AS booking_start',
                'bk.end_time     AS booking_end',
                'bk.status       AS booking_status',
                'rm.name         AS room_name',
                'rm.code AS room_abbr',
                'u.id   AS requester_id',
                'u.name AS requester_name',
            ])
            ->join('bookings bk',  'bk.id  = br.booking_id')
            ->join('resources r',  'r.id   = br.resource_id')
            ->join('rooms rm',     'rm.id  = bk.room_id', 'left')
            ->join('users u',      'u.id   = bk.owner_id', 'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.deleted_at IS NULL')
            ->where('br.status', self::STATUS_PENDING)
            // Exclude resources permanently assigned to the booking's room — they don't need approval
            ->where('br.resource_id NOT IN (SELECT rr.resource_id FROM room_resources rr WHERE rr.room_id = bk.room_id)')
            ->orderBy('br.created_at', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Returns a single booking_resource row with all joined details.
     */
    public function findWithDetails(int $id): ?array
    {
        $row = $this->db->table('booking_resources br')
            ->select([
                'br.*',
                'r.name  AS resource_name',
                'r.code  AS resource_code',
                'bk.title        AS booking_title',
                'bk.date         AS booking_date',
                'bk.start_time   AS booking_start',
                'bk.end_time     AS booking_end',
                'bk.status       AS booking_status',
                'bk.institution_id',
                'bk.owner_id     AS requester_id',
                'u.name          AS requester_name',
                'u.email         AS requester_email',
                'rm.name         AS room_name',
                'rm.id           AS room_id',
            ])
            ->join('bookings bk',  'bk.id  = br.booking_id')
            ->join('resources r',  'r.id   = br.resource_id')
            ->join('rooms rm',     'rm.id  = bk.room_id', 'left')
            ->join('users u',      'u.id   = bk.owner_id', 'left')
            ->where('br.id', $id)
            ->get()->getRowArray();

        return $row ?: null;
    }

    /**
     * Returns all resource requests for a given booking.
     */
    public function forBooking(int $bookingId): array
    {
        return $this->db->table('booking_resources br')
            ->select([
                'br.id',
                'br.resource_id',
                'br.quantity',
                'br.status',
                'br.rejection_note',
                'br.returned_at',
                'br.confirmed_at',
                'r.name     AS resource_name',
                'r.code     AS resource_code',
                'r.category AS resource_category',
            ])
            ->join('resources r', 'r.id = br.resource_id')
            ->where('br.booking_id', $bookingId)
            ->orderBy('r.name', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * RN-R05 / Sprint R5 — resources with status='approved' whose booking has already ended.
     * Used in the technician panel "Aguardando devolução" tab.
     */
    public function awaitingReturnForInstitution(int $institutionId): array
    {
        $now     = date('Y-m-d H:i:s');
        $today   = date('Y-m-d');
        $nowTime = date('H:i:s');

        return $this->db->table('booking_resources br')
            ->select([
                'br.id',
                'br.booking_id',
                'br.resource_id',
                'br.quantity',
                'br.status',
                'br.created_at',
                'r.name  AS resource_name',
                'r.code  AS resource_code',
                'bk.title      AS booking_title',
                'bk.date       AS booking_date',
                'bk.start_time AS booking_start',
                'bk.end_time   AS booking_end',
                'u.id   AS requester_id',
                'u.name AS requester_name',
                'u.email AS requester_email',
            ])
            ->join('bookings bk',  'bk.id  = br.booking_id')
            ->join('resources r',  'r.id   = br.resource_id')
            ->join('users u',      'u.id   = bk.owner_id', 'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.deleted_at IS NULL')
            ->where('br.status', self::STATUS_APPROVED)
            ->groupStart()
                ->where('bk.date <', $today)
                ->orGroupStart()
                    ->where('bk.date', $today)
                    ->where('bk.end_time <=', $nowTime)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('bk.date', 'ASC')
            ->orderBy('bk.end_time', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * RN-R08 / Sprint R5 — counts approved resources whose booking ended more than
     * $deadlineHours ago and no return was registered. Used for the banner in index.
     */
    public function countOverdueReturns(int $userId, int $institutionId, int $deadlineHours = 1): int
    {
        $threshold = date('Y-m-d H:i:s', time() - $deadlineHours * 3600);

        return (int) $this->db->table('booking_resources br')
            ->join('bookings bk', 'bk.id = br.booking_id')
            ->where('bk.owner_id', $userId)
            ->where('bk.institution_id', $institutionId)
            ->where('bk.deleted_at IS NULL')
            ->where('br.status', self::STATUS_APPROVED)
            ->where("CONCAT(bk.date, ' ', bk.end_time) <=", $threshold)
            ->countAllResults();
    }

    /**
     * RN-R08 / Sprint R6 — returns true if $userId has any approved resources
     * whose booking ended more than $deadlineHours ago (overdue, no return registered).
     * Used in BookingsController::store() to block new bookings.
     */
    public function hasOverdueReturns(int $userId, int $institutionId, int $deadlineHours = 1): bool
    {
        $threshold = date('Y-m-d H:i:s', time() - $deadlineHours * 3600);

        $count = $this->db->table('booking_resources br')
            ->join('bookings bk', 'bk.id = br.booking_id')
            ->where('bk.owner_id', $userId)
            ->where('bk.institution_id', $institutionId)
            ->where('bk.deleted_at IS NULL')
            ->where('br.status', self::STATUS_APPROVED)
            ->where("CONCAT(bk.date, ' ', bk.end_time) <=", $threshold)
            ->countAllResults();

        return $count > 0;
    }

    /**
     * RN-R09 / Sprint R6 — returns all approved resources whose booking ended
     * more than $deadlineHours ago and have NOT yet been notified (notified_at IS NULL).
     * Used by ResourceReturnReminders command.
     */
    public function overdueUnnotified(int $institutionId, int $deadlineHours = 1): array
    {
        $threshold = date('Y-m-d H:i:s', time() - $deadlineHours * 3600);

        return $this->db->table('booking_resources br')
            ->select([
                'br.id',
                'br.booking_id',
                'br.resource_id',
                'br.quantity',
                'r.name  AS resource_name',
                'r.code  AS resource_code',
                'bk.title      AS booking_title',
                'bk.date       AS booking_date',
                'bk.end_time   AS booking_end',
                'bk.institution_id',
                'bk.owner_id   AS requester_id',
                'u.name  AS requester_name',
                'u.email AS requester_email',
            ])
            ->join('bookings bk', 'bk.id  = br.booking_id')
            ->join('resources r', 'r.id   = br.resource_id')
            ->join('users u',     'u.id   = bk.owner_id', 'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.deleted_at IS NULL')
            ->where('br.status', self::STATUS_APPROVED)
            ->where('br.notified_at IS NULL')
            ->where("CONCAT(bk.date, ' ', bk.end_time) <=", $threshold)
            ->orderBy('bk.date', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * RN-R09 — Mark a booking_resource as notified to prevent duplicate notifications.
     */
    public function markNotified(int $id): void
    {
        $this->update($id, ['notified_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * RN-R07 / Sprint R5 — resources with status='returned', awaiting technician confirmation.
     * Used in the technician panel "Devoluções a confirmar" tab.
     */
    public function pendingConfirmationForInstitution(int $institutionId): array
    {
        return $this->db->table('booking_resources br')
            ->select([
                'br.id',
                'br.booking_id',
                'br.resource_id',
                'br.quantity',
                'br.status',
                'br.returned_at',
                'r.name  AS resource_name',
                'r.code  AS resource_code',
                'bk.title      AS booking_title',
                'bk.date       AS booking_date',
                'bk.start_time AS booking_start',
                'bk.end_time   AS booking_end',
                'u.id    AS requester_id',
                'u.name  AS requester_name',
                'u.email AS requester_email',
                'ret.name AS returned_by_name',
            ])
            ->join('bookings bk',   'bk.id  = br.booking_id')
            ->join('resources r',   'r.id   = br.resource_id')
            ->join('users u',       'u.id   = bk.owner_id',       'left')
            ->join('users ret',     'ret.id = br.returned_by_id',  'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.deleted_at IS NULL')
            ->where('br.status', self::STATUS_RETURNED)
            ->orderBy('br.returned_at', 'ASC')
            ->get()->getResultArray();
    }
}
