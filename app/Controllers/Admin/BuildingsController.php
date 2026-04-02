<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BuildingModel;

class BuildingsController extends BaseController
{
    private BuildingModel $buildings;

    public function __construct()
    {
        $this->buildings = new BuildingModel();
    }

    public function index(): string
    {
        return view('admin/buildings/index', $this->viewData([
            'pageTitle' => 'Prédios',
        ]));
    }

    public function data(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;

        $page   = max(1, (int) ($this->request->getGet('page')    ?? 1));
        $q      = trim($this->request->getGet('q')                ?? '');
        $status = (int) ($this->request->getGet('status')         ?? 0);
        $limit  = in_array((int) ($this->request->getGet('limit') ?? 10), [10, 25, 50, 100])
                      ? (int) $this->request->getGet('limit') : 10;
        $offset = ($page - 1) * $limit;

        $rows  = $this->buildings->search($institutionId, $q, $status, $limit, $offset);
        $total = $this->buildings->searchCount($institutionId, $q, $status);

        foreach ($rows as &$r) {
            $r['id']        = (int)  $r['id'];
            $r['is_active'] = (bool) $r['is_active'];
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

    public function exportXlsx(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $q      = trim($this->request->getGet('q')       ?? '');
        $status = (int) ($this->request->getGet('status') ?? 0);

        $rows = $this->buildings->search($institutionId, $q, $status, 5000, 0);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Prédios');

        $sheet->fromArray(['Nome', 'Código', 'Descrição', 'Status'], null, 'A1');
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                       'startColor' => ['rgb' => '1E40AF']],
        ]);

        $row = 2;
        foreach ($rows as $r) {
            $sheet->fromArray([
                $r['name'],
                $r['code']        ?? '',
                $r['description'] ?? '',
                $r['is_active']   ? 'Ativo' : 'Inativo',
            ], null, 'A' . $row);

            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':D' . $row)
                    ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }
            $row++;
        }

        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="predios_' . date('Y-m-d') . '.xlsx"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody($content);
    }

    public function exportPdf(): void
    {
        $institutionId = $this->institution['id'] ?? 0;
        $q      = trim($this->request->getGet('q')       ?? '');
        $status = (int) ($this->request->getGet('status') ?? 0);

        $rows = $this->buildings->search($institutionId, $q, $status, 5000, 0);

        $html = view('admin/buildings/pdf_export', [
            'rows'        => $rows,
            'institution' => $this->institution,
            'generatedAt' => date('d/m/Y H:i'),
        ]);

        $options = new \Dompdf\Options();
        $options->setChroot(ROOTPATH);
        $options->setIsRemoteEnabled(false);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('predios_' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
        exit;
    }

    public function store(): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;

        $rules = [
            'name' => 'required|max_length[200]',
            'code' => 'permit_empty|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->buildings->insert([
            'institution_id' => $institutionId,
            'name'           => $this->request->getPost('name'),
            'code'           => $this->request->getPost('code') ?: null,
            'description'    => $this->request->getPost('description') ?: null,
            'is_active'      => (int) (bool) $this->request->getPost('is_active'),
        ]);

        return redirect()->to(base_url('admin/predios'))
            ->with('success', 'Prédio cadastrado com sucesso.');
    }

    public function update(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $building      = $this->buildings->where('institution_id', $institutionId)->find($id);

        if (!$building) {
            return redirect()->to(base_url('admin/predios'))->with('error', 'Prédio não encontrado.');
        }

        $rules = [
            'name' => 'required|max_length[200]',
            'code' => 'permit_empty|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->buildings->update($id, [
            'name'        => $this->request->getPost('name'),
            'code'        => $this->request->getPost('code') ?: null,
            'description' => $this->request->getPost('description') ?: null,
            'is_active'   => (int) (bool) $this->request->getPost('is_active'),
        ]);

        return redirect()->to(base_url('admin/predios'))
            ->with('success', 'Prédio atualizado com sucesso.');
    }

    public function delete(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $building      = $this->buildings->where('institution_id', $institutionId)->find($id);

        if (!$building) {
            return redirect()->to(base_url('admin/predios'))->with('error', 'Prédio não encontrado.');
        }

        // Check if any rooms are linked
        $roomCount = db_connect()->table('rooms')
            ->where('building_id', $id)
            ->where('deleted_at IS NULL')
            ->countAllResults();

        if ($roomCount > 0) {
            return redirect()->to(base_url('admin/predios'))
                ->with('error', "Não é possível excluir: este prédio possui {$roomCount} ambiente(s) vinculado(s).");
        }

        $this->buildings->delete($id);

        return redirect()->to(base_url('admin/predios'))
            ->with('success', 'Prédio excluído.');
    }
}
