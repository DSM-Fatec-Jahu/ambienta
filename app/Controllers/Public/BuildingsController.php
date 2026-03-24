<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;

class BuildingsController extends BaseController
{
    public function index(): string
    {
        return view('public/buildings', $this->viewData([
            'pageTitle' => 'Prédios',
            'buildings' => [],  // Sprint 3 will populate this
        ]));
    }
}
