<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;

class EquipmentController extends BaseController
{
    public function index(): string
    {
        return view('public/equipment', $this->viewData([
            'pageTitle' => 'Equipamentos',
            'equipment' => [],  // Sprint 4 will populate this
        ]));
    }
}
