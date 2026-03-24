<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\RoomModel;
use App\Models\EquipmentModel;
use App\Models\HolidayModel;

class BookingsController extends BaseController
{
    private BookingModel   $bookings;
    private RoomModel      $rooms;
    private EquipmentModel $equipment;
    private HolidayModel   $holidays;

    public function __construct()
    {
        $this->bookings  = new BookingModel();
        $this->rooms     = new RoomModel();
        $this->equipment = new EquipmentModel();
        $this->holidays  = new HolidayModel();
    }

    // ── My bookings ─────────────────────────────────────────────────

    public function index(): string
    {
        $user   = $this->currentUser();
        $filter = $this->request->getGet('status') ?? '';

        $items = $this->bookings->forUser((int) $user['id'], $filter);

        return view('bookings/index', $this->viewData([
            'pageTitle' => 'Minhas Reservas',
            'items'     => $items,
            'filter'    => $filter,
        ]));
    }

    // ── New booking ─────────────────────────────────────────────────

    public function create(): string
    {
        $institutionId = $this->institution['id'] ?? 0;

        $rooms     = $this->rooms->activeForInstitution($institutionId);
        $equipment = $this->equipment->activeForInstitution($institutionId);

        // Restore wizard state when returning after a validation error
        $restoreData = null;
        if (old('room_id')) {
            foreach ($rooms as $r) {
                if ((string) $r['id'] === (string) old('room_id')) {
                    $restoreData = [
                        'step'        => 3,
                        'searchDate'  => old('date', ''),
                        'searchStart' => old('start_time', ''),
                        'searchEnd'   => old('end_time', ''),
                        'selectedRoom' => [
                            'id'                       => (int) $r['id'],
                            'name'                     => $r['name'],
                            'code'                     => $r['code'] ?? '',
                            'capacity'                 => (int) $r['capacity'],
                            'allows_equipment_lending' => (bool) $r['allows_equipment_lending'],
                            'building_name'            => $r['building_name'] ?? '',
                            'floor'                    => $r['floor'] ?? '',
                        ],
                    ];
                    break;
                }
            }
        }

        return view('bookings/create', $this->viewData([
            'pageTitle'   => 'Nova Reserva',
            'rooms'       => $rooms,
            'equipment'   => $equipment,
            'restoreData' => $restoreData,
        ]));
    }

    public function availableRooms(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $date          = $this->request->getGet('date') ?? '';
        $startTime     = $this->request->getGet('start_time') ?? '';
        $endTime       = $this->request->getGet('end_time') ?? '';
        $equipmentIds  = array_values(array_filter(
            array_map('intval', (array) ($this->request->getGet('equipment_ids') ?? []))
        ));

        if (!$date || !$startTime || !$endTime || $startTime >= $endTime) {
            return $this->response->setJSON(['rooms' => [], 'equipment' => []]);
        }

        $rooms     = $this->rooms->availableForSlot($institutionId, $date, $startTime, $endTime, $equipmentIds);
        $equipment = $this->equipment->availableQuantitiesForSlot($institutionId, $date, $startTime, $endTime);

        return $this->response->setJSON(['rooms' => $rooms, 'equipment' => $equipment]);
    }

    public function store(): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $user          = $this->currentUser();

