<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ResourceMovementModel — tracks all movements of resources.
 *
 * Replaces the legacy EquipmentTransferModel for new code.
 * Movement types:
 *   room_allocation   — resource permanently allocated to a room
 *   room_deallocation — resource removed from permanent room allocation
 *   booking_checkout  — resource checked out via an approved booking
 *   booking_return    — return registered by requester
 *   return_confirmed  — return confirmed by technician
 *   return_rejected   — technician rejected the return claim
 */
class ResourceMovementModel extends Model
{
    protected $table      = 'resource_movements';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'institution_id',
        'resource_id',
        'movement_type',
        'quantity',
        'origin_room_id',
        'destination_room_id',
        'booking_id',
        'handler_id',
        'confirmed_by_id',
        'notes',
        'moved_at',
    ];

    protected $useTimestamps = false;

    // ── Movement type constants ───────────────────────────────────────────────

    public const TYPE_ROOM_ALLOCATION   = 'room_allocation';
    public const TYPE_ROOM_DEALLOCATION = 'room_deallocation';
    public const TYPE_BOOKING_CHECKOUT  = 'booking_checkout';
    public const TYPE_BOOKING_RETURN    = 'booking_return';
    public const TYPE_RETURN_CONFIRMED  = 'return_confirmed';
    public const TYPE_RETURN_REJECTED   = 'return_rejected';

    // ── History ───────────────────────────────────────────────────────────────

    /**
     * Returns the full movement history for a resource, with room names and user names.
     */
    public function historyForResource(int $resourceId): array
    {
        return $this->db->table('resource_movements rm')
            ->select([
                'rm.id',
                'rm.movement_type',
                'rm.quantity',
                'rm.notes',
                'rm.moved_at',
                'orig.name        AS origin_room_name',
                'orig.abbreviation AS origin_room_abbr',
                'dest.name        AS destination_room_name',
                'dest.abbreviation AS destination_room_abbr',
                'u.name           AS handler_name',
                'cu.name          AS confirmed_by_name',
                'bk.id            AS booking_ref',
            ])
            ->join('rooms orig', 'orig.id = rm.origin_room_id',        'left')
            ->join('rooms dest', 'dest.id = rm.destination_room_id',   'left')
            ->join('users u',    'u.id    = rm.handler_id',             'left')
            ->join('users cu',   'cu.id   = rm.confirmed_by_id',        'left')
            ->join('bookings bk','bk.id   = rm.booking_id',             'left')
            ->where('rm.resource_id', $resourceId)
            ->orderBy('rm.moved_at', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Records a room allocation movement.
     */
    public function recordRoomAllocation(
        int    $institutionId,
        int    $resourceId,
        ?int   $destinationRoomId,
        int    $quantity,
        int    $handlerId,
        string $notes = null
    ): void {
        $this->insert([
            'institution_id'      => $institutionId,
            'resource_id'         => $resourceId,
            'movement_type'       => self::TYPE_ROOM_ALLOCATION,
            'quantity'            => $quantity,
            'origin_room_id'      => null,
            'destination_room_id' => $destinationRoomId,
            'handler_id'          => $handlerId,
            'notes'               => $notes,
            'moved_at'            => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Records a room deallocation movement.
     */
    public function recordRoomDeallocation(
        int    $institutionId,
        int    $resourceId,
        ?int   $originRoomId,
        int    $quantity,
        int    $handlerId,
        string $notes = null
    ): void {
        $this->insert([
            'institution_id'      => $institutionId,
            'resource_id'         => $resourceId,
            'movement_type'       => self::TYPE_ROOM_DEALLOCATION,
            'quantity'            => $quantity,
            'origin_room_id'      => $originRoomId,
            'destination_room_id' => null,
            'handler_id'          => $handlerId,
            'notes'               => $notes,
            'moved_at'            => date('Y-m-d H:i:s'),
        ]);
    }
}
