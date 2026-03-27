<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * @deprecated Use ResourceModel for new code. This class is kept for backward
 *             compatibility with existing controllers pending migration to ResourceModel.
 */
class EquipmentModel extends Model
{
    protected $table          = 'resources';  // renamed from 'equipment' in Sprint R1
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
        'name'           => 'required|max_length[200]',
        'institution_id' => 'required|integer',
        'quantity_total' => 'required|integer|greater_than[0]',
    ];

    public function activeForInstitution(int $institutionId): array
    {
        return $this->where('institution_id', $institutionId)
                    ->where('is_active', 1)
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }

    /**
     * Returns active equipment with available quantity for a given time slot.
     * Available = quantity_total minus sum already booked in overlapping bookings.
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

        $equipIds  = array_column($equipment, 'id');
        $bookedRows = $this->db->table('booking_resources be')
            ->select('be.resource_id, SUM(be.quantity) AS booked_qty')
            ->join('bookings b', 'b.id = be.booking_id')
            ->where('b.date', $date)
            ->whereIn('b.status', ['pending', 'approved'])
            ->where('b.deleted_at IS NULL')
            ->where('b.start_time <', $endTime)
            ->where('b.end_time >', $startTime)
            ->whereIn('be.resource_id', $equipIds)
            ->groupBy('be.resource_id')
            ->get()->getResultArray();

        $bookedMap = array_column($bookedRows, 'booked_qty', 'resource_id');

        foreach ($equipment as &$eq) {
            $booked              = (int) ($bookedMap[$eq['id']] ?? 0);
            $eq['available_qty'] = max(0, (int) $eq['quantity_total'] - $booked);
        }

        return $equipment;
    }
}
