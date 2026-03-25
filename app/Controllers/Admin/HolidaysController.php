<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\HolidayModel;

class HolidaysController extends BaseController
{
    private HolidayModel $holidays;

    public function __construct()
    {
        $this->holidays = new HolidayModel();
    }

    public function index(): string
    {
        $institutionId = $this->institution['id'] ?? 0;
        $items         = $this->holidays->forInstitution($institutionId);

        return view('admin/holidays/index', $this->viewData([
            'pageTitle' => 'Feriados',
            'items'     => $items,
        ]));
    }

    public function store(): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;

        if (!$this->validate(['name' => 'required|max_length[200]', 'date' => 'required|valid_date[Y-m-d]'])) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->holidays->insert([
            'institution_id' => $institutionId,
            'name'           => $this->request->getPost('name'),
            'date'           => $this->request->getPost('date'),
            'is_recurring'   => (int) (bool) $this->request->getPost('is_recurring'),
        ]);

        return redirect()->to(base_url('admin/feriados'))
            ->with('success', 'Feriado cadastrado com sucesso.');
    }

    public function update(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $item          = $this->holidays->where('institution_id', $institutionId)->find($id);

        if (!$item) {
            return redirect()->to(base_url('admin/feriados'))->with('error', 'Feriado não encontrado.');
        }

        if (!$this->validate(['name' => 'required|max_length[200]', 'date' => 'required|valid_date[Y-m-d]'])) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->holidays->update($id, [
            'name'         => $this->request->getPost('name'),
            'date'         => $this->request->getPost('date'),
            'is_recurring' => (int) (bool) $this->request->getPost('is_recurring'),
        ]);

        return redirect()->to(base_url('admin/feriados'))
            ->with('success', 'Feriado atualizado.');
    }

    public function delete(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $item          = $this->holidays->where('institution_id', $institutionId)->find($id);

        if (!$item) {
            return redirect()->to(base_url('admin/feriados'))->with('error', 'Feriado não encontrado.');
        }

        $this->holidays->delete($id);

        return redirect()->to(base_url('admin/feriados'))->with('success', 'Feriado removido.');
    }

    /**
     * POST /admin/feriados/importar-api/:year
     * Fetches Brazilian national holidays from BrasilAPI and inserts new ones.
     */
    public function importFromApi(int $year): \CodeIgniter\HTTP\ResponseInterface
    {
        if ($year < 2000 || $year > 2100) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Ano inválido.']);
        }

        $institutionId = $this->institution['id'] ?? 0;

        $client = \Config\Services::curlrequest();

        try {
            $apiResponse = $client->get(
                "https://brasilapi.com.br/api/feriados/v1/{$year}",
                ['timeout' => 10, 'http_errors' => false]
            );
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(502)
                ->setJSON(['error' => 'Não foi possível conectar à BrasilAPI: ' . $e->getMessage()]);
        }

        if ($apiResponse->getStatusCode() !== 200) {
            return $this->response->setStatusCode(502)
                ->setJSON(['error' => 'BrasilAPI retornou status ' . $apiResponse->getStatusCode()]);
        }

        $apiHolidays = json_decode($apiResponse->getBody(), true);
        if (!is_array($apiHolidays)) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'Resposta inválida da BrasilAPI.']);
        }

        // Build a set of existing MM-DD for duplicate detection
        $existing    = $this->holidays->forInstitution($institutionId);
        $existingSet = [];
        foreach ($existing as $h) {
            $existingSet[date('m-d', strtotime($h['date']))] = true;
        }

        $imported = 0;
        foreach ($apiHolidays as $h) {
            if (empty($h['date']) || empty($h['name'])) {
                continue;
            }
            $mmdd = date('m-d', strtotime($h['date']));
            if (isset($existingSet[$mmdd])) {
                continue; // already exists
            }
            $this->holidays->insert([
                'institution_id' => $institutionId,
                'name'           => $h['name'],
                'date'           => $h['date'],
                'is_recurring'   => 1,
            ]);
            $existingSet[$mmdd] = true;
            $imported++;
        }

        return $this->response->setJSON([
            'imported' => $imported,
            'message'  => $imported > 0
                ? "{$imported} feriado(s) importado(s) com sucesso."
                : 'Nenhum feriado novo encontrado para este ano (todos já estão cadastrados).',
        ]);
    }
}
