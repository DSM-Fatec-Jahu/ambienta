<?php

namespace App\Services;

use App\Exceptions\ResourceUnavailableException;
use App\Models\ResourceModel;
use App\Models\RoomResourceModel;
use App\Models\ResourceMovementModel;

/**
 * ResourceAllocationService — centralizes logic for permanent resource allocation in rooms.
 *
 * Created in Sprint R2. Handles:
 *   - allocate()          : move resource from general stock to a room
 *   - deallocate()        : remove resource from a room back to general stock
 *   - futureBookingsForRoom() : check for future approved bookings (RN-R10)
 */
class ResourceAllocationService
{
    private ResourceModel         $resources;
    private RoomResourceModel     $roomResources;
    private ResourceMovementModel $movements;

    public function __construct()
    {
        $this->resources     = new ResourceModel();
        $this->roomResources = new RoomResourceModel();
        $this->movements     = new ResourceMovementModel();
    }

    /**
     * Allocates a resource to a room and records the movement.
     *
     * Validations:
     *   - Resource must exist and be active for the institution.
     *   - Resource must NOT already be allocated to a different room (requires deallocation first).
     *   - Quantity must be between 1 and resource.quantity_total.
     *
     * @return array{success: bool, error?: string}
     */
    public function allocate(
        int $institutionId,
        int $roomId,
        int $resourceId,
        int $quantity,
        int $handlerId
    ): array {
        $resource = $this->resources
            ->where('institution_id', $institutionId)
            ->where('is_active', 1)
            ->where('deleted_at IS NULL')
            ->find($resourceId);

        if (!$resource) {
            return ['success' => false, 'error' => 'Recurso não encontrado ou inativo.'];
        }

        // Check if already allocated to a DIFFERENT room (T-R02.02)
        $existingAllocation = db_connect()->table('room_resources')
            ->where('resource_id', $resourceId)
            ->where('room_id !=', $roomId)
            ->get()->getRowArray();

        if ($existingAllocation) {
            return ['success' => false, 'error' => 'Este recurso já está alocado em outro ambiente. Remova a alocação atual antes de realocar.'];
        }

        if ($quantity < 1 || $quantity > (int) $resource['quantity_total']) {
            return ['success' => false, 'error' => "Quantidade inválida. Máximo: {$resource['quantity_total']}."];
        }

        $this->roomResources->allocate($institutionId, $roomId, $resourceId, $quantity, $handlerId);

        $this->movements->recordRoomAllocation(
            $institutionId,
            $resourceId,
            $roomId,
            $quantity,
            $handlerId
        );

        return ['success' => true];
    }

    /**
     * Deallocates a resource from a room and records the movement.
     *
     * @param string|null $notes  Optional notes for the movement record.
     * @return array{success: bool, error?: string}
     */
    public function deallocate(
        int     $institutionId,
        int     $roomId,
        int     $resourceId,
        int     $handlerId,
        ?string $notes = null
    ): array {
        $allocation = $this->roomResources
            ->where('room_id', $roomId)
            ->where('resource_id', $resourceId)
            ->first();

        if (!$allocation) {
            return ['success' => false, 'error' => 'Alocação não encontrada.'];
        }

        $this->roomResources->deallocate($roomId, $resourceId);

        $this->movements->recordRoomDeallocation(
            $institutionId,
            $resourceId,
            $roomId,
            (int) $allocation['quantity'],
            $handlerId,
            $notes
        );

        return ['success' => true];
    }

    /**
     * Resolves and allocates general-stock resources for a booking slot (Sprint R8, RN-R14/R15/R16).
     *
     * Searches for general-stock resources matching $name (LIKE) OR $category (exact),
     * locks candidates with SELECT FOR UPDATE (RN-R16), allocates them FIFO by id (RN-R15),
     * and inserts rows in booking_resources.
     *
     * Errors of type ResourceUnavailableException do NOT roll back the booking itself —
     * the caller should flash a warning instead of aborting (RN-R04).
     *
     * @param int    $institutionId
     * @param string $name          Resource name (partial match). May be empty if $category set.
     * @param string $category      Resource category (exact match). May be empty if $name set.
     * @param int    $quantity      Units requested (>= 1).
     * @param string $date          Booking date (Y-m-d).
     * @param string $startTime     Booking start time (H:i or H:i:s).
     * @param string $endTime       Booking end time (H:i or H:i:s).
     * @param int    $bookingId     The booking that will own the allocated resources.
     *
     * @return int[]  Array of resource_ids that were allocated.
     *
     * @throws \InvalidArgumentException      If $quantity < 1 or both $name and $category are empty.
     * @throws ResourceUnavailableException   If not enough units are available for the slot.
     */
    public function resolve(
        int    $institutionId,
        string $name,
        string $category,
        int    $quantity,
        string $date,
        string $startTime,
        string $endTime,
        int    $bookingId
    ): array {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantidade deve ser maior que zero.');
        }
        if (trim($name) === '' && trim($category) === '') {
            throw new \InvalidArgumentException('Nome ou categoria do recurso devem ser informados.');
        }

