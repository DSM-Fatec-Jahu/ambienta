<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingModel extends Model
{
    protected $table          = 'bookings';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'institution_id',
        'owner_id',
        'room_id',
        'title',
        'description',
        'date',
        'start_time',
        'end_time',
        'attendees_count',
        'status',
        'reviewer_id',
        'reviewed_at',
        'review_notes',
        'cancelled_at',
        'cancelled_reason',
        'recurrence_type',
        'recurrence_end_date',
        'recurrence_parent_id',
        'checkin_at',
        'qr_token',
        'creator_id',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'title'           => 'required|max_length[300]',
        'room_id'         => 'required|integer',
        'date'            => 'required|valid_date[Y-m-d]',
        'start_time'      => 'required',
        'end_time'        => 'required',
        'attendees_count' => 'required|integer|greater_than[0]',
    ];

    // ── Queries ─────────────────────────────────────────────────────

    /**
     * Returns bookings for a given user with room/building info.
     */
    public function forUser(int $userId, string $status = ''): array
    {
        $q = $this->db->table('bookings bk')
            ->select('bk.*, r.name AS room_name, r.code AS room_code, b.name AS building_name,
                      u.name AS reviewer_name')
            ->join('rooms r', 'r.id = bk.room_id', 'left')
            ->join('buildings b', 'b.id = r.building_id', 'left')
            ->join('users u', 'u.id = bk.reviewer_id', 'left')
            ->where('bk.owner_id', $userId)
            ->where('bk.deleted_at IS NULL');

        if ($status) {
            $q->where('bk.status', $status);
        }

        return $q->orderBy('bk.date DESC, bk.start_time DESC')->get()->getResultArray();
    }

    /**
     * Returns pending bookings for a given institution (for approval workflow).
     */
    public function pendingForInstitution(int $institutionId): array
    {
        return $this->db->table('bookings bk')
            ->select('bk.*, r.name AS room_name, r.code AS room_code, b.name AS building_name,
                      u.name AS user_name, u.email AS user_email, u.role AS user_role')
            ->join('rooms r', 'r.id = bk.room_id', 'left')
            ->join('buildings b', 'b.id = r.building_id', 'left')
            ->join('users u', 'u.id = bk.owner_id', 'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.status', 'pending')
            ->where('bk.deleted_at IS NULL')
            ->orderBy('bk.date ASC, bk.start_time ASC')
            ->get()->getResultArray();
    }

    /**
     * Returns bookings for a room on a specific date (for conflict check).
     */
    public function forRoomOnDate(int $roomId, string $date, ?int $excludeId = null): array
    {
        $q = $this->db->table('bookings')
            ->where('room_id', $roomId)
            ->where('date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->where('deleted_at IS NULL');

        if ($excludeId) {
            $q->where('id !=', $excludeId);
        }

        return $q->get()->getResultArray();
    }

    /**
     * Checks time conflict for a room: returns true if there's an overlap.
     */
    public function hasConflict(int $roomId, string $date, string $startTime, string $endTime, ?int $excludeId = null): bool
    {
        $existing = $this->forRoomOnDate($roomId, $date, $excludeId);

        foreach ($existing as $b) {
            // Overlap condition: start < existingEnd AND end > existingStart
            if ($startTime < $b['end_time'] && $endTime > $b['start_time']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns booking count stats for dashboard.
     */
    public function statsForUser(int $userId): array
    {
        $today = date('Y-m-d');
        $now   = date('H:i:s');

        $myToday = $this->db->table('bookings')
            ->where('owner_id', $userId)
            ->where('date', $today)
            ->whereIn('status', ['pending', 'approved'])
            ->where('deleted_at IS NULL')
            ->countAllResults();

        $next = $this->db->table('bookings bk')
            ->select('bk.date, bk.start_time, r.name AS room_name')
            ->join('rooms r', 'r.id = bk.room_id', 'left')
            ->where('bk.owner_id', $userId)
            ->where('bk.status', 'approved')
            ->where('bk.deleted_at IS NULL')
            ->groupStart()
                ->where('bk.date >', $today)
                ->orGroupStart()
                    ->where('bk.date', $today)
                    ->where('bk.start_time >', $now)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('bk.date ASC, bk.start_time ASC')
            ->limit(1)
            ->get()->getRowArray();

        return [
            'my_today' => $myToday,
            'next'     => $next,
        ];
    }

    /**
     * Count bookings for a user within the ISO week that contains $date.
     */
    public function countForUserInWeek(int $userId, string $date): int
    {
        $monday = date('Y-m-d', strtotime('monday this week', strtotime($date)));
        $sunday = date('Y-m-d', strtotime('sunday this week', strtotime($date)));

        return (int) $this->db->table('bookings')
            ->where('owner_id', $userId)
            ->where('deleted_at IS NULL')
            ->whereIn('status', ['pending', 'approved'])
            ->where('date >=', $monday)
            ->where('date <=', $sunday)
            ->countAllResults();
    }

    /**
     * Count pending bookings for institution (for sidebar badge).
     */
    public function countPendingForInstitution(int $institutionId): int
    {
        return (int) $this->db->table('bookings')
            ->where('institution_id', $institutionId)
            ->where('status', 'pending')
            ->where('deleted_at IS NULL')
            ->countAllResults();
    }

    /**
     * Find a booking by its QR token.
     */
    public function findByQrToken(string $token): ?array
    {
        return $this->where('qr_token', $token)
                    ->where('deleted_at IS NULL')
                    ->first();
    }

    /**
     * Returns all active bookings in a recurring series, ordered by date.
     * Pass the parent booking's ID (or any child's recurrence_parent_id).
     */
    public function forSeries(int $parentId): array
    {
        return $this->db->table('bookings bk')
            ->select('bk.*, r.name AS room_name, r.code AS room_code')
            ->join('rooms r', 'r.id = bk.room_id', 'left')
            ->groupStart()
                ->where('bk.id', $parentId)
                ->orWhere('bk.recurrence_parent_id', $parentId)
            ->groupEnd()
            ->whereIn('bk.status', ['pending', 'approved'])
            ->where('bk.deleted_at IS NULL')
            ->orderBy('bk.date ASC, bk.start_time ASC')
            ->get()->getResultArray();
    }

    // ── Analytics ────────────────────────────────────────────────────

    /**
     * Count bookings per weekday (0=Sun … 6=Sat) for the last N days.
     * Returns array indexed 0-6 with counts.
     */
    public function countByWeekdayForInstitution(int $institutionId, int $days = 30): array
    {
        $since = date('Y-m-d', strtotime("-{$days} days"));

        $rows = $this->db->table('bookings')
            ->select('DAYOFWEEK(date) - 1 AS dow, COUNT(*) AS total')
            ->where('institution_id', $institutionId)
            ->where('date >=', $since)
            ->whereIn('status', ['approved', 'pending', 'absent'])
            ->where('deleted_at IS NULL')
            ->groupBy('dow')
            ->get()->getResultArray();

        $counts = array_fill(0, 7, 0);
        foreach ($rows as $r) {
            $counts[(int) $r['dow']] = (int) $r['total'];
        }
        return $counts;
    }

    /**
     * Institution-level summary stats for the dashboard.
     * Returns: total, approved, rejected, cancelled, approval_rate, top rooms (up to 5).
     */
    public function institutionSummary(int $institutionId, int $days = 30): array
    {
        $since = date('Y-m-d', strtotime("-{$days} days"));

        $base = $this->db->table('bookings')
            ->where('institution_id', $institutionId)
            ->where('date >=', $since)
            ->where('deleted_at IS NULL');

        $total     = (clone $base)->countAllResults(false);
        $approved  = (clone $base)->where('status', 'approved')->countAllResults(false);
        $rejected  = (clone $base)->where('status', 'rejected')->countAllResults(false);
        $cancelled = (clone $base)->where('status', 'cancelled')->countAllResults(false);

        $approvalRate = $total > 0 ? round($approved / $total * 100) : 0;

        $topRooms = $this->db->table('bookings bk')
            ->select('r.name AS room_name, COUNT(bk.id) AS total')
            ->join('rooms r', 'r.id = bk.room_id', 'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.date >=', $since)
            ->whereIn('bk.status', ['approved', 'absent'])
            ->where('bk.deleted_at IS NULL')
            ->groupBy('bk.room_id')
            ->orderBy('total DESC')
            ->limit(5)
            ->get()->getResultArray();

        return compact('total', 'approved', 'rejected', 'cancelled', 'approvalRate', 'topRooms');
    }

    /**
     * Equipment usage stats for a date range.
     * Returns list of equipment with total requests and total quantity, ordered by most used.
     */
    public function equipmentUsage(int $institutionId, string $dateFrom, string $dateTo): array
    {
        return $this->db->table('booking_equipment be')
            ->select('e.id AS equipment_id, e.name AS equipment_name, e.code AS equipment_code,
                      COUNT(DISTINCT bk.id) AS total_bookings,
                      SUM(be.quantity) AS total_quantity')
            ->join('bookings bk', 'bk.id = be.booking_id', 'inner')
            ->join('equipment e', 'e.id = be.equipment_id', 'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.deleted_at IS NULL')
            ->where('bk.date >=', $dateFrom)
            ->where('bk.date <=', $dateTo)
            ->whereIn('bk.status', ['approved', 'absent', 'pending'])
            ->groupBy('be.equipment_id')
            ->orderBy('total_bookings DESC')
            ->get()->getResultArray();
    }

    /**
     * Occupancy stats per room for a date range.
     * Returns array of rooms with: room_id, room_name, building_name, total_bookings, total_hours, capacity
     */
    public function occupancyByRoom(int $institutionId, string $dateFrom, string $dateTo): array
    {
        return $this->db->table('bookings bk')
            ->select('bk.room_id,
                      r.name AS room_name, r.code AS room_code, r.capacity,
                      b.name AS building_name,
                      COUNT(bk.id) AS total_bookings,
                      SUM(TIME_TO_SEC(TIMEDIFF(bk.end_time, bk.start_time)) / 3600) AS total_hours')
            ->join('rooms r', 'r.id = bk.room_id', 'left')
            ->join('buildings b', 'b.id = r.building_id', 'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.deleted_at IS NULL')
            ->where('bk.date >=', $dateFrom)
            ->where('bk.date <=', $dateTo)
            ->whereIn('bk.status', ['approved', 'absent'])
            ->groupBy('bk.room_id')
            ->orderBy('total_hours DESC')
            ->get()->getResultArray();
    }

    /**
     * Booking activity stats grouped by user for a date range.
     */
    public function activityByUser(int $institutionId, string $dateFrom, string $dateTo): array
    {
        return $this->db->table('bookings bk')
            ->select('u.id AS user_id, u.name AS user_name, u.email,
                      u.role AS user_role,
                      COUNT(*) AS total,
                      SUM(bk.status = "pending")   AS total_pending,
                      SUM(bk.status = "approved")  AS total_approved,
                      SUM(bk.status = "rejected")  AS total_rejected,
                      SUM(bk.status = "cancelled") AS total_cancelled,
                      SUM(bk.status = "absent")    AS total_absent')
            ->join('users u', 'u.id = bk.owner_id', 'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.deleted_at IS NULL')
            ->where('bk.date >=', $dateFrom)
            ->where('bk.date <=', $dateTo)
            ->groupBy('bk.owner_id')
            ->orderBy('total', 'DESC')
            ->get()->getResultArray();
    }
}
