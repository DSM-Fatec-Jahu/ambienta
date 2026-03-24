<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;

class HomeController extends BaseController
{
    public function index(): \CodeIgniter\HTTP\RedirectResponse
    {
        // Redirect to public agenda as the home page
        return redirect()->to(base_url('agenda'));
    }
}
