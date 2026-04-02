<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ResourceModel — primary model for the `resources` table (Sprint R1).
 *
 * Replaces the legacy EquipmentModel for new code. EquipmentModel is kept as a
 * compatibility alias pointing to the same table for existing controllers that
 * have not yet been migrated.
 */
class ResourceModel extends Model
{
    protected $table          = 'resources';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'institution_id',
        'name',
        'category',
        'code',
        'description',
        'quantity_total',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name'           => 'required|max_length[150]',
        'institution_id' => 'required|integer',
        'quantity_total' => 'required|integer|greater_than[0]',
    ];

    // ── Basic queries ────────────────────────────────────────────────────────

    public function activeForInstitution(int $institutionId): array
    {
        return $this->where('institution_id', $institutionId)
                    ->where('is_active', 1)
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }

    // ── Stock location queries ────────────────────────────────────────────────

    /**
     * Returns all active resources NOT allocated to any room (general stock).
     */
    public function inGeneralStock(int $institutionId): array
    {
        return $this->db->table('resources r')
            ->select('r.*')
            ->where('r.institution_id', $institutionId)
            ->where('r.is_active', 1)
            ->where('r.deleted_at IS NULL')
            ->whereNotIn('r.id', function ($builder) {
                $builder->select('resource_id')->from('room_resources');
            })
            ->orderBy('r.name', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Paginated search over general stock resources (for lazy dropdown).
     */
    public function inGeneralStockSearch(int $institutionId, string $q, int $limit, int $offset): array
    {
        return $this->_inGeneralStockQuery($institutionId, $q)
            ->limit($limit, $offset)
            ->get()->getResultArray();
    }

    public function inGeneralStockCount(int $institutionId, string $q): int
    {
        return (int) $this->_inGeneralStockQuery($institutionId, $q)->countAllResults();
    }

    private function _inGeneralStockQuery(int $institutionId, string $q): \CodeIgniter\Database\BaseBuilder
    {
        $qb = $this->db->table('resources r')
            ->select('r.id, r.name, r.code, r.quantity_total')
            ->where('r.institution_id', $institutionId)
            ->where('r.is_active', 1)
            ->where('r.deleted_at IS NULL')
            ->whereNotIn('r.id', function ($builder) {
                $builder->select('resource_id')->from('room_resources');
            })
            ->orderBy('r.name', 'ASC');

        if ($q !== '') {
            $qb->groupStart()
                ->like('r.name', $q)
                ->orLike('r.code', $q)
            ->groupEnd();
        }

        return $qb;
    }

    /**
     * Paginated search over resources allocated to a room.
     */
    public function allocatedToRoomSearch(int $roomId, string $q, int $limit, int $offset): array
    {
        return $this->_allocatedToRoomQuery($roomId, $q)
            ->limit($limit, $offset)
            ->get()->getResultArray();
    }

    public function allocatedToRoomCount(int $roomId, string $q): int
    {
        return (int) $this->_allocatedToRoomQuery($roomId, $q)->countAllResults();
    }

    private function _allocatedToRoomQuery(int $roomId, string $q): \CodeIgniter\Database\BaseBuilder
    {
        $qb = $this->db->table('room_resources rr')
            ->select('r.id, r.name, r.code, r.quantity_total,
                      rr.quantity AS allocated_quantity, rr.id AS room_resource_id,
                      rr.allocated_by_id, rr.allocated_at,
                      u.name AS allocated_by_name')
            ->join('resources r', 'r.id = rr.resource_id')
            ->join('users u',     'u.id = rr.allocated_by_id', 'left')
            ->where('rr.room_id', $roomId)
            ->where('r.deleted_at IS NULL')
            ->orderBy('r.name', 'ASC');

        if ($q !== '') {
            $qb->groupStart()
                ->like('r.name', $q)
                ->orLike('r.code', $q)
            ->groupEnd();
        }

        return $qb;
    }

    /**
     * Returns resources currently allocated to a specific room.
     */
    public function allocatedToRoom(int $roomId): array
    {
        return $this->db->table('room_resources rr')
            ->select('r.*, rr.quantity AS allocated_quantity, rr.id AS room_resource_id,
                      rr.allocated_by_id, rr.allocated_at,
                      u.name AS allocated_by_name')
            ->join('resources r',  'r.id = rr.resource_id')
            ->join('users u',      'u.id = rr.allocated_by_id', 'left')
            ->where('rr.room_id', $roomId)
            ->where('r.deleted_at IS NULL')
            ->orderBy('r.name', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Returns general-stock resources available for a given booking slot.
     *
     * Availability = general stock resources (not in room_resources) minus those
     * already committed in booking_resources (status approved/pending) that overlap
     * the requested date/time window.
     *
     * @param int         $institutionId  Institution scope.
     * @param string      $date           Booking date (Y-m-d).
     * @param string      $startTime      Start time (H:i or H:i:s).
     * @param string      $endTime        End time (H:i or H:i:s).
     * @param int|null    $excludeRoomId  Exclude resources allocated to this room
     *                                   (they are shown as "included automatically").
     */
    public function availableForBookingSlot(
        int    $institutionId,
        string $date,
        string $startTime,
        string $endTime,
        ?int   $excludeRoomId = null
    ): array {
        // Start with general stock (not allocated to any room)
        $query = $this->db->table('resources r')
            ->select('r.*, (r.quantity_total - COALESCE(booked.booked_qty, 0)) AS available_qty')
            ->where('r.institution_id', $institutionId)
            ->where('r.is_active', 1)
            ->where('r.deleted_at IS NULL')
            // Only general stock — not currently allocated to any room
            ->whereNotIn('r.id', function ($b) {
                $b->select('resource_id')->from('room_resources');
            });

        // Exclude resources that belong to the booking's own room
        if ($excludeRoomId) {
            // These are already shown as "included automatically"
            $query->whereNotIn('r.id', function ($b) use ($excludeRoomId) {
                $b->select('resource_id')->from('room_resources')->where('room_id', $excludeRoomId);
            });
        }

        // Subquery: how many units are already committed in overlapping bookings
        $bookedSub = $this->db->table('booking_resources br')
            ->select('br.resource_id, SUM(br.quantity) AS booked_qty')
            ->join('bookings bk', 'bk.id = br.booking_id')
            ->where('bk.date', $date)
            ->whereIn('bk.status', ['pending', 'approved'])
            ->where('bk.deleted_at IS NULL')
            ->where('bk.start_time <', $endTime)
            ->where('bk.end_time >', $startTime)
            ->whereIn('br.status', ['pending', 'approved'])
            ->groupBy('br.resource_id')
            ->getCompiledSelect();

        $query->join("({$bookedSub}) booked", 'booked.resource_id = r.id', 'left');
        $query->having('available_qty >', 0);
        $query->orderBy('r.name', 'ASC');

        return $query->get()->getResultArray();
    }

    /**
     * Returns resources with their current location (room name or NULL for general stock).
     * Used for the admin resource listing table.
     */
    public function withCurrentLocation(int $institutionId): array
    {
        return $this->db->table('resources r')
            ->select('r.*, rm.name AS current_room_name, rm.code AS current_room_abbr,
                      rr.quantity AS allocated_quantity')
            ->join('room_resources rr', 'rr.resource_id = r.id', 'left')
            ->join('rooms rm',          'rm.id = rr.room_id',     'left')
            ->where('r.institution_id', $institutionId)
            ->where('r.deleted_at IS NULL')
            ->orderBy('r.name', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Check if a resource has any movements (used to block hard deletes).
     */
    public function hasMovements(int $resourceId): bool
    {
        $count = $this->db->table('resource_movements')
            ->where('resource_id', $resourceId)
            ->countAllResults();

        if ($count > 0) {
            return true;
        }

        $countBooking = $this->db->table('booking_resources')
            ->where('resource_id', $resourceId)
            ->countAllResults();

        return $countBooking > 0;
    }

    // ── Grouping queries (Sprint R8) ──────────────────────────────────────────

    /**
     * Returns resources allocated to a room, grouped by name+category (no id/code).
     * Used for requester-facing views (RN-R13).
     *
     * @return array<array{name: string, category: string|null, total_quantity: int}>
     */
    public function getGroupedByRoom(int $roomId): array
    {
        return $this->db->table('room_resources rr')
            ->select('r.name, r.category, SUM(rr.quantity) AS total_quantity')
            ->join('resources r', 'r.id = rr.resource_id')
            ->where('rr.room_id', $roomId)
            ->where('r.is_active', 1)
            ->where('r.deleted_at IS NULL')
            ->groupBy('r.name, r.category')
            ->orderBy('r.name', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Returns general-stock resources available for a slot, grouped by name+category (no id/code).
     * Used for requester-facing views (RN-R13).
     *
     * @return array<array{name: string, category: string|null, available_qty: int}>
     */
    public function getGroupedGeneralStock(
        int    $institutionId,
        string $date,
        string $startTime,
        string $endTime
    ): array {
        $raw     = $this->availableForBookingSlot($institutionId, $date, $startTime, $endTime);
        $grouped = [];

        foreach ($raw as $item) {
            $key = $item['name'] . '||' . ($item['category'] ?? '');
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'name'          => $item['name'],
                    'category'      => $item['category'] ?? null,
                    'available_qty' => 0,
                ];
            }
            $grouped[$key]['available_qty'] += (int) $item['available_qty'];
        }

        return array_values(array_filter($grouped, fn($g) => $g['available_qty'] > 0));
    }

    /**
     * Returns room IDs that contain at least one resource matching the given term.
     * Matches exact category (case-insensitive) OR partial name (RN-R12).
     *
     * @return int[]
     */
    public function roomIdsHavingResource(int $institutionId, string $term): array
    {
        $rows = $this->db->table('room_resources rr')
            ->select('rr.room_id')
            ->distinct()
            ->join('resources r', 'r.id = rr.resource_id')
            ->where('r.institution_id', $institutionId)
            ->where('r.is_active', 1)
            ->where('r.deleted_at IS NULL')
            ->groupStart()
                ->where('r.category', $term)
                ->orLike('r.name', $term, 'both')
            ->groupEnd()
            ->get()->getResultArray();

        return array_map('intval', array_column($rows, 'room_id'));
    }

    /**
     * Returns a unified list of distinct categories and names for use as filter terms.
     * Used in availability/booking filter dropdowns (RN-R12).
     *
     * @return array<array{term: string, type: string}>
     */
    public function getDistinctCategoriesAndNames(int $institutionId): array
    {
        return $this->db->table('resources')
            ->select("DISTINCT COALESCE(category, name) AS term,
                      CASE WHEN category IS NOT NULL THEN 'category' ELSE 'name' END AS type")
            ->where('institution_id', $institutionId)
            ->where('is_active', 1)
            ->where('deleted_at IS NULL')
            ->orderBy('term', 'ASC')
            ->get()->getResultArray();
    }

    // ── Admin search / pagination (lazy table) ───────────────────────────────

    public function getDistinctCategories(int $institutionId): array
    {
        return $this->db->table('resources')
            ->distinct()
            ->select('category')
            ->where('institution_id', $institutionId)
            ->where('deleted_at IS NULL')
            ->where('category IS NOT NULL')
            ->where('category !=', '')
            ->orderBy('category', 'ASC')
            ->get()->getResultArray();
    }

    public function search(int $institutionId, string $q, string $categoria, int $localId, int $status, int $limit, int $offset): array
    {
        return $this->_searchQuery($institutionId, $q, $categoria, $localId, $status)
            ->orderBy('r.name', 'ASC')
            ->limit($limit, $offset)
            ->get()->getResultArray();
    }

    public function searchCount(int $institutionId, string $q, string $categoria, int $localId, int $status): int
    {
        return (int) $this->_searchQuery($institutionId, $q, $categoria, $localId, $status)
            ->countAllResults();
    }

    private function _searchQuery(int $institutionId, string $q, string $categoria, int $localId, int $status): \CodeIgniter\Database\BaseBuilder
    {
        $qb = $this->db->table('resources r')
            ->select('r.*, rm.name AS current_room_name, rm.code AS current_room_abbr, rr.quantity AS allocated_quantity')
            ->join('room_resources rr', 'rr.resource_id = r.id', 'left')
            ->join('rooms rm',          'rm.id = rr.room_id',     'left')
            ->where('r.institution_id', $institutionId)
            ->where('r.deleted_at IS NULL');

        if ($q !== '') {
            $qb->groupStart()
                ->like('r.name', $q)
                ->orLike('r.code', $q)
            ->groupEnd();
        }

        if ($categoria !== '') {
            $qb->where('r.category', $categoria);
        }

        if ($localId === -1) {
            $qb->whereNotIn('r.id', function ($b) {
                $b->select('resource_id')->from('room_resources');
            });
        } elseif ($localId > 0) {
            $qb->where('rr.room_id', $localId);
        }

        if ($status === 1)     { $qb->where('r.is_active', 1); }
        elseif ($status === 2) { $qb->where('r.is_active', 0); }

        return $qb;
    }

    /**
     * Compatibility: returns active equipment with available quantity for a booking slot.
     * Updated to use new table/column names.
     */
    public function availableQuantitiesForSlot(
        int    $institutionId,
        string $date,
        string $startTime,
        string $endTime
    ): array {
        $equipment = $this->activeForInstitution($institutionId);

        if (empty($equipment)) {
            return [];
        }

        $ids = array_column($equipment, 'id');

        $bookedRows = $this->db->table('booking_resources br')
            ->select('br.resource_id, SUM(br.quantity) AS booked_qty')
            ->join('bookings b', 'b.id = br.booking_id')
            ->where('b.date', $date)
            ->whereIn('b.status', ['pending', 'approved'])
            ->where('b.deleted_at IS NULL')
            ->where('b.start_time <', $endTime)
            ->where('b.end_time >', $startTime)
            ->whereIn('br.resource_id', $ids)
            ->groupBy('br.resource_id')
            ->get()->getResultArray();

        $bookedMap = array_column($bookedRows, 'booked_qty', 'resource_id');

        foreach ($equipment as &$eq) {
            $booked              = (int) ($bookedMap[$eq['id']] ?? 0);
            $eq['available_qty'] = max(0, (int) $eq['quantity_total'] - $booked);
        }

        return $equipment;
    }
}
