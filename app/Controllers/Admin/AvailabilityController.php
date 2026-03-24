<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class AvailabilityController extends BaseController
{
    /**
     * Daily availability grid showing all rooms × time slots for a given date.
     * GET /admin/disponibilidade
     */
    public function index(): string
    {
        $institutionId = $this->institution['id'] ?? 0;
        $db            = db_connect();

        $date = $this->request->getGet('date') ?? date('Y-m-d');

        // Validate date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        // All active rooms for the institution
        $rooms = $db->table('rooms r')
            ->select('r.id, r.name, r.code, r.capacity, b.name AS building_name')
            ->join('buildings b', 'b.id = r.building_id', 'left')
            ->where('r.institution_id', $institutionId)
            ->where('r.is_active', 1)
            ->where('r.deleted_at IS NULL')
            ->orderBy('b.name ASC, r.name ASC')
            ->get()->getResultArray();

        // All bookings for the selected date
        $bookings = $db->table('bookings bk')
            ->select('bk.id, bk.room_id, bk.title, bk.start_time, bk.end_time,
                      bk.status, bk.attendees_count, u.name AS user_name')
            ->join('users u', 'u.id = bk.user_id', 'left')
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
        $slots    = [];
        $startH   = (int) explode(':', $dayOpen)[0];
        $endH     = (int) explode(':', $dayClose)[0];
        for ($h = $startH; $h < $endH; $h++) {
            $slots[] = sprintf('%02d:00', $h);
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
        ]));
    }
}
