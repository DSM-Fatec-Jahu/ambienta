<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class SettingsController extends BaseController
{
    public function index(): string
    {
        $institution = $this->institution;
        $settings    = $institution['settings_decoded'] ?? [];

        return view('admin/settings/index', $this->viewData([
            'pageTitle'   => 'Configurações',
            'institution' => $institution,
            'settings'    => $settings,
        ]));
    }

    public function update(): \CodeIgniter\HTTP\RedirectResponse
    {
        $db   = db_connect();
        $id   = $this->institution['id'] ?? 0;
        $inst = $db->table('institutions')->where('id', $id)->get()->getRowArray();

        if (!$inst) {
            return redirect()->to(base_url('admin/configuracoes'))->with('error', 'Instituição não encontrada.');
        }

        $current  = json_decode($inst['settings'] ?? '{}', true) ?? [];

        // ── Auth settings ──────────────────────────────────────────
        $current['auth']['local_login_enabled']  = (bool) $this->request->getPost('local_login_enabled');
        $current['auth']['sso_google_enabled']   = (bool) $this->request->getPost('sso_google_enabled');

        $rawDomains = trim($this->request->getPost('sso_allowed_domains') ?? '');
        $domains    = array_filter(array_map('trim', explode(',', $rawDomains)));
        $current['auth']['sso_allowed_domains']  = array_values($domains);

        // ── Booking settings ───────────────────────────────────────
        $current['booking']['max_days_ahead']        = max(1, (int) $this->request->getPost('max_days_ahead'));
        $current['booking']['min_duration_min']      = max(15, (int) $this->request->getPost('min_duration_min'));
        $current['booking']['max_duration_min']      = max(15, (int) $this->request->getPost('max_duration_min'));
        $current['booking']['requires_approval']     = (bool) $this->request->getPost('requires_approval');
        $current['booking']['max_bookings_per_week'] = max(0, (int) $this->request->getPost('max_bookings_per_week'));

        // ── Institution basic info ─────────────────────────────────
        $name  = trim($this->request->getPost('institution_name') ?? '');
        $email = trim($this->request->getPost('contact_email')    ?? '');

        if ($email !== '') {
            $current['contact_email'] = $email;
        }

        $updateData = ['settings' => json_encode($current, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)];
        if ($name) $updateData['name'] = $name;

        $db->table('institutions')->where('id', $id)->update($updateData);

        service('audit')->log('settings.updated', 'institution', $id, $inst, $updateData);

        return redirect()->to(base_url('admin/configuracoes'))
            ->with('success', 'Configurações salvas com sucesso.');
    }
}
