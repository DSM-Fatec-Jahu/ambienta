<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\RoomModel;

class DashboardController extends BaseController
{
    public function index(): string
    {
        $user          = $this->currentUser();
        $institutionId = $this->institution['id'] ?? 0;

        $bookingModel = new BookingModel();
        $roomModel    = new RoomModel();

        $stats        = $bookingModel->statsForUser((int) $user['id']);
        $pendingCount = 0;

        $isStaff = in_array($user['role'] ?? '', [
            'role_technician', 'role_coordinator',
            'role_vice_director', 'role_director', 'role_admin',
        ]);

        if ($isStaff) {
            $pendingCount = $bookingModel->countPendingForInstitution($institutionId);
        }

        $activeRooms = count($roomModel->activeForInstitution($institutionId));

        return view('dashboard/index', $this->viewData([
            'pageTitle'    => 'Dashboard',
            'stats'        => $stats,
            'pendingCount' => $pendingCount,
            'activeRooms'  => $activeRooms,
            'isStaff'      => $isStaff,
        ]));
    }
}