        $rules = [
            'room_id'        => 'required|integer',
            'title'          => 'required|max_length[300]',
            'date'           => 'required|valid_date[Y-m-d]',
            'start_time'     => 'required',
            'end_time'       => 'required',
            'attendees_count'=> 'required|integer|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $roomId    = (int) $this->request->getPost('room_id');
        $date      = $this->request->getPost('date');
        $startTime = $this->request->getPost('start_time');
        $endTime   = $this->request->getPost('end_time');

        // Validate time logic
        if ($startTime >= $endTime) {
            return redirect()->back()->withInput()
                ->with('error', 'O horário de término deve ser posterior ao horário de início.');
        }

        // Check future date
        if ($date < date('Y-m-d')) {
            return redirect()->back()->withInput()
                ->with('error', 'Não é possível fazer reservas para datas passadas.');
        }

        // Check holiday
        if ($this->holidays->isHoliday($institutionId, $date)) {
            $names = $this->holidays->getForDate($institutionId, $date);
            $label = implode(', ', $names);
            return redirect()->back()->withInput()
                ->with('error', "A data selecionada é feriado ({$label}). Escolha outra data.");
        }

        // Check conflict
        if ($this->bookings->hasConflict($roomId, $date, $startTime, $endTime)) {
            return redirect()->back()->withInput()
                ->with('error', 'Já existe uma reserva para este ambiente no horário selecionado. Escolha outro horário.');
        }

        // Verify room belongs to institution
        $room = $this->rooms->where('institution_id', $institutionId)->find($roomId);
        if (!$room || !$room['is_active']) {
            return redirect()->back()->withInput()
                ->with('error', 'Ambiente inválido ou inativo.');
        }

        // Validate operating hours
        $dayOfWeek = (int) date('w', strtotime($date));
        $ohRow = db_connect()->table('operating_hours')
            ->where('institution_id', $institutionId)
            ->where('day_of_week', $dayOfWeek)
            ->get()->getRowArray();

        if ($ohRow) {
            if (!$ohRow['is_open']) {
                return redirect()->back()->withInput()
                    ->with('error', 'O estabelecimento não funciona neste dia da semana.');
            }
            if ($ohRow['open_time'] && $startTime < $ohRow['open_time']) {
                return redirect()->back()->withInput()
                    ->with('error', 'Horário de início anterior à abertura (' . substr($ohRow['open_time'], 0, 5) . ').');
            }
            if ($ohRow['close_time'] && $endTime > $ohRow['close_time']) {
                return redirect()->back()->withInput()
                    ->with('error', 'Horário de término posterior ao fechamento (' . substr($ohRow['close_time'], 0, 5) . ').');
            }
        }

        // ── Booking policies ──────────────────────────────────────────────────────
        $policy = $this->getBookingSettings();

        // 1. Max days ahead
        $maxDate = date('Y-m-d', strtotime('+' . $policy['max_days_ahead'] . ' days'));
        if ($date > $maxDate) {
            return redirect()->back()->withInput()
                ->with('error', "Só é possível agendar com até {$policy['max_days_ahead']} dias de antecedência.");
        }

        // 2. Duration
        $durationMin = (strtotime($endTime) - strtotime($startTime)) / 60;
        if ($durationMin < $policy['min_duration_min']) {
            return redirect()->back()->withInput()
                ->with('error', "A reserva deve ter no mínimo {$policy['min_duration_min']} minutos de duração.");
        }
        if ($durationMin > $policy['max_duration_min']) {
            return redirect()->back()->withInput()
                ->with('error', "A reserva não pode exceder {$policy['max_duration_min']} minutos de duração.");
        }

        // 3. Max bookings per week
        if ($policy['max_bookings_per_week'] > 0) {
            $weekCount = $this->bookings->countForUserInWeek((int) $user['id'], $date);
            if ($weekCount >= $policy['max_bookings_per_week']) {
                return redirect()->back()->withInput()
                    ->with('error', "Limite de {$policy['max_bookings_per_week']} reservas por semana atingido.");
            }
        }

        $recurrenceType    = $this->request->getPost('recurrence_type') ?: 'none';
        $recurrenceEndDate = $this->request->getPost('recurrence_end_date') ?: null;

        $requiresApproval = $policy['requires_approval'];

        $bookingId = $this->bookings->insert([
            'institution_id'     => $institutionId,
            'user_id'            => $user['id'],
            'room_id'            => $roomId,
            'title'              => $this->request->getPost('title'),
            'description'        => $this->request->getPost('description') ?: null,
            'date'               => $date,
            'start_time'         => $startTime,
            'end_time'           => $endTime,
            'attendees_count'    => (int) $this->request->getPost('attendees_count'),
            'status'             => $requiresApproval ? 'pending' : 'approved',
            'reviewed_at'        => $requiresApproval ? null : date('Y-m-d H:i:s'),
            'recurrence_type'    => $recurrenceType,
            'recurrence_end_date'=> ($recurrenceType !== 'none') ? $recurrenceEndDate : null,
        ]);

        // Save equipment requests
        $equipIds = $this->request->getPost('equipment_ids') ?? [];
        if (!empty($equipIds) && $room['allows_equipment_lending']) {
            $db = db_connect();
            foreach ($equipIds as $eqId) {
                $qty = (int) ($this->request->getPost("equipment_qty_{$eqId}") ?? 1);
                if ($qty > 0) {
                    $db->table('booking_equipment')->insert([
                        'booking_id'   => $bookingId,
                        'equipment_id' => (int) $eqId,
                        'quantity'     => $qty,
                    ]);
                }
            }
        }

        service('audit')->log('booking.created', 'booking', (int) $bookingId);

        // Generate recurrence children
        if ($recurrenceType !== 'none' && $recurrenceEndDate && $recurrenceEndDate > $date) {
            $step       = ($recurrenceType === 'daily') ? '+1 day' : '+7 days';
            $current    = strtotime($step, strtotime($date));
            $endStamp   = strtotime($recurrenceEndDate);
            $attendees  = (int) $this->request->getPost('attendees_count');
            $title      = $this->request->getPost('title');
            $desc       = $this->request->getPost('description') ?: null;

            while ($current <= $endStamp) {
                $childDate = date('Y-m-d', $current);

                // Skip holidays and conflicts for children (silently)
                $isHoliday = $this->holidays->isHoliday($institutionId, $childDate);
                $hasConflict = $this->bookings->hasConflict($roomId, $childDate, $startTime, $endTime);

                if (!$isHoliday && !$hasConflict) {
                    $this->bookings->insert([
                        'institution_id'      => $institutionId,
                        'user_id'             => $user['id'],
                        'room_id'             => $roomId,
                        'title'               => $title,
                        'description'         => $desc,
                        'date'                => $childDate,
                        'start_time'          => $startTime,
                        'end_time'            => $endTime,
                        'attendees_count'     => $attendees,
                        'status'              => $requiresApproval ? 'pending' : 'approved',
                        'reviewed_at'         => $requiresApproval ? null : date('Y-m-d H:i:s'),
                        'recurrence_type'     => $recurrenceType,
                        'recurrence_end_date' => $recurrenceEndDate,
                        'recurrence_parent_id'=> (int) $bookingId,
                    ]);
                }

                $current = strtotime($step, $current);
            }
        }

        $notifBooking = $this->bookings->find($bookingId);
        if ($requiresApproval) {
            service('notification')->bookingCreated($notifBooking, $user, $room);
        } else {
            service('notification')->bookingApproved($notifBooking, $user, $room, null);
        }

        $successMsg = $requiresApproval
            ? 'Reserva enviada com sucesso! Aguarde a aprovação.'
            : 'Reserva criada e aprovada automaticamente!';

        return redirect()->to(base_url('reservas'))
            ->with('success', $successMsg);
    }

