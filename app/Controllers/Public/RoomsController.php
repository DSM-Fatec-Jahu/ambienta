<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;

class RoomsController extends BaseController
{
    public function index(): string
    {
        $institutionId = $this->institution['id'] ?? 0;
        $rooms = (new \App\Models\RoomModel())->activeForInstitution($institutionId);

        return view('public/rooms', $this->viewData([
            'pageTitle' => 'Ambientes',
            'rooms'     => $rooms,
        ]));
    }

    public function show(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $db = db_connect();

        $room = (new \App\Models\RoomModel())
            ->where('institution_id', $institutionId)
            ->where('is_active', 1)
            ->find($id);

        if (!$room) {
            return redirect()->to(base_url('ambientes'))->with('error', 'Ambiente não encontrado.');
        }

        $building = $db->table('buildings')->where('id', $room['building_id'])->get()->getRowArray();

        // Equipment frequently used with this room
        $equipment = $db->table('booking_equipment be')
            ->select('e.name, e.code, e.description')
            ->join('equipment e', 'e.id = be.equipment_id')
            ->join('bookings bk', 'bk.id = be.booking_id')
            ->where('bk.room_id', $id)
            ->where('bk.status', 'approved')
            ->where('bk.deleted_at IS NULL')
            ->where('e.deleted_at IS NULL')
            ->groupBy('e.id')
            ->orderBy('e.name', 'ASC')
            ->get()->getResultArray();

        return view('public/rooms_show', $this->viewData([
            'pageTitle' => $room['name'],
            'room'      => $room,
            'building'  => $building,
            'equipment' => $equipment,
        ]));
    }
}
