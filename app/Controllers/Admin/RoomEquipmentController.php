<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\RoomEquipmentModel;
use App\Models\RoomModel;
use App\Models\EquipmentModel;

class RoomEquipmentController extends BaseController
{
    private RoomEquipmentModel $roomEquipment;
    private RoomModel          $rooms;
    private EquipmentModel     $equipment;

    public function __construct()
    {
        $this->roomEquipment = new RoomEquipmentModel();
        $this->rooms         = new RoomModel();
        $this->equipment     = new EquipmentModel();
    }

    /**
     * GET /admin/ambientes/:id/equipamentos
     * Returns JSON list of equipment assigned to the room.
     */
    public function index(int $roomId): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $room          = $this->rooms->where('institution_id', $institutionId)->find($roomId);

        if (!$room) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Ambiente não encontrado.']);
        }

        return $this->response->setJSON([
            'room_name' => $room['name'],
            'items'     => $this->roomEquipment->forRoom($roomId),
        ]);
    }

    /**
     * POST /admin/ambientes/:id/equipamentos
     * Attaches (or updates qty of) an equipment to the room.
     */
    public function store(int $roomId): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $room          = $this->rooms->where('institution_id', $institutionId)->find($roomId);

        if (!$room) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Ambiente não encontrado.']);
        }

        $equipmentId = (int) $this->request->getPost('equipment_id');
        $quantity    = max(1, (int) $this->request->getPost('quantity'));

        $equip = $this->equipment->where('institution_id', $institutionId)->find($equipmentId);
        if (!$equip) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Equipamento não encontrado.']);
        }

        $this->roomEquipment->attach($institutionId, $roomId, $equipmentId, $quantity);

        return $this->response->setJSON(['message' => 'Equipamento vinculado ao ambiente.']);
    }

    /**
     * POST /admin/ambientes/:roomId/equipamentos/:equipId/delete
     * Removes equipment association from room.
     */
    public function destroy(int $roomId, int $equipmentId): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $room          = $this->rooms->where('institution_id', $institutionId)->find($roomId);

        if (!$room) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Ambiente não encontrado.']);
        }

        $this->roomEquipment->detach($roomId, $equipmentId);

        return $this->response->setJSON(['message' => 'Equipamento removido do ambiente.']);
    }
}
