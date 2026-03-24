<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class OperatingHoursController extends BaseController
{
    private const DAYS = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira',
                          'Quinta-feira', 'Sexta-feira', 'Sábado'];

    public function index(): string
    {
        $institutionId = $this->institution['id'] ?? 0;
        $db = db_connect();

        // Ensure all 7 rows exist
        for ($d = 0; $d < 7; $d++) {
            $exists = $db->table('operating_hours')
                ->where('institution_id', $institutionId)
                ->where('day_of_week', $d)
                ->countAllResults();

            if (!$exists) {
                $db->table('operating_hours')->insert([
                    'institution_id' => $institutionId,
                    'day_of_week'    => $d,
                    'is_open'        => ($d >= 1 && $d <= 5) ? 1 : 0,
                    'open_time'      => '08:00:00',
                    'close_time'     => '18:00:00',
                ]);
            }
        }

        $rows = $db->table('operating_hours')
            ->where('institution_id', $institutionId)
            ->orderBy('day_of_week')
            ->get()->getResultArray();

        return view('admin/horarios/index', $this->viewData([
            'pageTitle' => 'Horários de Funcionamento',
            'rows'      => $rows,
            'dayNames'  => self::DAYS,
        ]));
    }

    public function update(): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $db = db_connect();

        for ($d = 0; $d < 7; $d++) {
            $isOpen    = (int) ($this->request->getPost("is_open_{$d}") ?? 0);
            $openTime  = $this->request->getPost("open_time_{$d}")  ?: null;
            $closeTime = $this->request->getPost("close_time_{$d}") ?: null;
            $extra     = (int) ($this->request->getPost("requires_extra_{$d}") ?? 0);

            $db->table('operating_hours')
                ->where('institution_id', $institutionId)
                ->where('day_of_week', $d)
                ->update([
                    'is_open'                   => $isOpen,
                    'open_time'                 => $isOpen ? $openTime : null,
                    'close_time'                => $isOpen ? $closeTime : null,
                    'requires_extra_confirmation' => $extra,
                ]);
        }

        service('audit')->log('operating_hours.updated', 'operating_hours', null);

        return redirect()->to(base_url('admin/horarios'))
            ->with('success', 'Horários de funcionamento atualizados.');
    }
}
