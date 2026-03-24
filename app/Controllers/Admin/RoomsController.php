<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BuildingModel;
use App\Models\RoomModel;

class RoomsController extends BaseController
{
    private RoomModel     $rooms;
    private BuildingModel $buildings;

    public function __construct()
    {
        $this->rooms     = new RoomModel();
        $this->buildings = new BuildingModel();
    }

    public function index(): string
    {
        $institutionId = $this->institution['id'] ?? 0;

        $items     = $this->rooms->withBuilding($institutionId);
        $buildings = $this->buildings->activeForInstitution($institutionId);

        return view('admin/rooms/index', $this->viewData([
            'pageTitle' => 'Ambientes',
            'items'     => $items,
            'buildings' => $buildings,
        ]));
    }

    public function store(): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;

        $rules = [
            'name'     => 'required|max_length[200]',
            'capacity' => 'permit_empty|integer|greater_than_equal_to[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->rooms->insert([
            'institution_id'           => $institutionId,
            'building_id'              => $this->request->getPost('building_id') ?: null,
            'name'                     => $this->request->getPost('name'),
            'code'                     => $this->request->getPost('code') ?: null,
            'capacity'                 => (int) $this->request->getPost('capacity') ?: 0,
            'floor'                    => $this->request->getPost('floor') ?: null,
            'description'              => $this->request->getPost('description') ?: null,
            'allows_equipment_lending' => (int) (bool) $this->request->getPost('allows_equipment_lending'),
            'is_active'                => (int) (bool) $this->request->getPost('is_active'),
        ]);

        return redirect()->to(base_url('admin/ambientes'))
            ->with('success', 'Ambiente cadastrado com sucesso.');
    }

    public function update(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $room          = $this->rooms->where('institution_id', $institutionId)->find($id);

        if (!$room) {
            return redirect()->to(base_url('admin/ambientes'))->with('error', 'Ambiente não encontrado.');
        }

        $rules = [
            'name'     => 'required|max_length[200]',
            'capacity' => 'permit_empty|integer|greater_than_equal_to[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->rooms->update($id, [
            'building_id'              => $this->request->getPost('building_id') ?: null,
            'name'                     => $this->request->getPost('name'),
            'code'                     => $this->request->getPost('code') ?: null,
            'capacity'                 => (int) $this->request->getPost('capacity') ?: 0,
            'floor'                    => $this->request->getPost('floor') ?: null,
            'description'              => $this->request->getPost('description') ?: null,
            'allows_equipment_lending' => (int) (bool) $this->request->getPost('allows_equipment_lending'),
            'is_active'                => (int) (bool) $this->request->getPost('is_active'),
        ]);

        return redirect()->to(base_url('admin/ambientes'))
            ->with('success', 'Ambiente atualizado com sucesso.');
    }

    public function delete(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $room          = $this->rooms->where('institution_id', $institutionId)->find($id);

        if (!$room) {
            return redirect()->to(base_url('admin/ambientes'))->with('error', 'Ambiente não encontrado.');
        }

        // Check pending/approved bookings
        $activeBookings = db_connect()->table('bookings')
            ->where('room_id', $id)
            ->whereIn('status', ['pending', 'approved'])
            ->where('deleted_at IS NULL')
            ->countAllResults();

        if ($activeBookings > 0) {
            return redirect()->to(base_url('admin/ambientes'))
                ->with('error', "Não é possível excluir: existem {$activeBookings} reserva(s) ativa(s) para este ambiente.");
        }

        $this->rooms->delete($id);

        return redirect()->to(base_url('admin/ambientes'))
            ->with('success', 'Ambiente excluído.');
    }
}