    // ── Show single booking ──────────────────────────────────────────

    public function show(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $user    = $this->currentUser();
        $booking = $this->bookings->find($id);

        if (!$booking || $booking['user_id'] != $user['id']) {
            return redirect()->to(base_url('reservas'))->with('error', 'Reserva não encontrada.');
        }

        $room = $this->rooms->find($booking['room_id']);

        $equipItems = db_connect()->table('booking_equipment be')
            ->select('be.quantity, e.name AS equipment_name, e.code')
            ->join('equipment e', 'e.id = be.equipment_id', 'left')
            ->where('be.booking_id', $id)
            ->get()->getResultArray();

        return view('bookings/show', $this->viewData([
            'pageTitle'  => 'Detalhe da Reserva',
            'booking'    => $booking,
            'room'       => $room,
            'equipItems' => $equipItems,
        ]));
    }

    // ── Cancel booking ───────────────────────────────────────────────

    public function cancel(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $user    = $this->currentUser();
        $booking = $this->bookings->find($id);

        if (!$booking || $booking['user_id'] != $user['id']) {
            return redirect()->to(base_url('reservas'))->with('error', 'Reserva não encontrada.');
        }

        if (!in_array($booking['status'], ['pending', 'approved'])) {
            return redirect()->to(base_url('reservas'))
                ->with('error', 'Esta reserva não pode ser cancelada.');
        }

        $this->bookings->update($id, [
            'status'           => 'cancelled',
            'cancelled_at'     => date('Y-m-d H:i:s'),
            'cancelled_reason' => $this->request->getPost('reason') ?: 'Cancelada pelo solicitante',
        ]);

        service('audit')->log('booking.cancelled', 'booking', $id);

        $reason = $this->bookings->find($id)['cancelled_reason'] ?? 'Cancelada pelo solicitante';
        $notifRoom = $this->rooms->find($booking['room_id']);
        service('notification')->bookingCancelled($booking, $user, $notifRoom, $reason);

        return redirect()->to(base_url('reservas'))
            ->with('success', 'Reserva cancelada.');
    }

