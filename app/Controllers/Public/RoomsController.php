<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;

class RoomsController extends BaseController
{
    public function index(): string
    {
        $institutionId = $this->institution['id'] ?? 0;
        $resourceModel = new \App\Models\ResourceModel();
        $roomModel     = new \App\Models\RoomModel();

        // Text-based resource filter (RN-R12)
        $selectedTerms = array_values(array_filter(
            array_map('trim', (array) ($this->request->getGet('resource_terms') ?? []))
        ));

        if (!empty($selectedTerms)) {
            $rooms = $roomModel->availableForSlot(
                $institutionId,
                date('Y-m-d'),   // date not relevant for this listing — we just need the room filter
                '00:00', '23:59',
                [],
                $selectedTerms
            );
            // Re-fetch full room data (availableForSlot only returns available for a slot)
            // Use roomIdsHavingResource intersection instead
            $allowedIds = null;
            foreach ($selectedTerms as $term) {
                $ids        = $resourceModel->roomIdsHavingResource($institutionId, $term);
                $allowedIds = $allowedIds === null
                    ? $ids
                    : array_values(array_intersect($allowedIds, $ids));
            }
            if (empty($allowedIds)) {
                $rooms = [];
            } else {
                $rooms = $roomModel->activeForInstitution($institutionId);
                $rooms = array_values(array_filter($rooms, fn($r) => in_array((int)$r['id'], $allowedIds)));
            }
        } else {
            $rooms = $roomModel->activeForInstitution($institutionId);
        }

        $filterTerms = $resourceModel->getDistinctCategoriesAndNames($institutionId);

        return view('public/rooms', $this->viewData([
            'pageTitle'     => 'Ambientes',
            'rooms'         => $rooms,
            'filterTerms'   => $filterTerms,
            'selectedTerms' => $selectedTerms,
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

        // Grouped resources allocated to this room — no id/code exposed (RN-R13/C5)
        $groupedResources = (new \App\Models\ResourceModel())->getGroupedByRoom($id);

        return view('public/rooms_show', $this->viewData([
            'pageTitle'        => $room['name'],
            'room'             => $room,
            'building'         => $building,
            'groupedResources' => $groupedResources,
        ]));
    }
}
