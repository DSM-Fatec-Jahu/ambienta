<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EquipmentModel;
use App\Models\EquipmentTransferModel;
use App\Models\RoomModel;

class EquipmentController extends BaseController
{
    private EquipmentModel         $equipment;
    private EquipmentTransferModel $transfers;
    private RoomModel              $rooms;

    public function __construct()
    {
        $this->equipment = new EquipmentModel();
        $this->transfers = new EquipmentTransferModel();
        $this->rooms     = new RoomModel();
    }

    public function index(): string
    {
        $institutionId = $this->institution['id'] ?? 0;

        $items = $this->equipment
            ->where('institution_id', $institutionId)
            ->orderBy('name', 'ASC')
            ->findAll();

        $rooms = $this->rooms->activeForInstitution($institutionId);

        return view('admin/equipment/index', $this->viewData([
            'pageTitle' => 'Equipamentos',
            'items'     => $items,
            'rooms'     => $rooms,
        ]));
    }

    public function store(): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;

        $rules = [
            'name'           => 'required|max_length[200]',
            'quantity_total' => 'required|integer|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->equipment->insert([
            'institution_id' => $institutionId,
            'name'           => $this->request->getPost('name'),
            'code'           => $this->request->getPost('code') ?: null,
            'description'    => $this->request->getPost('description') ?: null,
            'quantity_total' => (int) $this->request->getPost('quantity_total'),
            'is_active'      => (int) (bool) $this->request->getPost('is_active'),
        ]);

        return redirect()->to(base_url('admin/equipamentos'))
            ->with('success', 'Equipamento cadastrado com sucesso.');
    }

    public function update(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $item          = $this->equipment->where('institution_id', $institutionId)->find($id);

        if (!$item) {
            return redirect()->to(base_url('admin/equipamentos'))->with('error', 'Equipamento não encontrado.');
        }

        $rules = [
            'name'           => 'required|max_length[200]',
            'quantity_total' => 'required|integer|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->equipment->update($id, [
            'name'           => $this->request->getPost('name'),
            'code'           => $this->request->getPost('code') ?: null,
            'description'    => $this->request->getPost('description') ?: null,
            'quantity_total' => (int) $this->request->getPost('quantity_total'),
            'is_active'      => (int) (bool) $this->request->getPost('is_active'),
        ]);

        return redirect()->to(base_url('admin/equipamentos'))
            ->with('success', 'Equipamento atualizado com sucesso.');
    }

    public function delete(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $item          = $this->equipment->where('institution_id', $institutionId)->find($id);

        if (!$item) {
            return redirect()->to(base_url('admin/equipamentos'))->with('error', 'Equipamento não encontrado.');
        }

        $this->equipment->delete($id);

        return redirect()->to(base_url('admin/equipamentos'))
            ->with('success', 'Equipamento excluído.');
    }

    // ── Equipment Transfers ───────────────────────────────────────────────────

    /**
     * POST /admin/equipamentos/:id/transferir
     * Records a physical movement of equipment between rooms.
     */
    public function transfer(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $user          = $this->currentUser();

        $item = $this->equipment->where('institution_id', $institutionId)->find($id);
        if (!$item) {
            return redirect()->to(base_url('admin/equipamentos'))->with('error', 'Equipamento não encontrado.');
        }

        $rules = [
            'quantity' => 'required|integer|greater_than[0]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $qty    = (int) $this->request->getPost('quantity');
        $fromId = (int) ($this->request->getPost('origin_room_id') ?: 0) ?: null;
        $toId   = (int) ($this->request->getPost('destination_room_id') ?: 0) ?: null;

        if (!$fromId && !$toId) {
            return redirect()->to(base_url('admin/equipamentos'))
                ->with('error', 'Informe ao menos a sala de origem ou a sala de destino da movimentação.');
        }

        $this->transfers->insert([
            'institution_id'      => $institutionId,
            'equipment_id'        => $id,
            'quantity'            => $qty,
            'origin_room_id'      => $fromId,
            'destination_room_id' => $toId,
            'handler_id'          => (int) $user['id'],
            'notes'               => $this->request->getPost('notes') ?: null,
            'transferred_at'      => date('Y-m-d H:i:s'),
        ]);

        service('audit')->log('equipment.transferred', 'equipment', $id, null, [
            'quantity'            => $qty,
            'origin_room_id'      => $fromId,
            'destination_room_id' => $toId,
        ]);

        return redirect()->to(base_url('admin/equipamentos'))
            ->with('success', 'Movimentação registrada com sucesso.');
    }

    /**
     * GET /admin/equipamentos/:id/historico
     * Returns JSON transfer history for a given equipment item.
     */
    public function history(int $id): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $item          = $this->equipment->where('institution_id', $institutionId)->find($id);

        if (!$item) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Equipamento não encontrado.']);
        }

        $history = $this->transfers->historyForEquipment($id);

        return $this->response->setJSON([
            'equipment_name' => $item['name'],
            'history'        => $history,
        ]);
    }
}
