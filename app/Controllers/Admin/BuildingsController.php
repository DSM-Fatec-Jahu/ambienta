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
        $institutionId = $this->institution['id'] ?? 0;

        $items = $this->buildings
            ->forInstitution($institutionId)
            ->orderBy('name', 'ASC')
            ->findAll();

        return view('admin/buildings/index', $this->viewData([
            'pageTitle' => 'Prédios',
            'items'     => $items,
        ]));
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