    // ── Pending approval (staff) ─────────────────────────────────────

    public function pending(): string
    {
        $institutionId = $this->institution['id'] ?? 0;
        $items         = $this->bookings->pendingForInstitution($institutionId);

        // Approved bookings from today onwards (for absent marking)
        $approved = db_connect()->table('bookings bk')
            ->select('bk.*, r.name AS room_name, b.name AS building_name, u.name AS user_name')
            ->join('rooms r',     'r.id = bk.room_id',    'left')
            ->join('buildings b', 'b.id = r.building_id', 'left')
            ->join('users u',     'u.id = bk.user_id',   'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.status', 'approved')
            ->where('bk.date <=', date('Y-m-d'))
            ->where('bk.deleted_at IS NULL')
            ->orderBy('bk.date DESC, bk.start_time DESC')
            ->limit(30)
            ->get()->getResultArray();

        return view('bookings/pending', $this->viewData([
            'pageTitle' => 'Aprovação de Reservas',
            'items'     => $items,
            'approved'  => $approved,
        ]));
    }

    public function approve(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $reviewer = $this->currentUser();
        $booking  = $this->bookings->find($id);

        if (!$booking || $booking['status'] !== 'pending') {
            return redirect()->to(base_url('reservas/pendentes'))
                ->with('error', 'Reserva não encontrada ou não está pendente.');
        }

        $this->bookings->update($id, [
            'status'       => 'approved',
            'reviewed_by'  => $reviewer['id'],
            'reviewed_at'  => date('Y-m-d H:i:s'),
            'review_notes' => $this->request->getPost('notes') ?: null,
        ]);

        service('audit')->log('booking.approved', 'booking', $id);

        $notifBooking = $this->bookings->find($id);
        $notifUser    = (new \App\Models\UserModel())->find($notifBooking['user_id']);
        $notifRoom    = $this->rooms->find($notifBooking['room_id']);
        service('notification')->bookingApproved($notifBooking, $notifUser, $notifRoom, $reviewer);

        return redirect()->to(base_url('reservas/pendentes'))
            ->with('success', 'Reserva aprovada com sucesso.');
    }

    public function reject(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $reviewer = $this->currentUser();
        $booking  = $this->bookings->find($id);

        if (!$booking || $booking['status'] !== 'pending') {
            return redirect()->to(base_url('reservas/pendentes'))
                ->with('error', 'Reserva não encontrada ou não está pendente.');
        }

        $notes = trim($this->request->getPost('notes') ?? '');
        if (empty($notes)) {
            return redirect()->to(base_url('reservas/pendentes'))
                ->with('error', 'Informe o motivo da recusa.');
        }

        $this->bookings->update($id, [
            'status'       => 'rejected',
            'reviewed_by'  => $reviewer['id'],
            'reviewed_at'  => date('Y-m-d H:i:s'),
            'review_notes' => $notes,
        ]);

        service('audit')->log('booking.rejected', 'booking', $id);

        $notifBooking = $this->bookings->find($id);
        $notifUser    = (new \App\Models\UserModel())->find($notifBooking['user_id']);
        $notifRoom    = $this->rooms->find($notifBooking['room_id']);
        service('notification')->bookingRejected($notifBooking, $notifUser, $notifRoom, $notes);

        return redirect()->to(base_url('reservas/pendentes'))
            ->with('success', 'Reserva recusada.');
    }

    public function batchApprove(): \CodeIgniter\HTTP\RedirectResponse
    {
        $ids = $this->request->getPost('ids') ?? [];
        if (empty($ids)) {
            return redirect()->to(base_url('reservas/pendentes'))->with('error', 'Nenhuma reserva selecionada.');
        }

        $reviewer = $this->currentUser();
        $count = 0;

        foreach ($ids as $rawId) {
            $id = (int) $rawId;
            $booking = $this->bookings->find($id);
            if (!$booking || $booking['status'] !== 'pending' || $booking['institution_id'] != ($this->institution['id'] ?? 0)) {
                continue;
            }

            $this->bookings->update($id, [
                'status'      => 'approved',
                'reviewed_by' => $reviewer['id'],
                'reviewed_at' => date('Y-m-d H:i:s'),
                'review_notes'=> null,
            ]);

            service('audit')->log('booking.approved', 'booking', $id);

            $room = $this->rooms->find($booking['room_id']);
            service('notification')->bookingApproved($booking, $reviewer, $room);

            $count++;
        }

        return redirect()->to(base_url('reservas/pendentes'))
            ->with('success', "{$count} reserva(s) aprovada(s) com sucesso.");
    }

