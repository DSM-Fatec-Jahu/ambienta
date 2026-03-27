<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class ReportsController extends BaseController
{
    public function index(): string
    {
        $institutionId = $this->institution['id'] ?? 0;
        $db            = db_connect();

        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo   = $this->request->getGet('date_to')   ?? date('Y-m-d');

        // ── Totals by status ──────────────────────────────────────────────────
        $byStatus = $db->table('bookings')
            ->select('status, COUNT(*) AS total')
            ->where('institution_id', $institutionId)
            ->where('deleted_at IS NULL')
            ->where('date >=', $dateFrom)
            ->where('date <=', $dateTo)
            ->groupBy('status')
            ->get()->getResultArray();

        $statusMap = array_column($byStatus, 'total', 'status');

        // ── Top rooms ─────────────────────────────────────────────────────────
        $topRooms = $db->table('bookings bk')
            ->select('r.name AS room_name, b.name AS building_name, COUNT(*) AS total,
                      SUM(bk.attendees_count) AS total_attendees')
            ->join('rooms r', 'r.id = bk.room_id', 'left')
            ->join('buildings b', 'b.id = r.building_id', 'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.deleted_at IS NULL')
            ->where('bk.date >=', $dateFrom)
            ->where('bk.date <=', $dateTo)
            ->where('bk.status', 'approved')
            ->groupBy('bk.room_id')
            ->orderBy('total', 'DESC')
            ->limit(10)
            ->get()->getResultArray();

        // ── Top requesters ────────────────────────────────────────────────────
        $topUsers = $db->table('bookings bk')
            ->select('u.name AS user_name, u.email, COUNT(*) AS total')
            ->join('users u', 'u.id = bk.owner_id', 'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.deleted_at IS NULL')
            ->where('bk.date >=', $dateFrom)
            ->where('bk.date <=', $dateTo)
            ->groupBy('bk.owner_id')
            ->orderBy('total', 'DESC')
            ->limit(10)
            ->get()->getResultArray();

        // ── Daily count (last 30 days in range) ───────────────────────────────
        $dailyRaw = $db->table('bookings')
            ->select('date, COUNT(*) AS total')
            ->where('institution_id', $institutionId)
            ->where('deleted_at IS NULL')
            ->where('date >=', $dateFrom)
            ->where('date <=', $dateTo)
            ->whereIn('status', ['approved', 'pending'])
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get()->getResultArray();

        $daily = array_column($dailyRaw, 'total', 'date');

        return view('admin/reports/index', $this->viewData([
            'pageTitle'  => 'Relatórios',
            'dateFrom'   => $dateFrom,
            'dateTo'     => $dateTo,
            'statusMap'  => $statusMap,
            'topRooms'   => $topRooms,
            'topUsers'   => $topUsers,
            'daily'      => $daily,
        ]));
    }

    /**
     * Export bookings in the date range as CSV.
     * GET /admin/relatorios/exportar-csv
     */
    public function exportCsv(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $db            = db_connect();

        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo   = $this->request->getGet('date_to')   ?? date('Y-m-d');
        $status   = $this->request->getGet('status')    ?? '';

        $query = $db->table('bookings bk')
            ->select('bk.id, bk.title, bk.date, bk.start_time, bk.end_time,
                      bk.attendees_count, bk.status, bk.created_at, bk.reviewed_at,
                      bk.review_notes, bk.cancelled_reason,
                      r.name AS room_name, r.code AS room_code,
                      b.name AS building_name,
                      u.name AS user_name, u.email AS user_email,
                      rev.name AS reviewer_name')
            ->join('rooms r',    'r.id = bk.room_id',    'left')
            ->join('buildings b','b.id = r.building_id', 'left')
            ->join('users u',    'u.id = bk.owner_id',   'left')
            ->join('users rev',  'rev.id = bk.reviewer_id', 'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.deleted_at IS NULL')
            ->where('bk.date >=', $dateFrom)
            ->where('bk.date <=', $dateTo)
            ->orderBy('bk.date ASC, bk.start_time ASC');

        if ($status) {
            $query->where('bk.status', $status);
        }

        $rows = $query->get()->getResultArray();

        // Build CSV in memory
        $csv  = fopen('php://temp', 'r+');

        fputcsv($csv, [
            'ID', 'Título', 'Data', 'Início', 'Término', 'Participantes', 'Status',
            'Ambiente', 'Código', 'Prédio',
            'Solicitante', 'E-mail solicitante',
            'Revisor', 'Data revisão', 'Observação revisão',
            'Motivo cancelamento', 'Criado em',
        ], ';');

        $statusLabels = [
            'pending'   => 'Pendente',
            'approved'  => 'Aprovada',
            'rejected'  => 'Recusada',
            'cancelled' => 'Cancelada',
            'absent'    => 'Ausente',
        ];

        foreach ($rows as $r) {
            fputcsv($csv, [
                $r['id'],
                $r['title'],
                date('d/m/Y', strtotime($r['date'])),
                substr($r['start_time'], 0, 5),
                substr($r['end_time'],   0, 5),
                $r['attendees_count'],
                $statusLabels[$r['status']] ?? $r['status'],
                $r['room_name']     ?? '',
                $r['room_code']     ?? '',
                $r['building_name'] ?? '',
                $r['user_name']     ?? '',
                $r['user_email']    ?? '',
                $r['reviewer_name'] ?? '',
                $r['reviewed_at']   ? date('d/m/Y H:i', strtotime($r['reviewed_at'])) : '',
                $r['review_notes']  ?? '',
                $r['cancelled_reason'] ?? '',
                date('d/m/Y H:i', strtotime($r['created_at'])),
            ], ';');
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        $filename = 'reservas_' . $dateFrom . '_' . $dateTo . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody("\xEF\xBB\xBF" . $content); // UTF-8 BOM for Excel
    }

    /**
     * Export bookings in the date range as PDF.
     * GET /admin/relatorios/exportar-pdf
     */
    public function exportPdf(): void
    {
        $institutionId = $this->institution['id'] ?? 0;
        $db            = db_connect();

        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo   = $this->request->getGet('date_to')   ?? date('Y-m-d');
        $status   = $this->request->getGet('status')    ?? '';

        $query = $db->table('bookings bk')
            ->select('bk.id, bk.title, bk.date, bk.start_time, bk.end_time,
                      bk.attendees_count, bk.status,
                      r.name AS room_name, r.code AS room_code,
                      b.name AS building_name,
                      u.name AS user_name, u.email AS user_email,
                      rev.name AS reviewer_name,
                      bk.review_notes, bk.cancelled_reason')
            ->join('rooms r',    'r.id = bk.room_id',    'left')
            ->join('buildings b','b.id = r.building_id', 'left')
            ->join('users u',    'u.id = bk.owner_id',   'left')
            ->join('users rev',  'rev.id = bk.reviewer_id', 'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.deleted_at IS NULL')
            ->where('bk.date >=', $dateFrom)
            ->where('bk.date <=', $dateTo)
            ->orderBy('bk.date ASC, bk.start_time ASC');

        if ($status) {
            $query->where('bk.status', $status);
        }

        $rows = $query->get()->getResultArray();

        $statusLabels = [
            'pending'   => 'Pendente',
            'approved'  => 'Aprovada',
            'rejected'  => 'Recusada',
            'cancelled' => 'Cancelada',
            'absent'    => 'Ausente',
        ];

        $html = view('admin/reports/pdf_export', [
            'rows'          => $rows,
            'dateFrom'      => $dateFrom,
            'dateTo'        => $dateTo,
            'institution'   => $this->institution,
            'statusLabels'  => $statusLabels,
            'generatedAt'   => date('d/m/Y H:i'),
        ]);

        $options = new \Dompdf\Options();
        $options->setChroot(ROOTPATH);
        $options->setIsRemoteEnabled(false);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'reservas_' . $dateFrom . '_' . $dateTo . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    /**
     * Room occupancy report.
     * GET /admin/relatorios/ocupacao
     */
    public function occupancy(): string
    {
        $institutionId = $this->institution['id'] ?? 0;

        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo   = $this->request->getGet('date_to')   ?? date('Y-m-d');

        $bookingModel = new \App\Models\BookingModel();
        $rows = $bookingModel->occupancyByRoom($institutionId, $dateFrom, $dateTo);

        // Calculate days in range for a rough available-hours reference (operating days × 8h)
        $days = max(1, (int) ((strtotime($dateTo) - strtotime($dateFrom)) / 86400) + 1);

        return view('admin/reports/occupancy', $this->viewData([
            'pageTitle' => 'Ocupação por Sala',
            'dateFrom'  => $dateFrom,
            'dateTo'    => $dateTo,
            'rows'      => $rows,
            'days'      => $days,
        ]));
    }

    /**
     * Equipment usage report.
     * GET /admin/relatorios/equipamentos
     */
    public function equipment(): string
    {
        $institutionId = $this->institution['id'] ?? 0;

        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo   = $this->request->getGet('date_to')   ?? date('Y-m-d');

        $bookingModel = new \App\Models\BookingModel();
        $rows = $bookingModel->equipmentUsage($institutionId, $dateFrom, $dateTo);

        return view('admin/reports/equipment', $this->viewData([
            'pageTitle' => 'Uso de Recursos',
            'dateFrom'  => $dateFrom,
            'dateTo'    => $dateTo,
            'rows'      => $rows,
        ]));
    }

    /**
     * Export equipment usage report as CSV.
     * GET /admin/relatorios/equipamentos/exportar-csv
     */
    public function exportEquipmentCsv(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;

        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo   = $this->request->getGet('date_to')   ?? date('Y-m-d');

        $bookingModel = new \App\Models\BookingModel();
        $rows = $bookingModel->equipmentUsage($institutionId, $dateFrom, $dateTo);

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['Recurso', 'Código', 'Reservas com uso', 'Qtd. total solicitada'], ';');

        foreach ($rows as $r) {
            fputcsv($csv, [
                $r['equipment_name'],
                $r['equipment_code'] ?? '',
                $r['total_bookings'],
                $r['total_quantity'],
            ], ';');
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="recursos_' . $dateFrom . '_' . $dateTo . '.csv"')
            ->setBody("\xEF\xBB\xBF" . $content);
    }

    /**
     * User activity report.
     * GET /admin/relatorios/usuarios
     */
    public function userActivity(): string
    {
        $institutionId = $this->institution['id'] ?? 0;

        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo   = $this->request->getGet('date_to')   ?? date('Y-m-d');

        $bookingModel = new \App\Models\BookingModel();
        $rows = $bookingModel->activityByUser($institutionId, $dateFrom, $dateTo);

        return view('admin/reports/user_activity', $this->viewData([
            'pageTitle' => 'Atividade por Usuário',
            'dateFrom'  => $dateFrom,
            'dateTo'    => $dateTo,
            'rows'      => $rows,
        ]));
    }

    /**
     * Export user activity report as CSV.
     * GET /admin/relatorios/usuarios/exportar-csv
     */
    public function exportUserActivityCsv(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;

        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo   = $this->request->getGet('date_to')   ?? date('Y-m-d');

        $bookingModel = new \App\Models\BookingModel();
        $rows = $bookingModel->activityByUser($institutionId, $dateFrom, $dateTo);

        $roleLabels = [
            'role_admin'         => 'Admin',
            'role_director'      => 'Diretor',
            'role_vice_director' => 'Vice-diretor',
            'role_coordinator'   => 'Coordenador',
            'role_technician'    => 'Técnico',
            'role_requester'     => 'Professor',
        ];

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, [
            'Usuário', 'E-mail', 'Perfil',
            'Total', 'Aprovadas', 'Pendentes', 'Recusadas', 'Canceladas', 'Ausentes',
            'Taxa aprovação (%)', 'Taxa ausência (%)',
        ], ';');

        foreach ($rows as $r) {
            $approved  = (int) $r['total_approved'];
            $total     = (int) $r['total'];
            $absent    = (int) $r['total_absent'];
            $decided   = $approved + (int) $r['total_rejected'];
            $approvalRate = $decided > 0 ? round(($approved / $decided) * 100, 1) : 0;
            $absenceRate  = $approved > 0 ? round(($absent / $approved) * 100, 1) : 0;

            fputcsv($csv, [
                $r['user_name']  ?? '',
                $r['email']      ?? '',
                $roleLabels[$r['user_role']] ?? $r['user_role'],
                $total,
                $approved,
                (int) $r['total_pending'],
                (int) $r['total_rejected'],
                (int) $r['total_cancelled'],
                $absent,
                $approvalRate . '%',
                $absenceRate  . '%',
            ], ';');
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="usuarios_' . $dateFrom . '_' . $dateTo . '.csv"')
            ->setBody("\xEF\xBB\xBF" . $content);
    }

    /**
     * Export occupancy report as CSV.
     * GET /admin/relatorios/ocupacao/exportar-csv
     */
    public function exportOccupancyCsv(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;

        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo   = $this->request->getGet('date_to')   ?? date('Y-m-d');

        $bookingModel = new \App\Models\BookingModel();
        $rows = $bookingModel->occupancyByRoom($institutionId, $dateFrom, $dateTo);

        $days = max(1, (int) ((strtotime($dateTo) - strtotime($dateFrom)) / 86400) + 1);
        $refHours = $days * 8;

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['Sala', 'Código', 'Prédio', 'Capacidade', 'Reservas', 'Horas reservadas', 'Ref. horas disponíveis', '% Ocupação'], ';');

        foreach ($rows as $r) {
            $pct = $refHours > 0 ? round(min(100, ($r['total_hours'] / $refHours) * 100), 1) : 0;
            fputcsv($csv, [
                $r['room_name'],
                $r['room_code'] ?? '',
                $r['building_name'] ?? '',
                $r['capacity'],
                $r['total_bookings'],
                number_format((float)$r['total_hours'], 1, ',', '.'),
                $refHours,
                $pct . '%',
            ], ';');
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="ocupacao_' . $dateFrom . '_' . $dateTo . '.csv"')
            ->setBody("\xEF\xBB\xBF" . $content);
    }
}
