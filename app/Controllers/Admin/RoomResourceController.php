<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ResourceModel;
use App\Models\RoomModel;
use App\Services\ResourceAllocationService;

/**
 * RoomResourceController — manages permanent resource allocations in rooms.
 *
 * Routes: /admin/ambientes/:id/recursos
 * Replaces RoomEquipmentController for the /recursos routes (Sprint R2).
 *
 * All responses are JSON (modal is AJAX-driven in the rooms/index.php view).
 */
class RoomResourceController extends BaseController
{
    private RoomModel                 $rooms;
    private ResourceModel             $resources;
    private ResourceAllocationService $service;

    public function __construct()
    {
        $this->rooms     = new RoomModel();
        $this->resources = new ResourceModel();
        $this->service   = new ResourceAllocationService();
    }

    /**
     * GET /admin/ambientes/:id/recursos
     *
     * Returns JSON:
     *   room_name       — room display name
     *   items           — resources currently allocated to this room (with user/date info)
     *   available_stock — resources in general stock (not allocated to any room)
     */
    public function index(int $roomId): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $room          = $this->rooms->where('institution_id', $institutionId)->find($roomId);

        if (!$room) {
            return $this->response->setStatusCode(404)
                ->setJSON(['error' => 'Ambiente não encontrado.']);
        }

        $items = $this->resources->allocatedToRoom($roomId);

