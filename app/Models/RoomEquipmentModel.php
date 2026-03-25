<?php

namespace App\Models;

use CodeIgniter\Model;

class RoomEquipmentModel extends Model
{
    protected $table      = 'room_equipment';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'institution_id',
        'room_id',
        'equipment_id',
        'quantity',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Returns all equipment assigned to a room, joined with equipment names.
     */
    public function forRoom(int $roomId): array
    {
        return $this->db->table('room_equipment re')
            ->select('re.id, re.equipment_id, re.quantity, e.name, e.code, e.is_active')
            ->join('equipment e', 'e.id = re.equipment_id')
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
        $existing = $this->where('room_id', $roomId)->where('equipment_id', $equipmentId)->first();

        if ($existing) {
            $this->update($existing['id'], ['quantity' => $quantity]);
        } else {
            $this->insert([
                'institution_id' => $institutionId,
                'room_id'        => $roomId,
                'equipment_id'   => $equipmentId,
                'quantity'       => $quantity,
            ]);
        }
    }

    /**
     * Removes an equipment assignment from a room.
     */
    public function detach(int $roomId, int $equipmentId): void
    {
        $this->where('room_id', $roomId)->where('equipment_id', $equipmentId)->delete();
    }
}
