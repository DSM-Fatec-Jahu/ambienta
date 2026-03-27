<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

/**
 * Returns FullCalendar-compatible JSON events from approved bookings.
 * Public endpoint — no auth required (shows only approved bookings, no PII).
 *
 * GET /api/agenda/events
 *   ?start=        ISO 8601 range start (set by FullCalendar)
 *   ?end=          ISO 8601 range end
 *   ?building_id=  optional filter
 *   ?room_id=      optional filter
 *   ?equipment_name= optional keyword filter
 *   ?patrimony_code= optional patrimony/code filter
 */
class AgendaController extends BaseController
{
    public function events(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;

        $start         = $this->request->getGet('start')          ?? date('Y-m-01');
        $end           = $this->request->getGet('end')            ?? date('Y-m-t');
        $buildingId    = (int) ($this->request->getGet('building_id')    ?? 0);
        $roomId        = (int) ($this->request->getGet('room_id')        ?? 0);
        $equipName     = trim($this->request->getGet('equipment_name')   ?? '');
        $patrimonyCode = trim($this->request->getGet('patrimony_code')   ?? '');

        // Normalise dates — FullCalendar sends ISO 8601 with timezone
        $startDate = substr($start, 0, 10);
        $endDate   = substr($end,   0, 10);

        $db = db_connect();

        $query = $db->table('bookings bk')
            ->select('bk.id, bk.title, bk.date, bk.start_time, bk.end_time,
                      bk.attendees_count, bk.description,
                      r.id AS room_id, r.name AS room_name, r.code AS room_code,
                      b.name AS building_name')
            ->join('rooms r',     'r.id = bk.room_id',     'left')
            ->join('buildings b', 'b.id = r.building_id',  'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.status', 'approved')
            ->where('bk.deleted_at IS NULL')
            ->where('bk.date >=', $startDate)
            ->where('bk.date <',  $endDate);

        if ($buildingId) {
            $query->where('r.building_id', $buildingId);
        }

        if ($roomId) {
            $query->where('bk.room_id', $roomId);
        }

        if ($equipName || $patrimonyCode) {
            // Filter by equipment associated with the booking
            $subQuery = $db->table('booking_resources be')
                ->select('be.booking_id')
                ->join('resources e', 'e.id = be.resource_id', 'inner');

            if ($equipName) {
                $subQuery->like('e.name', $equipName);
            }
            if ($patrimonyCode) {
                $subQuery->like('e.code', $patrimonyCode);
            }

            $bookingIds = array_column($subQuery->get()->getResultArray(), 'booking_id');

            if (empty($bookingIds)) {
                return $this->response->setJSON([]);
            }
            $query->whereIn('bk.id', $bookingIds);
        }

        $rows = $query->orderBy('bk.date, bk.start_time')->get()->getResultArray();

        // Map to FullCalendar event objects
        $statusColors = [
            'approved' => '#1A8C5B',
        ];

        $events = array_map(function (array $row) use ($statusColors): array {
            $roomLabel = $row['room_name'] ?? 'Sem ambiente';
            if (!empty($row['building_name'])) {
                $roomLabel .= ' — ' . $row['building_name'];
            }

            return [
                'id'    => $row['id'],
                'title' => $roomLabel,
                'start' => $row['date'] . 'T' . $row['start_time'],
                'end'   => $row['date'] . 'T' . $row['end_time'],
                'color' => $statusColors['approved'],
                'extendedProps' => [
                    'room_name'       => $row['room_name'] ?? '—',
                    'room_code'       => $row['room_code'] ?? null,
                    'building_name'   => $row['building_name'] ?? null,
                    'purpose'         => $row['title'],
                    'description'     => $row['description'],
                    'attendees_count' => $row['attendees_count'],
                ],
            ];
        }, $rows);

        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setJSON($events);
    }

    /**
     * Returns events for the authenticated user's agenda.
     * Shows own bookings (all statuses) + approved bookings from same institution.
     * GET /api/reservas/agenda-events  (auth required)
     *   ?start=   ?end=   ?room_id=   ?building_id=
     */
    public function userEvents(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $user          = $this->currentUser();
        $userId        = (int) ($user['id'] ?? 0);

        $start     = $this->request->getGet('start') ?? date('Y-m-01');
        $end       = $this->request->getGet('end')   ?? date('Y-m-t');
        $buildingId = (int) ($this->request->getGet('building_id') ?? 0);
        $roomId     = (int) ($this->request->getGet('room_id')     ?? 0);

        $startDate = substr($start, 0, 10);
        $endDate   = substr($end,   0, 10);

        $db = db_connect();

        $base = $db->table('bookings bk')
            ->select('bk.id, bk.title, bk.date, bk.start_time, bk.end_time, bk.status, bk.owner_id,
                      r.name AS room_name, r.code AS room_code, b.name AS building_name')
            ->join('rooms r',     'r.id = bk.room_id',    'left')
            ->join('buildings b', 'b.id = r.building_id', 'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.deleted_at IS NULL')
            ->where('bk.date >=', $startDate)
            ->where('bk.date <',  $endDate)
            ->groupStart()
                // Own bookings (any status) OR others' approved bookings
                ->where('bk.owner_id', $userId)
                ->orGroupStart()
                    ->where('bk.owner_id !=', $userId)
                    ->where('bk.status', 'approved')
                ->groupEnd()
            ->groupEnd();

        if ($buildingId) {
            $base->where('r.building_id', $buildingId);
        }
        if ($roomId) {
            $base->where('bk.room_id', $roomId);
        }

        $rows = $base->orderBy('bk.date, bk.start_time')->get()->getResultArray();

        $statusColors = [
            'pending'   => '#D97706',
            'approved'  => '#1A8C5B',
            'rejected'  => '#C0392B',
            'cancelled' => '#64748B',
            'absent'    => '#7C3AED',
        ];

        $events = array_map(function (array $row) use ($userId, $statusColors): array {
            $isOwn = (int) $row['owner_id'] === $userId;
            $color = $isOwn
                ? ($statusColors[$row['status']] ?? '#64748B')
                : '#1A8C5B';

            $label = $row['room_name'] ?? 'Sem ambiente';
            if (!empty($row['building_name'])) {
                $label .= ' — ' . $row['building_name'];
            }

            return [
                'id'    => $row['id'],
                'title' => $isOwn ? $row['title'] : $label,
                'start' => $row['date'] . 'T' . $row['start_time'],
                'end'   => $row['date'] . 'T' . $row['end_time'],
                'color' => $color,
                'extendedProps' => [
                    'status'        => $row['status'],
                    'room_name'     => $row['room_name'] ?? '—',
                    'building_name' => $row['building_name'] ?? null,
                    'is_own'        => $isOwn,
                ],
            ];
        }, $rows);

        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setJSON($events);
    }

    /**
     * Returns buildings + rooms lists for the agenda filter dropdowns.
     * GET /api/agenda/filters
     */
    public function filters(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $db = db_connect();

        $buildings = $db->table('buildings')
            ->select('id, name, code')
            ->where('institution_id', $institutionId)
            ->where('is_active', 1)
            ->where('deleted_at IS NULL')
            ->orderBy('name')
            ->get()->getResultArray();

        $rooms = $db->table('rooms r')
            ->select('r.id, r.name, r.code, r.building_id, b.name AS building_name')
            ->join('buildings b', 'b.id = r.building_id', 'left')
            ->where('r.institution_id', $institutionId)
            ->where('r.is_active', 1)
            ->where('r.deleted_at IS NULL')
            ->orderBy('b.name, r.name')
            ->get()->getResultArray();

        return $this->response->setJSON([
            'buildings' => $buildings,
            'rooms'     => $rooms,
        ]);
    }
}
