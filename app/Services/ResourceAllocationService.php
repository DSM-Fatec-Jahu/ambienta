<?php

namespace App\Services;

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