        return $this->response->setJSON([
            'room_name'       => $room['name'],
            'items'           => $items,
            'available_stock' => [], // loaded lazily via /disponivel
        ]);
    }

    /**
     * GET /admin/ambientes/:id/recursos/disponivel
     *
     * Paginated search over resources in general stock (not allocated to any room).
     * Used by the lazy searchable combobox in the resources modal.
     *
     * Query params: q (search), page (1-based)
     */
    public function available(int $roomId): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $room          = $this->rooms->where('institution_id', $institutionId)->find($roomId);

        if (!$room) {
            return $this->response->setStatusCode(404)
                ->setJSON(['error' => 'Ambiente não encontrado.']);
        }

        $q      = trim($this->request->getGet('q')    ?? '');
        $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit  = 10;
        $offset = ($page - 1) * $limit;

        $rows  = $this->resources->inGeneralStockSearch($institutionId, $q, $limit, $offset);
        $total = $this->resources->inGeneralStockCount($institutionId, $q);

        foreach ($rows as &$r) {
            $r['id']             = (int) $r['id'];
            $r['quantity_total'] = (int) $r['quantity_total'];
        }
        unset($r);

        return $this->response->setJSON([
            'data'  => $rows,
            'total' => $total,
            'pages' => (int) ceil($total / $limit),
            'page'  => $page,
        ]);
    }

    /**
     * GET /admin/ambientes/:id/recursos/data
     *
     * Paginated + searchable list of resources allocated to the room.
     * Query params: q, page, limit (10|25|50)
     */
    public function roomData(int $roomId): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $room          = $this->rooms->where('institution_id', $institutionId)->find($roomId);

        if (!$room) {
            return $this->response->setStatusCode(404)
                ->setJSON(['error' => 'Ambiente não encontrado.']);
        }

        $q      = trim($this->request->getGet('q')     ?? '');
        $page   = max(1, (int) ($this->request->getGet('page')  ?? 1));
        $limit  = in_array((int) ($this->request->getGet('limit') ?? 10), [10, 25, 50])
                    ? (int) $this->request->getGet('limit') : 10;
        $offset = ($page - 1) * $limit;

        $rows  = $this->resources->allocatedToRoomSearch($roomId, $q, $limit, $offset);
        $total = $this->resources->allocatedToRoomCount($roomId, $q);

        foreach ($rows as &$r) {
            $r['id']                 = (int) $r['id'];
            $r['room_resource_id']   = (int) $r['room_resource_id'];
            $r['allocated_quantity'] = (int) $r['allocated_quantity'];
        }
        unset($r);

        return $this->response->setJSON([
            'room_name' => $room['name'],
            'data'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'pages'     => (int) ceil($total / $limit),
            'limit'     => $limit,
        ]);
    }

    /**
     * GET /admin/ambientes/:id/recursos/exportar-xlsx
     */
    public function exportXlsx(int $roomId): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $room          = $this->rooms->where('institution_id', $institutionId)->find($roomId);

        if (!$room) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Ambiente não encontrado.']);
        }

        $q    = trim($this->request->getGet('q') ?? '');
        $rows = $this->resources->allocatedToRoomSearch($roomId, $q, 5000, 0);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet()->setTitle('Recursos Alocados');

        $sheet->fromArray(['Recurso', 'Código/Patrimônio', 'Qtd. Alocada', 'Alocado por', 'Data de Alocação'], null, 'A1');
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                       'startColor' => ['rgb' => '1E40AF']],
        ]);

        $row = 2;
        foreach ($rows as $r) {
            $allocatedAt = $r['allocated_at']
                ? date('d/m/Y H:i', strtotime($r['allocated_at']))
                : '';
            $sheet->fromArray([
                $r['name'],
                $r['code'] ?? '',
                (int) $r['allocated_quantity'],
                $r['allocated_by_name'] ?? '',
                $allocatedAt,
            ], null, 'A' . $row);
            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':E' . $row)
                    ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }
            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="recursos_' . $roomId . '_' . date('Y-m-d') . '.xlsx"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody($content);
    }

    /**
     * GET /admin/ambientes/:id/recursos/exportar-pdf
     */
    public function exportPdf(int $roomId): void
    {
        $institutionId = $this->institution['id'] ?? 0;
        $room          = $this->rooms->where('institution_id', $institutionId)->find($roomId);

        if (!$room) {
            echo 'Ambiente não encontrado.'; exit;
        }

        $q    = trim($this->request->getGet('q') ?? '');
        $rows = $this->resources->allocatedToRoomSearch($roomId, $q, 5000, 0);

        $html = view('admin/rooms/room_resources_pdf', [
            'rows'        => $rows,
            'room'        => $room,
            'institution' => $this->institution,
            'generatedAt' => date('d/m/Y H:i'),
        ]);

        $options = new \Dompdf\Options();
        $options->setChroot(ROOTPATH);
        $options->setIsRemoteEnabled(false);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('recursos_' . $roomId . '_' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
        exit;
    }

    /**
     * POST /admin/ambientes/:id/recursos
     *
     * Allocates a resource from general stock to the room.
     * POST fields: resource_id, quantity
     */
    public function store(int $roomId): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $room          = $this->rooms->where('institution_id', $institutionId)->find($roomId);

        if (!$room) {
            return $this->response->setStatusCode(404)
                ->setJSON(['error' => 'Ambiente não encontrado.']);
        }

        $resourceId = (int) $this->request->getPost('resource_id');
        $quantity   = max(1, (int) $this->request->getPost('quantity'));
        $handlerId  = (int) ($this->currentUser()['id'] ?? 0);

        if ($resourceId < 1) {
            return $this->response->setStatusCode(422)
                ->setJSON(['error' => 'Selecione um recurso.']);
        }

        $result = $this->service->allocate($institutionId, $roomId, $resourceId, $quantity, $handlerId);

        if (!$result['success']) {
            return $this->response->setStatusCode(422)
                ->setJSON(['error' => $result['error']]);
        }

        service('audit')->log('resource.room_allocated', 'resource', $resourceId, null, [
            'room_id'  => $roomId,
            'room_name' => $room['name'],
            'quantity' => $quantity,
        ]);

        return $this->response->setJSON(['message' => 'Recurso alocado ao ambiente com sucesso.']);
    }

    /**
     * POST /admin/ambientes/:roomId/recursos/:resourceId/delete
     *
     * Deallocates a resource from the room.
     *
     * RN-R10: If POST field `force` is not truthy AND there are future approved bookings
     * for this room, returns { needs_confirm: true, future_bookings: [...] } so the
     * client can show a confirmation modal before calling again with force=1.
     *
     * POST fields: force (0|1), notes (optional)
     */
    public function destroy(int $roomId, int $resourceId): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $room          = $this->rooms->where('institution_id', $institutionId)->find($roomId);

        if (!$room) {
            return $this->response->setStatusCode(404)
                ->setJSON(['error' => 'Ambiente não encontrado.']);
        }

        $force     = (bool) $this->request->getPost('force');
        $handlerId = (int) ($this->currentUser()['id'] ?? 0);

        // RN-R10: warn about future approved bookings before deallocating
        if (!$force) {
            $futureBookings = $this->service->futureBookingsForRoom($roomId);
            if (!empty($futureBookings)) {
                // Format dates for display
                foreach ($futureBookings as &$bk) {
                    $bk['date_fmt']       = date('d/m/Y', strtotime($bk['date']));
                    $bk['start_time_fmt'] = substr($bk['start_time'], 0, 5);
                    $bk['end_time_fmt']   = substr($bk['end_time'], 0, 5);
                }
                unset($bk);

                return $this->response->setJSON([
                    'needs_confirm'   => true,
                    'future_bookings' => $futureBookings,
                ]);
            }
        }

        $notes  = $this->request->getPost('notes') ?: null;
        $result = $this->service->deallocate($institutionId, $roomId, $resourceId, $handlerId, $notes);

        if (!$result['success']) {
            return $this->response->setStatusCode(422)
                ->setJSON(['error' => $result['error']]);
        }

        service('audit')->log('resource.room_deallocated', 'resource', $resourceId, null, [
            'room_id'   => $roomId,
            'room_name' => $room['name'],
        ]);

        return $this->response->setJSON(['message' => 'Recurso removido do ambiente.']);
    }
}
