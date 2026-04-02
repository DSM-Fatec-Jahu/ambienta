<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ResourceModel;
use App\Models\ResourceMovementModel;
use App\Models\RoomModel;

/**
 * ResourceController — Sprint R1
 *
 * CRUD completo de recursos com:
 * - Travamento de quantidade = 1 quando patrimônio preenchido (RN-R01)
 * - Campo categoria
 * - created_by_id / updated_by_id automáticos
 * - Importação XLSX com validação de patrimônio
 * - Histórico de movimentações
 * - Auditoria: resource.created, resource.updated, resource.deleted, resource.imported
 */
class ResourceController extends BaseController
{
    private ResourceModel         $resources;
    private ResourceMovementModel $movements;
    private RoomModel             $rooms;

    public function __construct()
    {
        $this->resources = new ResourceModel();
        $this->movements = new ResourceMovementModel();
        $this->rooms     = new RoomModel();
    }

    // ── Listing ──────────────────────────────────────────────────────────────

    public function index(): string
    {
        $institutionId = $this->institution['id'] ?? 0;

        $categories = $this->resources->getDistinctCategories($institutionId);
        $rooms      = $this->rooms->activeForInstitution($institutionId);

        return view('admin/resources/index', $this->viewData([
            'pageTitle'  => 'Recursos',
            'categories' => $categories,
            'rooms'      => $rooms,
        ]));
    }

