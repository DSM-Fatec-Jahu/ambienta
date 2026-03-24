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
}
