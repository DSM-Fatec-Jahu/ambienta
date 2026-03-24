<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EquipmentModel;

class EquipmentController extends BaseController
{
    private EquipmentModel $equipment;

    public function __construct()
    {
        $this->equipment = new EquipmentModel();
    }

    public function index(): string
    {
        $institutionId = $this->institution['id'] ?? 0;

        $items = $this->equipment
            ->where('institution_id', $institutionId)
            ->orderBy('name', 'ASC')
            ->findAll();

        return view('admin/equipment/index', $this->viewData([
            'pageTitle' => 'Equipamentos',
            'items'     => $items,
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
}