    public function data(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $page      = max(1, (int) ($this->request->getGet('page')      ?? 1));
        $q         = trim($this->request->getGet('q')                  ?? '');
        $categoria = trim($this->request->getGet('categoria')          ?? '');
        $localId   = (int) ($this->request->getGet('local')            ?? 0);
        $status    = (int) ($this->request->getGet('status')           ?? 0);
        $limit     = in_array((int) ($this->request->getGet('limit')   ?? 10), [10, 25, 50, 100])
                        ? (int) $this->request->getGet('limit') : 10;
        $offset    = ($page - 1) * $limit;

        $rows  = $this->resources->search($institutionId, $q, $categoria, $localId, $status, $limit, $offset);
        $total = $this->resources->searchCount($institutionId, $q, $categoria, $localId, $status);

        foreach ($rows as &$r) {
            $r['id']               = (int)  $r['id'];
            $r['institution_id']   = (int)  $r['institution_id'];
            $r['quantity_total']   = (int)  $r['quantity_total'];
            $r['is_active']        = (bool) $r['is_active'];
            $r['allocated_quantity'] = $r['allocated_quantity'] !== null ? (int) $r['allocated_quantity'] : null;
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
        $q         = trim($this->request->getGet('q')         ?? '');
        $categoria = trim($this->request->getGet('categoria') ?? '');
        $localId   = (int) ($this->request->getGet('local')   ?? 0);
        $status    = (int) ($this->request->getGet('status')  ?? 0);

        $rows = $this->resources->search($institutionId, $q, $categoria, $localId, $status, 5000, 0);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Recursos');

        $sheet->fromArray(['Nome', 'Categoria', 'Patrimônio', 'Quantidade', 'Localização', 'Status'], null, 'A1');
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                       'startColor' => ['rgb' => '1E40AF']],
        ]);

        $row = 2;
        foreach ($rows as $r) {
            $localizacao = !empty($r['current_room_name'])
                ? $r['current_room_name'] . (!empty($r['current_room_abbr']) ? ' (' . $r['current_room_abbr'] . ')' : '')
                : 'Estoque geral';

            $sheet->fromArray([
                $r['name'],
                $r['category'] ?? '',
                $r['code'] ?? '',
                (int) $r['quantity_total'],
                $localizacao,
                $r['is_active'] ? 'Ativo' : 'Inativo',
            ], null, 'A' . $row);

            if ($row % 2 === 0) {
                $sheet->getStyle('A'.$row.':F'.$row)
                    ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }
            $row++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        ob_start(); $writer->save('php://output'); $content = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="recursos_'.date('Y-m-d').'.xlsx"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody($content);
    }

    public function exportPdf(): void
    {
        $institutionId = $this->institution['id'] ?? 0;
        $q         = trim($this->request->getGet('q')         ?? '');
        $categoria = trim($this->request->getGet('categoria') ?? '');
        $localId   = (int) ($this->request->getGet('local')   ?? 0);
        $status    = (int) ($this->request->getGet('status')  ?? 0);

        $rows = $this->resources->search($institutionId, $q, $categoria, $localId, $status, 5000, 0);

        $html = view('admin/resources/pdf_export', [
            'rows'        => $rows,
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
        $dompdf->stream('recursos_'.date('Y-m-d').'.pdf', ['Attachment' => true]);
        exit;
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function store(): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $user          = $this->currentUser();

        $code = $this->request->getPost('code') ?: null;

        $rules    = ['name' => 'required|max_length[150]'];
        $messages = [
            'name' => [
                'required'   => 'O campo Nome é obrigatório.',
                'max_length' => 'O campo Nome deve ter no máximo 150 caracteres.',
            ],
        ];

        // RN-R01: patrimônio preenchido → quantidade travada em 1; sem patrimônio → campo obrigatório
        if ($code === null) {
            $rules['quantity_total'] = 'required|integer|greater_than[0]';
            $messages['quantity_total'] = [
                'required'     => 'O campo Quantidade total é obrigatório.',
                'integer'      => 'O campo Quantidade total deve ser um número inteiro.',
                'greater_than' => 'O campo Quantidade total deve ser maior que 0.',
            ];
        }

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $qty = $code !== null ? 1 : (int) $this->request->getPost('quantity_total');

        $id = $this->resources->insert([
            'institution_id' => $institutionId,
            'name'           => $this->request->getPost('name'),
            'category'       => $this->request->getPost('category') ?: null,
            'code'           => $code,
            'description'    => $this->request->getPost('description') ?: null,
            'quantity_total' => $qty,
            'is_active'      => (int) (bool) $this->request->getPost('is_active'),
            'created_by_id'  => (int) $user['id'],
            'updated_by_id'  => (int) $user['id'],
        ]);

        service('audit')->log('resource.created', 'resource', (int) $id, null, [
            'name' => $this->request->getPost('name'),
        ]);

        return redirect()->to(base_url('admin/recursos'))
            ->with('success', 'Recurso cadastrado com sucesso.');
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function update(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $user          = $this->currentUser();
        $item          = $this->resources->where('institution_id', $institutionId)->find($id);

        if (!$item) {
            return redirect()->to(base_url('admin/recursos'))->with('error', 'Recurso não encontrado.');
        }

        $code = $this->request->getPost('code') ?: null;

        $rules    = ['name' => 'required|max_length[150]'];
        $messages = [
            'name' => [
                'required'   => 'O campo Nome é obrigatório.',
                'max_length' => 'O campo Nome deve ter no máximo 150 caracteres.',
            ],
        ];

        // RN-R01: patrimônio preenchido → quantidade travada em 1; sem patrimônio → campo obrigatório
        if ($code === null) {
            $rules['quantity_total'] = 'required|integer|greater_than[0]';
            $messages['quantity_total'] = [
                'required'     => 'O campo Quantidade total é obrigatório.',
                'integer'      => 'O campo Quantidade total deve ser um número inteiro.',
                'greater_than' => 'O campo Quantidade total deve ser maior que 0.',
            ];
        }

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $qty = $code !== null ? 1 : (int) $this->request->getPost('quantity_total');

        $old = $item;
        $this->resources->update($id, [
            'name'           => $this->request->getPost('name'),
            'category'       => $this->request->getPost('category') ?: null,
            'code'           => $code,
            'description'    => $this->request->getPost('description') ?: null,
            'quantity_total' => $qty,
            'is_active'      => (int) (bool) $this->request->getPost('is_active'),
            'updated_by_id'  => (int) $user['id'],
        ]);

        service('audit')->log('resource.updated', 'resource', $id, $old, [
            'name' => $this->request->getPost('name'),
        ]);

        return redirect()->to(base_url('admin/recursos'))
            ->with('success', 'Recurso atualizado com sucesso.');
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function destroy(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $item          = $this->resources->where('institution_id', $institutionId)->find($id);

        if (!$item) {
            return redirect()->to(base_url('admin/recursos'))->with('error', 'Recurso não encontrado.');
        }

        // Block delete if resource has movement history
        if ($this->resources->hasMovements($id)) {
            return redirect()->to(base_url('admin/recursos'))
                ->with('error', 'Não é possível excluir um recurso com movimentações registradas. Inative-o em vez de excluir.');
        }

        $this->resources->delete($id);

        service('audit')->log('resource.deleted', 'resource', $id, $item, null);

        return redirect()->to(base_url('admin/recursos'))
            ->with('success', 'Recurso excluído.');
    }

    // ── Import Templates ─────────────────────────────────────────────────────

    /**
     * GET /admin/recursos/template-xlsx
     * Returns an XLSX template for bulk resource import.
     * Columns: nome, patrimonio, categoria, descricao, quantidade
     */
    public function templateXlsx(): \CodeIgniter\HTTP\ResponseInterface
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Recursos');

        // Header row
        $sheet->fromArray(['nome', 'patrimonio', 'categoria', 'descricao', 'quantidade'], null, 'A1');

        // Example rows
        $sheet->fromArray(['Projetor Epson',   'PRJ-001', 'Audiovisual', 'Resolução Full HD, 3000 lumens', 1], null, 'A2');
        $sheet->fromArray(['Câmera Canon',     '',        'Fotografia',  '',                               2], null, 'A3');
        $sheet->fromArray(['Notebook Dell',    'NB-010',  'Informática', 'Core i5, 8GB RAM',               1], null, 'A4');

        // Style header bold + background
        $headerStyle = $sheet->getStyle('A1:E1');
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('1D6FA4');
        $headerStyle->getFont()->getColor()->setRGB('FFFFFF');

        foreach (['A', 'B', 'C', 'D', 'E'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="template_recursos.xlsx"')
            ->setBody($content);
    }

    // ── File Import ──────────────────────────────────────────────────────────

    /**
     * POST /admin/recursos/importar
     * Parses an uploaded XLSX (primary) or CSV file and bulk-inserts resources.
     * Columns: nome (required), patrimonio, categoria, descricao, quantidade
     *
     * RN-R01: If patrimonio is set, quantidade is forced to 1 (line is rejected if quantidade != 1 and != blank).
     */
    public function importFile(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $user          = $this->currentUser();

        $file = $this->request->getFile('import_file');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Arquivo inválido ou não enviado.']);
        }

        $ext = strtolower($file->getClientExtension());

        if ($ext === 'xlsx') {
            [$imported, $errors] = $this->_importFromXlsx($file, $institutionId, (int) $user['id']);
        } elseif (in_array($ext, ['csv', 'txt'], true)) {
            [$imported, $errors] = $this->_importFromCsv($file, $institutionId, (int) $user['id']);
        } else {
            return $this->response->setStatusCode(422)
                ->setJSON(['error' => 'Formato não suportado. Envie um arquivo .xlsx ou .csv.']);
        }

        if ($imported > 0) {
            service('audit')->log('resource.imported', 'resource', null, null, [
                'imported' => $imported,
                'errors'   => count($errors),
            ]);
        }

        return $this->response->setJSON([
            'imported' => $imported,
            'errors'   => $errors,
            'message'  => "{$imported} recurso(s) importado(s) com sucesso."
                        . (count($errors) ? ' ' . count($errors) . ' linha(s) ignorada(s).' : ''),
        ]);
    }

    private function _importFromXlsx(
        \CodeIgniter\HTTP\Files\UploadedFile $file,
        int $institutionId,
        int $userId
    ): array {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getTempName());
        } catch (\Exception $e) {
            return [0, [['row' => '—', 'message' => 'Não foi possível ler o arquivo XLSX.']]];
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows  = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            return [0, [['row' => '—', 'message' => 'Planilha vazia.']]];
        }

        $header = array_map(fn($h) => strtolower(trim((string) $h)), array_shift($rows));
        $colMap = $this->_buildColMap($header);

        if (!isset($colMap['nome'])) {
            return [0, [['row' => '—', 'message' => 'Coluna obrigatória "nome" não encontrada no cabeçalho.']]];
        }

        return $this->_processRows($rows, $colMap, $institutionId, $userId, 2);
    }

    private function _importFromCsv(
        \CodeIgniter\HTTP\Files\UploadedFile $file,
        int $institutionId,
        int $userId
    ): array {
        $handle = fopen($file->getTempName(), 'r');
        if (!$handle) {
            return [0, [['row' => '—', 'message' => 'Não foi possível ler o arquivo.']]];
        }

        $rawHeader = fgetcsv($handle);
        if (!$rawHeader) {
            fclose($handle);
            return [0, [['row' => '—', 'message' => 'Arquivo CSV vazio ou com formato inválido.']]];
        }

        $header = array_map(fn($h) => strtolower(trim(preg_replace('/[\x{FEFF}]/u', '', $h))), $rawHeader);
        $colMap = $this->_buildColMap($header);

        if (!isset($colMap['nome'])) {
            fclose($handle);
            return [0, [['row' => '—', 'message' => 'Coluna obrigatória "nome" não encontrada no cabeçalho.']]];
        }

        $csvRows = [];
        $rowNum  = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            $assoc = [];
            foreach ($colMap as $key => $idx) {
                $assoc[$key] = $row[$idx] ?? '';
            }
            $csvRows[] = $assoc;
        }
        fclose($handle);

        return $this->_processRows($csvRows, null, $institutionId, $userId, 2, true);
    }

    private function _buildColMap(array $header): array
    {
        $colMap = [];
        foreach ($header as $idx => $key) {
            if (in_array($key, ['nome', 'categoria', 'descricao', 'quantidade'], true)) {
                $colMap[$key] = $idx;
            } elseif (in_array($key, ['patrimonio', 'codigo', 'patrimônio'], true)) {
                $colMap['patrimonio'] = $idx;
            }
        }
        return $colMap;
    }

    private function _processRows(
        array $rows,
        ?array $colMap,
        int $institutionId,
        int $userId,
        int $startRow,
        bool $isMappedAssoc = false
    ): array {
        $imported = 0;
        $errors   = [];
        $rowNum   = $startRow - 1;

        foreach ($rows as $row) {
            $rowNum++;

            if ($isMappedAssoc) {
                $assoc = $row;
            } else {
                $assoc = [
                    'nome'       => trim((string) ($row[$colMap['nome']]       ?? '')),
                    'patrimonio' => isset($colMap['patrimonio']) ? trim((string) ($row[$colMap['patrimonio']] ?? '')) : '',
                    'categoria'  => isset($colMap['categoria'])  ? trim((string) ($row[$colMap['categoria']]  ?? '')) : '',
                    'descricao'  => isset($colMap['descricao'])  ? trim((string) ($row[$colMap['descricao']]  ?? '')) : '',
                    'quantidade' => isset($colMap['quantidade']) ? trim((string) ($row[$colMap['quantidade']] ?? '')) : '',
                ];
            }

            [$ok, $err] = $this->_validateAndInsert($assoc, $rowNum, $institutionId, $userId);
            if ($ok) {
                $imported++;
            } elseif ($err) {
                $errors[] = $err;
            }
        }

        return [$imported, $errors];
    }

    private function _validateAndInsert(array $assoc, int $rowNum, int $institutionId, int $userId): array
    {
        $name = trim($assoc['nome'] ?? '');
        if ($name === '') {
            return [false, ['row' => $rowNum, 'message' => 'Coluna "nome" está vazia.']];
        }
        if (mb_strlen($name) > 150) {
            return [false, ['row' => $rowNum, 'message' => "Nome excede 150 caracteres: \"{$name}\""]];
        }

        $code = trim($assoc['patrimonio'] ?? '') ?: null;
        if ($code && mb_strlen($code) > 50) {
            return [false, ['row' => $rowNum, 'message' => "Patrimônio excede 50 caracteres na linha com nome \"{$name}\"."]];
        }

        // Duplicate patrimônio check — catches both DB duplicates and within-file duplicates
        if ($code) {
            $exists = $this->resources->where('institution_id', $institutionId)
                                      ->where('code', $code)
                                      ->withDeleted()
                                      ->first();
            if ($exists) {
                return [false, ['row' => $rowNum, 'message' => "Patrimônio \"{$code}\" já existe para o recurso \"{$exists['name']}\"."]];
            }
        }

        // RN-R01: patrimônio preenchido → quantidade deve ser 1
        $rawQty = trim($assoc['quantidade'] ?? '');
        $qty    = $rawQty !== '' ? (int) $rawQty : ($code ? 1 : 1);

        if ($code && $qty !== 1) {
            return [false, ['row' => $rowNum, 'message' => "Recurso \"{$name}\" tem patrimônio preenchido — quantidade deve ser 1 (recebido: {$qty})."]];
        }

        if ($qty < 1) {
            return [false, ['row' => $rowNum, 'message' => "Quantidade inválida na linha com nome \"{$name}\"."]];
        }

        $category    = trim($assoc['categoria'] ?? '') ?: null;
        $description = trim($assoc['descricao']  ?? '') ?: null;

        $this->resources->insert([
            'institution_id' => $institutionId,
            'name'           => $name,
            'category'       => $category,
            'code'           => $code,
            'description'    => $description,
            'quantity_total' => $qty,
            'is_active'      => 1,
            'created_by_id'  => $userId,
            'updated_by_id'  => $userId,
        ]);

        return [true, null];
    }

    // ── Movement History ─────────────────────────────────────────────────────

    /**
     * GET /admin/recursos/:id/historico
     * Returns JSON movement history for a resource.
     */
    public function history(int $id): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $item          = $this->resources->where('institution_id', $institutionId)->find($id);

        if (!$item) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Recurso não encontrado.']);
        }

        $history = $this->movements->historyForResource($id);

        return $this->response->setJSON([
            'resource_name' => $item['name'],
            'history'       => $history,
        ]);
    }
}
