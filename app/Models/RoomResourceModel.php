<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * RoomResourceModel — manages permanent resource allocations in rooms.
 *
 * Replaces RoomEquipmentModel for new code (Sprint R2).
 * Table: room_resources
 */
class RoomResourceModel extends Model
{
    protected $table      = 'room_resources';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'institution_id',
        'room_id',
        'resource_id',
        'quantity',
        'allocated_by_id',
        'allocated_at',
    ];

    protected $useTimestamps = false;

    // ── Queries ──────────────────────────────────────────────────────────────

    /**
     * Returns all resources allocated to a specific room, joined with resource and user data.
     */
    public function forRoom(int $roomId): array
    {
        return $this->db->table('room_resources rr')
            ->select([
                'rr.id',
                'rr.resource_id',
                'rr.quantity',
                'rr.allocated_by_id',
                'rr.allocated_at',
                'r.name',
                'r.code',
                'r.is_active',
                'r.quantity_total',
                'u.name AS allocated_by_name',
            ])
            ->join('resources r', 'r.id = rr.resource_id')
            ->join('users u',     'u.id = rr.allocated_by_id', 'left')
            ->where('rr.room_id', $roomId)
            ->where('r.deleted_at IS NULL')
            ->orderBy('r.name', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Checks whether a resource is currently allocated to any room.
     */
    public function isAllocated(int $resourceId): bool
    {
        return $this->where('resource_id', $resourceId)->countAllResults() > 0;
    }

    /**
     * Checks whether a resource is allocated to a specific room.
     */
    public function isAllocatedToRoom(int $resourceId, int $roomId): bool
    {
        return $this->where('resource_id', $resourceId)
                    ->where('room_id', $roomId)
                    ->countAllResults() > 0;
    }

    // ── Mutations ────────────────────────────────────────────────────────────

    /**
     * Allocates a resource to a room (upsert — updates quantity/handler if already linked).
     */
    public function allocate(
        int $institutionId,
        int $roomId,
        int $resourceId,
        int $quantity,
        int $allocatedById
    ): void {
        $existing = $this->where('room_id', $roomId)
                         ->where('resource_id', $resourceId)
                         ->first();

        if ($existing) {
            $this->update($existing['id'], [
                'quantity'        => $quantity,
                'allocated_by_id' => $allocatedById,
                'allocated_at'    => date('Y-m-d H:i:s'),
            ]);
        } else {
            $this->insert([
                'institution_id'  => $institutionId,
                'room_id'         => $roomId,
                'resource_id'     => $resourceId,
                'quantity'        => $quantity,
                'allocated_by_id' => $allocatedById,
                'allocated_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Removes a resource allocation from a room.
     */
    public function deallocate(int $roomId, int $resourceId): void
    {
        $this->where('room_id', $roomId)
             ->where('resource_id', $resourceId)
             ->delete();
    }
}