        $db = db_connect();
        $db->transStart();

        // SELECT FOR UPDATE: lock matching general-stock resources with available units.
        // Subquery computes units already committed in overlapping bookings.
        $sql = "
            SELECT r.id, r.quantity_total,
                   COALESCE(booked.booked_qty, 0) AS booked_qty,
                   (r.quantity_total - COALESCE(booked.booked_qty, 0)) AS available_qty
            FROM resources r
            LEFT JOIN (
                SELECT br.resource_id, SUM(br.quantity) AS booked_qty
                FROM booking_resources br
                JOIN bookings bk ON bk.id = br.booking_id
                WHERE bk.date = ?
                  AND bk.status IN ('pending','approved')
                  AND bk.deleted_at IS NULL
                  AND bk.start_time < ?
                  AND bk.end_time > ?
                  AND br.status IN ('pending','approved')
                GROUP BY br.resource_id
            ) booked ON booked.resource_id = r.id
            WHERE r.institution_id = ?
              AND r.is_active = 1
              AND r.deleted_at IS NULL
              AND (r.category = ? OR r.name LIKE ?)
              AND r.id NOT IN (SELECT resource_id FROM room_resources)
              AND (r.quantity_total - COALESCE(booked.booked_qty, 0)) > 0
            ORDER BY r.id ASC
            FOR UPDATE
        ";

        $result     = $db->query($sql, [
            $date, $endTime, $startTime,
            $institutionId,
            $category,
            '%' . $name . '%',
        ]);
        $candidates = $result->getResultArray();

        // Check total available units across all candidates
        $totalAvailable = array_sum(array_column($candidates, 'available_qty'));

        if ((int) $totalAvailable < $quantity) {
            $db->transRollback();
            $label = $name !== '' ? $name : $category;
            service('audit')->log(
                'booking_resource.allocation_failed',
                'booking',
                $bookingId,
                null,
                ['name' => $name, 'category' => $category, 'quantity' => $quantity, 'available' => (int) $totalAvailable, 'reason' => 'insufficient_quantity']
            );
            throw new ResourceUnavailableException(
                "Quantidade insuficiente de '{$label}' disponível para o período."
            );
        }

        // Allocate FIFO (lowest id first — RN-R15)
        $remaining   = $quantity;
        $allocatedIds = [];

        foreach ($candidates as $candidate) {
            if ($remaining <= 0) {
                break;
            }

            $use       = min((int) $candidate['available_qty'], $remaining);
            $remaining -= $use;

            $db->table('booking_resources')->insert([
                'booking_id'  => $bookingId,
                'resource_id' => (int) $candidate['id'],
                'quantity'    => $use,
                'status'      => 'pending',
            ]);

            $allocatedIds[] = (int) $candidate['id'];
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            service('audit')->log(
                'booking_resource.allocation_failed',
                'booking',
                $bookingId,
                null,
                ['name' => $name, 'category' => $category, 'quantity' => $quantity, 'reason' => 'transaction_failed']
            );
            throw new ResourceUnavailableException('Falha ao alocar recursos. Tente novamente.');
        }

        service('audit')->log(
            'booking_resource.allocated_generic',
            'booking',
            $bookingId,
            null,
            ['name' => $name, 'category' => $category, 'quantity' => $quantity, 'resource_ids' => $allocatedIds]
        );

        return $allocatedIds;
    }

    /**
     * Returns approved bookings for a room that are in the future (or currently in progress).
     * Used by RN-R10 to warn the user before removing a permanent allocation.
     */
    public function futureBookingsForRoom(int $roomId): array
    {
        $today = date('Y-m-d');
        $time  = date('H:i:s');

        return db_connect()->table('bookings bk')
            ->select('bk.id, bk.title, bk.date, bk.start_time, bk.end_time, u.name AS requester_name')
            ->join('users u', 'u.id = bk.owner_id', 'left')
            ->where('bk.room_id', $roomId)
            ->where('bk.status', 'approved')
            ->where('bk.deleted_at IS NULL')
            ->groupStart()
                ->where('bk.date >', $today)
                ->orGroupStart()
                    ->where('bk.date', $today)
                    ->where('bk.end_time >', $time)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('bk.date ASC, bk.start_time ASC')
            ->limit(10)
            ->get()->getResultArray();
    }
}
