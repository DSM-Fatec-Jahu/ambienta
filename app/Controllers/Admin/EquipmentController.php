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

    // ── Import Templates & File Import ───────────────────────────────────────

    /**
     * GET /admin/equipamentos/template-csv
     * Returns a CSV template file for bulk equipment import (legacy/fallback).
     */
    public function templateCsv(): \CodeIgniter\HTTP\ResponseInterface
    {
        $csv  = "nome,patrimonio,descricao,quantidade\n";
        $csv .= "Projetor Epson,PRJ-001,\"Resolução Full HD, 3000 lumens\",2\n";
        $csv .= "Câmera Canon,CAM-001,,1\n";

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="template_equipamentos.csv"')
            ->setBody($csv);
    }

    /**
     * GET /admin/equipamentos/template-xlsx
     * Returns an XLSX template file for bulk equipment import.
     */
    public function templateXlsx(): \CodeIgniter\HTTP\ResponseInterface
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Equipamentos');

        // Header row
        $sheet->fromArray(['nome', 'patrimonio', 'descricao', 'quantidade'], null, 'A1');

        // Example rows
        $sheet->fromArray(['Projetor Epson', 'PRJ-001', 'Resolução Full HD, 3000 lumens', 2], null, 'A2');
        $sheet->fromArray(['Câmera Canon',   'CAM-001', '',                               1], null, 'A3');

        // Style header bold
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        foreach (['A', 'B', 'C', 'D'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="template_equipamentos.xlsx"')
            ->setBody($content);
    }

    /**
     * POST /admin/equipamentos/importar
     * Parses an uploaded XLSX (primary) or CSV file and bulk-inserts equipment.
     * Expected columns: nome, patrimonio (or legacy: codigo), descricao, quantidade
     */
    public function importFile(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;

        $file = $this->request->getFile('import_file');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Arquivo inválido ou não enviado.']);
        }

        $ext = strtolower($file->getClientExtension());

        if ($ext === 'xlsx') {
            return $this->_importFromXlsx($file, $institutionId);
        }

        if (in_array($ext, ['csv', 'txt'], true)) {
            return $this->_importFromCsv($file, $institutionId);
        }

        return $this->response->setStatusCode(422)
            ->setJSON(['error' => 'Formato não suportado. Envie um arquivo .xlsx ou .csv.']);
    }

    private function _importFromXlsx(\CodeIgniter\HTTP\Files\UploadedFile $file, int $institutionId): \CodeIgniter\HTTP\ResponseInterface
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getTempName());
        } catch (\Exception $e) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Não foi possível ler o arquivo XLSX.']);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows  = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Planilha vazia.']);
        }

        // Normalize header
        $header = array_map(fn($h) => strtolower(trim((string) $h)), array_shift($rows));
        $colMap = $this->_buildColMap($header);

        if (!isset($colMap['nome'])) {
            return $this->response->setStatusCode(422)
                ->setJSON(['error' => 'Coluna obrigatória "nome" não encontrada no cabeçalho.']);
        }

        [$imported, $errors] = $this->_processRows($rows, $colMap, $institutionId, 2);

        return $this->response->setJSON([
            'imported' => $imported,
            'errors'   => $errors,
            'message'  => "{$imported} equipamento(s) importado(s) com sucesso."
                        . (count($errors) ? ' ' . count($errors) . ' linha(s) ignorada(s).' : ''),
        ]);
    }

    private function _importFromCsv(\CodeIgniter\HTTP\Files\UploadedFile $file, int $institutionId): \CodeIgniter\HTTP\ResponseInterface
    {
        $handle = fopen($file->getTempName(), 'r');
        if (!$handle) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Não foi possível ler o arquivo.']);
        }

        $rawHeader = fgetcsv($handle);
        if (!$rawHeader) {
            fclose($handle);
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Arquivo CSV vazio ou com formato inválido.']);
        }

        $header = array_map(fn($h) => strtolower(trim(preg_replace('/[\x{FEFF}]/u', '', $h))), $rawHeader);
        $colMap = $this->_buildColMap($header);

        if (!isset($colMap['nome'])) {
            fclose($handle);
            return $this->response->setStatusCode(422)
                ->setJSON(['error' => 'Coluna obrigatória "nome" não encontrada no cabeçalho.']);
        }

        $csvRows = [];
        $rowNum  = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            // Convert indexed CSV row to assoc-style array for _processRows
            $assoc = [];
            foreach ($colMap as $key => $idx) {
                $assoc[$key] = $row[$idx] ?? '';
            }
            $csvRows[] = ['_rowNum' => $rowNum, 'data' => $assoc];
        }
        fclose($handle);

        [$imported, $errors] = $this->_processMappedRows($csvRows, $institutionId);

        return $this->response->setJSON([
            'imported' => $imported,
            'errors'   => $errors,
            'message'  => "{$imported} equipamento(s) importado(s) com sucesso."
                        . (count($errors) ? ' ' . count($errors) . ' linha(s) ignorada(s).' : ''),
        ]);
    }

    /** Build column map from normalized header array. Accepts 'patrimonio' and legacy 'codigo'. */
    private function _buildColMap(array $header): array
    {
        $colMap = [];
        foreach ($header as $idx => $key) {
            if (in_array($key, ['nome', 'descricao', 'quantidade'], true)) {
                $colMap[$key] = $idx;
            } elseif ($key === 'patrimonio' || $key === 'codigo') {
                $colMap['patrimonio'] = $idx; // normalise legacy column name
            }
        }
        return $colMap;
    }

    /** Process rows from XLSX (array of arrays). */
    private function _processRows(array $rows, array $colMap, int $institutionId, int $startRow): array
    {
        $imported = 0;
        $errors   = [];
        $rowNum   = $startRow - 1;

        foreach ($rows as $row) {
            $rowNum++;
            $assoc = [
                'nome'       => trim((string) ($row[$colMap['nome']] ?? '')),
                'patrimonio' => isset($colMap['patrimonio']) ? trim((string) ($row[$colMap['patrimonio']] ?? '')) : '',
                'descricao'  => isset($colMap['descricao'])  ? trim((string) ($row[$colMap['descricao']]  ?? '')) : '',
                'quantidade' => isset($colMap['quantidade']) ? trim((string) ($row[$colMap['quantidade']] ?? '')) : '',
            ];

            [$ok, $err] = $this->_validateAndInsert($assoc, $rowNum, $institutionId);
            if ($ok) {
                $imported++;
            } elseif ($err) {
                $errors[] = $err;
            }
        }

        return [$imported, $errors];
    }

    /** Process rows already mapped to assoc arrays (from CSV path). */
    private function _processMappedRows(array $rows, int $institutionId): array
    {
        $imported = 0;
        $errors   = [];

        foreach ($rows as $item) {
            $assoc = [
                'nome'       => trim((string) ($item['data']['nome']       ?? '')),
                'patrimonio' => trim((string) ($item['data']['patrimonio'] ?? '')),
                'descricao'  => trim((string) ($item['data']['descricao']  ?? '')),
                'quantidade' => trim((string) ($item['data']['quantidade'] ?? '')),
            ];

            [$ok, $err] = $this->_validateAndInsert($assoc, $item['_rowNum'], $institutionId);
            if ($ok) {
                $imported++;
            } elseif ($err) {
                $errors[] = $err;
            }
        }

        return [$imported, $errors];
    }

    private function _validateAndInsert(array $assoc, int $rowNum, int $institutionId): array
    {
        $name = $assoc['nome'];
        if ($name === '') {
            return [false, ['row' => $rowNum, 'message' => 'Coluna "nome" está vazia.']];
        }
        if (mb_strlen($name) > 200) {
            return [false, ['row' => $rowNum, 'message' => "Nome excede 200 caracteres: \"{$name}\""]];
        }

        $qty = $assoc['quantidade'] !== '' ? (int) $assoc['quantidade'] : 1;
        if ($qty < 1) {
            return [false, ['row' => $rowNum, 'message' => "Quantidade inválida na linha com nome \"{$name}\"."]];
        }

        $code = $assoc['patrimonio'] !== '' ? $assoc['patrimonio'] : null;
        if ($code && mb_strlen($code) > 20) {
            return [false, ['row' => $rowNum, 'message' => "Patrimônio excede 20 caracteres na linha com nome \"{$name}\"."]];
        }

        $description = $assoc['descricao'] !== '' ? $assoc['descricao'] : null;

        $this->equipment->insert([
            'institution_id' => $institutionId,
            'name'           => $name,
            'code'           => $code,
            'description'    => $description,
            'quantity_total' => $qty,
            'is_active'      => 1,
        ]);

        return [true, null];
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
