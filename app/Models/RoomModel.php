<?php

namespace App\Models;

use CodeIgniter\Model;

class RoomModel extends Model
{
    protected $table          = 'rooms';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'institution_id',
        'building_id',
        'name',
        'code',
        'capacity',
        'floor',
        'description',
        'allows_equipment_lending',
        'image_path',
        'is_active',
        'maintenance_mode',
        'maintenance_until',
        'maintenance_reason',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name'           => 'required|max_length[200]',
        'institution_id' => 'required|integer',
        'capacity'       => 'permit_empty|integer|greater_than_equal_to[0]',
    ];

    /**
     * Returns rooms with building name joined.
     */
    public function withBuilding(int $institutionId): array
    {
        return $this->db->table('rooms r')
            ->select('r.*, b.name AS building_name')
            ->join('buildings b', 'b.id = r.building_id', 'left')
            ->where('r.institution_id', $institutionId)
            ->where('r.deleted_at IS NULL')
            ->orderBy('b.name, r.name')
            ->get()->getResultArray();
    }

    public function activeForInstitution(int $institutionId): array
    {
        return $this->db->table('rooms r')
            ->select('r.*, b.name AS building_name')
            ->join('buildings b', 'b.id = r.building_id', 'left')
            ->where('r.institution_id', $institutionId)
            ->where('r.deleted_at IS NULL')
            ->where('r.is_active', 1)
            ->orderBy('b.name, r.name')
            ->get()->getResultArray();
    }

    /**
     * Returns active rooms with no conflicting booking in the given slot.
     *
     * @param array  $equipmentIds  Legacy: filter by specific resource IDs (Admin/Técnico).
     * @param array  $resourceTerms New: filter by name/category strings (Solicitante — RN-R12).
     *                              Each term is matched via ResourceModel::roomIdsHavingResource().
     *                              Multiple terms = AND logic (room must satisfy all terms).
     */
    public function availableForSlot(
        int    $institutionId,
        string $date,
        string $startTime,
        string $endTime,
        array  $equipmentIds  = [],  // legacy — Admin/Técnico
        array  $resourceTerms = []   // new — Solicitante (name/category strings)
    ): array {
        $bookedIds = array_column(
            $this->db->table('bookings')
                ->select('room_id')
                ->where('date', $date)
                ->whereIn('status', ['pending', 'approved'])
                ->where('deleted_at IS NULL')
                ->where('start_time <', $endTime)
                ->where('end_time >', $startTime)
                ->get()->getResultArray(),
            'room_id'
        );

        $q = $this->db->table('rooms r')
            ->select('r.id, r.name, r.code, r.capacity, r.floor, r.description,
                      r.allows_equipment_lending, b.name AS building_name')
            ->join('buildings b', 'b.id = r.building_id', 'left')
            ->where('r.institution_id', $institutionId)
            ->where('r.is_active', 1)
            ->where('r.deleted_at IS NULL');

        if (!empty($bookedIds)) {
            $q->whereNotIn('r.id', $bookedIds);
        }

        // Legacy filter: restrict by specific resource IDs (Admin/Técnico)
        if (!empty($equipmentIds)) {
            foreach ($equipmentIds as $resourceId) {
                $q->whereIn('r.id', function ($b) use ($resourceId) {
                    $b->select('room_id')->from('room_resources')->where('resource_id', $resourceId);
                });
            }
        }

        // New filter: restrict by name/category terms (Solicitante — RN-R12)
        if (!empty($resourceTerms)) {
            $resourceModel  = new \App\Models\ResourceModel();
            $allowedRoomIds = null;

            foreach ($resourceTerms as $term) {
                $ids = $resourceModel->roomIdsHavingResource($institutionId, (string) $term);
                $allowedRoomIds = $allowedRoomIds === null
                    ? $ids
                    : array_values(array_intersect($allowedRoomIds, $ids));
            }

            if (empty($allowedRoomIds)) {
                return [];
            }

            $q->whereIn('r.id', $allowedRoomIds);
        }

        return $q->orderBy('b.name, r.name')->get()->getResultArray();
    }
}
