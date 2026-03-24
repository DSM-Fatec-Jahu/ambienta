<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;

class AgendaController extends BaseController
{
    public function index(): string
    {
        return view('public/agenda', $this->viewData([
            'pageTitle' => 'Agenda',
        ]));
    }
}
