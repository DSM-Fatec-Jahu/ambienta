<?php

namespace App\Models;

use CodeIgniter\Model;

class EquipmentTransferModel extends Model
{
    protected $table      = 'equipment_transfers';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'institution_id',
        'equipment_id',
        'quantity',
        'origin_room_id',
        'destination_room_id',
        'handler_id',
        'notes',
        'transferred_at',
    ];

    protected $useTimestamps = false;

    /**
     * Returns transfer history for a given equipment item, with room names and handler name.
     */
    public function historyForEquipment(int $equipmentId): array
    {
        return $this->db->table('equipment_transfers et')
            ->select([
                'et.id',
                'et.quantity',
                'et.notes',
                'et.transferred_at',
                'orig.name  AS origin_room_name',
                'orig.code  AS origin_room_code',
                'dest.name  AS destination_room_name',
                'dest.code  AS destination_room_code',
                'u.name     AS handler_name',
            ])
            ->join('rooms orig', 'orig.id = et.origin_room_id',      'left')
            ->join('rooms dest', 'dest.id = et.destination_room_id', 'left')
            ->join('users u',    'u.id    = et.handler_id',            'left')
            ->where('et.equipment_id', $equipmentId)
            ->orderBy('et.transferred_at', 'DESC')
            ->get()->getResultArray();
    }
}
