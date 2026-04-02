<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BookingRatingModel;
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
        $buildings     = $this->buildings->activeForInstitution($institutionId);

        return view('admin/rooms/index', $this->viewData([
            'pageTitle' => 'Ambientes',
            'buildings' => $buildings,
        ]));
    }

    /**
     * JSON data endpoint for the infinite-scroll table.
     * GET /admin/ambientes/data
     *   ?q=         full-text search (name, code, building)
     *   ?building=  building_id filter
     *   ?status=    0=all, 1=active, 2=inactive, 3=maintenance
     *   ?page=      page number (1-based)
     */
    public function data(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;

        $page      = max(1, (int) ($this->request->getGet('page')     ?? 1));
        $q         = trim($this->request->getGet('q')                 ?? '');
        $buildingId = (int) ($this->request->getGet('building')       ?? 0);
        $status    = (int) ($this->request->getGet('status')          ?? 0);
        $limit     = in_array((int) ($this->request->getGet('limit') ?? 10), [10, 25, 50, 100])
                        ? (int) $this->request->getGet('limit')
                        : 10;
        $offset    = ($page - 1) * $limit;

        $rows  = $this->rooms->search($institutionId, $q, $buildingId, $status, $limit, $offset);
        $total = $this->rooms->searchCount($institutionId, $q, $buildingId, $status);

        $ratingModel = new BookingRatingModel();
        $ratingsMap  = $ratingModel->avgByRoomForInstitution($institutionId);

        foreach ($rows as &$r) {
            $rData = $ratingsMap[(int) $r['id']] ?? null;
            $r['avg_rating']    = ($rData && $rData['total_ratings'] > 0)
                ? (float) number_format((float) $rData['avg_rating'], 1)
                : null;
            $r['total_ratings'] = $rData ? (int) $rData['total_ratings'] : 0;
            // Cast booleans so JS gets proper types
            $r['is_active']               = (bool) $r['is_active'];
            $r['allows_equipment_lending'] = (bool) $r['allows_equipment_lending'];
            $r['maintenance_mode']         = (bool) $r['maintenance_mode'];
            $r['id']                       = (int)  $r['id'];
            $r['capacity']                 = (int)  $r['capacity'];
            $r['building_id']              = (int) ($r['building_id'] ?? 0);
        }
        unset($r);

        return $this->response->setJSON([
            'data'  => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => (int) ceil($total / $limit),
            'limit' => $limit,
        ]);
    }

    /**
     * Export filtered rooms as XLSX.
     * GET /admin/ambientes/exportar-xlsx
     */
    public function exportXlsx(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;

        $q          = trim($this->request->getGet('q')       ?? '');
        $buildingId = (int) ($this->request->getGet('building') ?? 0);
        $status     = (int) ($this->request->getGet('status')   ?? 0);

        $rows        = $this->rooms->search($institutionId, $q, $buildingId, $status, 5000, 0);
        $ratingModel = new BookingRatingModel();
        $ratingsMap  = $ratingModel->avgByRoomForInstitution($institutionId);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ambientes');

        // Header row
        $headers = [
            'ID', 'Nome', 'Código', 'Prédio', 'Andar',
            'Capacidade', 'Emp. Recursos', 'Ativo', 'Manutenção',
            'Motivo Manutenção', 'Até', 'Avaliação Média', 'Nº Avaliações',
        ];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font'    => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'    => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                          'startColor' => ['rgb' => '1E40AF']],
            'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($rows as $r) {
            $rData = $ratingsMap[(int) $r['id']] ?? null;
            $sheet->fromArray([
                (int) $r['id'],
                $r['name'],
                $r['code'] ?? '',
                $r['building_name'] ?? '',
                $r['floor'] ?? '',
                (int) $r['capacity'],
                $r['allows_equipment_lending'] ? 'Sim' : 'Não',
                $r['is_active'] ? 'Sim' : 'Não',
                $r['maintenance_mode'] ? 'Sim' : 'Não',
                $r['maintenance_reason'] ?? '',
                $r['maintenance_until'] ? date('d/m/Y', strtotime($r['maintenance_until'])) : '',
                ($rData && $rData['total_ratings'] > 0) ? number_format((float) $rData['avg_rating'], 1) : '',
                $rData ? (int) $rData['total_ratings'] : 0,
            ], null, 'A' . $row);

            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':M' . $row)
                    ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }
            $row++;
        }

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'ambientes_' . date('Y-m-d') . '.xlsx';

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody($content);
    }

    /**
     * Export filtered rooms as PDF.
     * GET /admin/ambientes/exportar-pdf
     */
    public function exportPdf(): void
    {
        $institutionId = $this->institution['id'] ?? 0;

        $q          = trim($this->request->getGet('q')          ?? '');
        $buildingId = (int) ($this->request->getGet('building') ?? 0);
        $status     = (int) ($this->request->getGet('status')   ?? 0);

        $rows        = $this->rooms->search($institutionId, $q, $buildingId, $status, 5000, 0);
        $ratingModel = new BookingRatingModel();
        $ratingsMap  = $ratingModel->avgByRoomForInstitution($institutionId);

        $html = view('admin/rooms/pdf_export', [
            'rows'        => $rows,
            'ratingsMap'  => $ratingsMap,
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

        $dompdf->stream('ambientes_' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
        exit;
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

    public function setMaintenance(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $room          = $this->rooms->where('institution_id', $institutionId)->find($id);

        if (!$room) {
            return redirect()->to(base_url('admin/ambientes'))->with('error', 'Ambiente não encontrado.');
        }

        $mode   = (int) (bool) $this->request->getPost('maintenance_mode');
        $until  = $this->request->getPost('maintenance_until') ?: null;
        $reason = $this->request->getPost('maintenance_reason') ?: null;

        $this->rooms->update($id, [
            'maintenance_mode'   => $mode,
            'maintenance_until'  => $mode ? $until : null,
            'maintenance_reason' => $mode ? $reason : null,
        ]);

        service('audit')->log('room.maintenance_set', 'room', $id, null, [
            'mode'   => $mode,
            'until'  => $until,
            'reason' => $reason,
        ]);

        $label = $mode ? 'colocado em manutenção' : 'retirado de manutenção';
        return redirect()->to(base_url('admin/ambientes'))
            ->with('success', "Ambiente «{$room['name']}» {$label}.");
    }
}
