<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * @deprecated Use ResourceMovementModel for new code.
 */
class EquipmentTransferModel extends Model
{
    protected $table      = 'resource_movements';  // renamed from 'equipment_transfers' in Sprint R1
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

    /**
     * Returns transfer history for a given equipment item, with room names and handler name.
     */
    public function historyForEquipment(int $equipmentId): array
    {
        return $this->db->table('resource_movements rm')
            ->select([
                'rm.id',
                'rm.movement_type',
                'rm.quantity',
                'rm.notes',
                'rm.moved_at AS transferred_at',
                'orig.name         AS origin_room_name',
                'orig.abbreviation AS origin_room_code',
                'dest.name         AS destination_room_name',
                'dest.abbreviation AS destination_room_code',
                'u.name            AS handler_name',
            ])
            ->join('rooms orig', 'orig.id = rm.origin_room_id',       'left')
            ->join('rooms dest', 'dest.id = rm.destination_room_id',  'left')
            ->join('users u',    'u.id    = rm.handler_id',            'left')
            ->where('rm.resource_id', $equipmentId)
            ->orderBy('rm.moved_at', 'DESC')
            ->get()->getResultArray();
    }
}