    public function batchReject(): \CodeIgniter\HTTP\RedirectResponse
    {
        $ids   = $this->request->getPost('ids')   ?? [];
        $notes = trim($this->request->getPost('notes') ?? '');

        if (empty($ids)) {
            return redirect()->to(base_url('reservas/pendentes'))->with('error', 'Nenhuma reserva selecionada.');
        }
        if ($notes === '') {
            return redirect()->to(base_url('reservas/pendentes'))->with('error', 'Informe o motivo da recusa para o lote.');
        }

        $reviewer = $this->currentUser();
        $count = 0;

        foreach ($ids as $rawId) {
            $id = (int) $rawId;
            $booking = $this->bookings->find($id);
            if (!$booking || $booking['status'] !== 'pending' || $booking['institution_id'] != ($this->institution['id'] ?? 0)) {
                continue;
            }

            $this->bookings->update($id, [
                'status'       => 'rejected',
                'reviewed_by'  => $reviewer['id'],
                'reviewed_at'  => date('Y-m-d H:i:s'),
                'review_notes' => $notes,
            ]);

            service('audit')->log('booking.rejected', 'booking', $id);

            $room = $this->rooms->find($booking['room_id']);
            service('notification')->bookingRejected($booking, $reviewer, $room);

            $count++;
        }

        return redirect()->to(base_url('reservas/pendentes'))
            ->with('success', "{$count} reserva(s) recusada(s) com sucesso.");
    }

    // ── Mark absent (staff) ─────────────────────────────────────────

    public function markAbsent(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $booking = $this->bookings->find($id);

        if (!$booking || $booking['status'] !== 'approved') {
            return redirect()->to(base_url('reservas/pendentes'))
                ->with('error', 'Reserva não encontrada ou não está aprovada.');
        }

        $this->bookings->update($id, ['status' => 'absent']);
        service('audit')->log('booking.absent', 'booking', $id);

        return redirect()->to(base_url('reservas/pendentes'))
            ->with('success', 'Reserva marcada como ausente.');
    }

    // ── Booking policy helpers ───────────────────────────────────────

    private function getBookingSettings(): array
    {
        $s = $this->institution['settings_decoded']['booking'] ?? [];
        return [
            'max_days_ahead'        => (int)  ($s['max_days_ahead']        ?? 90),
            'min_duration_min'      => (int)  ($s['min_duration_min']      ?? 30),
            'max_duration_min'      => (int)  ($s['max_duration_min']      ?? 480),
            'requires_approval'     => (bool) ($s['requires_approval']     ?? true),
            'max_bookings_per_week' => (int)  ($s['max_bookings_per_week'] ?? 0),
        ];
    }

    // ── Authenticated agenda ─────────────────────────────────────────

    public function agenda(): string
    {
        $institutionId = $this->institution['id'] ?? 0;

        $rooms     = $this->rooms->activeForInstitution($institutionId);
        $buildings = db_connect()->table('buildings')
            ->where('institution_id', $institutionId)
            ->where('is_active', 1)
            ->where('deleted_at IS NULL')
            ->orderBy('name')
            ->get()->getResultArray();

        return view('bookings/agenda', $this->viewData([
            'pageTitle' => 'Agenda',
            'rooms'     => $rooms,
            'buildings' => $buildings,
        ]));
    }

    // ── Availability API (JSON) ──────────────────────────────────────

    /**
     * Returns booked slots for a room on a given date (for front-end availability hints).
     * GET /reservas/disponibilidade?room_id=X&date=Y-m-d
     */
    public function availability(): \CodeIgniter\HTTP\ResponseInterface
    {
        $roomId = (int) $this->request->getGet('room_id');
        $date   = $this->request->getGet('date');

        if (!$roomId || !$date) {
            return $this->response->setJSON(['slots' => []]);
        }

        $bookings = $this->bookings->forRoomOnDate($roomId, $date);

        $slots = array_map(fn($b) => [
            'start'  => $b['start_time'],
            'end'    => $b['end_time'],
            'status' => $b['status'],
        ], $bookings);

        return $this->response->setJSON(['slots' => $slots]);
    }
}
