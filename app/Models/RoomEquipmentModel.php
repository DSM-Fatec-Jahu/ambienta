<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * @deprecated Use RoomResourceModel for new code (Sprint R2).
 */
class RoomEquipmentModel extends Model
{
    protected $table      = 'room_resources';  // renamed from 'room_equipment' in Sprint R1
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

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Returns all equipment assigned to a room, joined with equipment names.
     */
    public function forRoom(int $roomId): array
    {
        return $this->db->table('room_resources re')
            ->select('re.id, re.resource_id AS equipment_id, re.resource_id, re.quantity, e.name, e.code, e.is_active')
            ->join('resources e', 'e.id = re.resource_id')
            ->where('re.room_id', $roomId)
            ->where('e.deleted_at IS NULL')
            ->orderBy('e.name', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Attaches equipment to a room or updates quantity if already attached.
     */
    public function attach(int $institutionId, int $roomId, int $equipmentId, int $quantity): void
    {
        $existing = $this->where('room_id', $roomId)->where('resource_id', $equipmentId)->first();

        if ($existing) {
            $this->update($existing['id'], ['quantity' => $quantity]);
        } else {
            $this->insert([
                'institution_id' => $institutionId,
                'room_id'        => $roomId,
                'resource_id'    => $equipmentId,
                'quantity'       => $quantity,
                'allocated_at'   => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Removes an equipment assignment from a room.
     */
    public function detach(int $roomId, int $equipmentId): void
    {
        $this->where('room_id', $roomId)->where('resource_id', $equipmentId)->delete();
    }
}
