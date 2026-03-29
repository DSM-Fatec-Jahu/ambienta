<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ResourceModel;

class AvailabilityController extends BaseController
{
    /**
     * Daily availability grid showing all rooms × time slots for a given date.
     * GET /admin/disponibilidade
     *
     * RN-R17: Dual filter — text terms for Solicitante, text OR ID toggle for Admin/Técnico.
     */
    public function index(): string
    {
        $institutionId = $this->institution['id'] ?? 0;
        $db            = db_connect();
        $resourceModel = new ResourceModel();

        $user        = $this->currentUser();
        $isRequester = ($user['role'] === 'role_requester');

        $date = $this->request->getGet('date') ?? date('Y-m-d');

        // Validate date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $buildingId = (int) ($this->request->getGet('building_id') ?? 0);

        // Text-based filter (all roles)
        $resourceTerms = array_values(array_filter(
            array_map('trim', (array) ($this->request->getGet('resource_terms') ?? []))
        ));

        // ID-based filter (Admin/Técnico only — RN-R17)
        $resourceIds = [];
        if (!$isRequester) {
            $resourceIds = array_values(array_filter(
                array_map('intval', (array) ($this->request->getGet('resource_ids') ?? []))
            ));
        }

        // Buildings for filter dropdown
        $buildings = $db->table('buildings')
            ->select('id, name')
            ->where('institution_id', $institutionId)
            ->where('deleted_at IS NULL')
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        // All active rooms for the institution
        $roomBuilder = $db->table('rooms r')
            ->select('r.id, r.name, r.code, r.capacity, b.name AS building_name, r.building_id')
            ->join('buildings b', 'b.id = r.building_id', 'left')
            ->where('r.institution_id', $institutionId)
            ->where('r.is_active', 1)
            ->where('r.deleted_at IS NULL');

        if ($buildingId > 0) {
            $roomBuilder->where('r.building_id', $buildingId);
        }

        // Apply text-based resource filter (RN-R12)
        if (!empty($resourceTerms)) {
            $allowedRoomIds = null;
            foreach ($resourceTerms as $term) {
                $ids            = $resourceModel->roomIdsHavingResource($institutionId, $term);
                $allowedRoomIds = $allowedRoomIds === null
                    ? $ids
                    : array_values(array_intersect($allowedRoomIds, $ids));
            }
            if (empty($allowedRoomIds)) {
                $allowedRoomIds = [0]; // force empty result
            }
            $roomBuilder->whereIn('r.id', $allowedRoomIds);
        }

        // Apply ID-based resource filter (Admin/Técnico only)
        if (!empty($resourceIds)) {
            foreach ($resourceIds as $resId) {
                $roomBuilder->where(
                    "EXISTS (SELECT 1 FROM room_resources rr WHERE rr.room_id = r.id AND rr.resource_id = ?)",
                    $resId
                );
            }
        }

        $rooms = $roomBuilder->orderBy('b.name ASC, r.name ASC')->get()->getResultArray();

        // All bookings for the selected date
        $bookings = $db->table('bookings bk')
            ->select('bk.id, bk.room_id, bk.title, bk.start_time, bk.end_time,
                      bk.status, bk.attendees_count, u.name AS user_name')
            ->join('users u', 'u.id = bk.owner_id', 'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.date', $date)
            ->where('bk.deleted_at IS NULL')
            ->whereIn('bk.status', ['pending', 'approved'])
            ->orderBy('bk.start_time ASC')
            ->get()->getResultArray();

        // Index bookings by room_id
        $bookingsByRoom = [];
        foreach ($bookings as $b) {
            $bookingsByRoom[(int) $b['room_id']][] = $b;
        }

        // Operating hours for the selected day
        $dayOfWeek = (int) date('w', strtotime($date));
        $oh = $db->table('operating_hours')
            ->where('institution_id', $institutionId)
            ->where('day_of_week', $dayOfWeek)
            ->get()->getRowArray();

        $dayOpen  = ($oh && $oh['is_open']) ? substr($oh['open_time'] ?? '07:00', 0, 5)  : '07:00';
        $dayClose = ($oh && $oh['is_open']) ? substr($oh['close_time'] ?? '22:00', 0, 5) : '22:00';
        $isClosed = $oh && !$oh['is_open'];

        // Build hour slots array (full hours between dayOpen and dayClose)
        $slots  = [];
        $startH = (int) explode(':', $dayOpen)[0];
        $endH   = (int) explode(':', $dayClose)[0];
        for ($h = $startH; $h < $endH; $h++) {
            $slots[] = sprintf('%02d:00', $h);
        }

        $allResources = $isRequester ? [] : $resourceModel->activeForInstitution($institutionId);

        // E1: Audit when text-based resource filter is applied (RN-R17)
        if (!empty($resourceTerms)) {
            service('audit')->log(
                'availability.searched_by_term',
                'availability',
                null,
                null,
                ['date' => $date, 'terms' => $resourceTerms, 'building_id' => $buildingId ?: null]
            );
        }

        return view('admin/availability/index', $this->viewData([
            'pageTitle'      => 'Disponibilidade Diária',
            'date'           => $date,
            'rooms'          => $rooms,
            'bookingsByRoom' => $bookingsByRoom,
            'slots'          => $slots,
            'dayOpen'        => $dayOpen,
            'dayClose'       => $dayClose,
            'isClosed'       => $isClosed,
            'totalBookings'  => count($bookings),
            'buildings'      => $buildings,
            'buildingId'     => $buildingId,
            // New R8-B variables (used by R8-D view)
            'isRequester'    => $isRequester,
            'resourceTerms'  => $resourceTerms,
            'resourceIds'    => $isRequester ? [] : $resourceIds,
            'distinctTerms'  => $resourceModel->getDistinctCategoriesAndNames($institutionId),
            'allResources'   => $allResources,
            // Legacy variables kept for current view until R8-D is applied
            'equipmentList'  => $allResources,
            'equipmentIds'   => $isRequester ? [] : $resourceIds,
        ]));
    }
}
