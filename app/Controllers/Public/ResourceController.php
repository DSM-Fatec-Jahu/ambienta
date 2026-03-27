<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;
use App\Models\ResourceModel;

class ResourceController extends BaseController
{
    public function index(): string
    {
        $institutionId = $this->institution['id'] ?? 0;
        $model = new ResourceModel();

        $resources = $model->withCurrentLocation($institutionId);

        return view('public/resources', $this->viewData([
            'pageTitle' => 'Recursos',
            'resources' => $resources,
        ]));
    }
}
