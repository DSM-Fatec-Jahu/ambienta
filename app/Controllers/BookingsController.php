<?php

namespace App\Controllers;

use App\Models\BookingCommentModel;
use App\Models\BookingModel;
use App\Models\BookingRatingModel;
use App\Models\BookingResourceModel;
use App\Models\RoomBlackoutModel;
use App\Models\RoomModel;
use App\Models\EquipmentModel;
use App\Models\ResourceModel;
use App\Models\HolidayModel;
use App\Models\WaitlistModel;

class BookingsController extends BaseController
{
    private BookingCommentModel  $comments;
    private BookingModel         $bookings;
    private BookingRatingModel   $ratings;
    private BookingResourceModel $bookingResources;
    private RoomBlackoutModel    $blackouts;
    private RoomModel            $rooms;
    private EquipmentModel       $equipment;
    private ResourceModel        $resources;
    private HolidayModel         $holidays;
    private WaitlistModel        $waitlist;

    public function __construct()
    {
        $this->comments         = new BookingCommentModel();
        $this->bookings         = new BookingModel();
        $this->ratings          = new BookingRatingModel();
        $this->bookingResources = new BookingResourceModel();
        $this->blackouts        = new RoomBlackoutModel();
        $this->rooms            = new RoomModel();
        $this->equipment        = new EquipmentModel();
        $this->resources        = new ResourceModel();
        $this->holidays         = new HolidayModel();
        $this->waitlist         = new WaitlistModel();
    }

    // ── My bookings ─────────────────────────────────────────────────

    public function index(): string
    {
        $user          = $this->currentUser();
        $institutionId = $this->institution['id'] ?? 0;

        // RN-R08 / Sprint R5 — overdue return banner for requesters
        $overdueReturnCount = 0;
        if ($user['role'] === 'role_requester') {
            $resourcePolicy     = $this->getResourceSettings();
            $overdueReturnCount = $this->bookingResources->countOverdueReturns(
                (int) $user['id'],
                $institutionId,
                $resourcePolicy['resource_return_deadline_hours']
            );
        }

        $rooms = $this->rooms->activeForInstitution($institutionId);

        return view('bookings/index', $this->viewData([
            'pageTitle'          => 'Minhas Reservas',
            'overdueReturnCount' => $overdueReturnCount,
            'rooms'              => $rooms,
        ]));
    }

    public function data(): \CodeIgniter\HTTP\ResponseInterface
    {
        $user   = $this->currentUser();
        $userId = (int) $user['id'];

        $page   = max(1, (int) ($this->request->getGet('page')    ?? 1));
        $q      = trim($this->request->getGet('q')                ?? '');
        $status = trim($this->request->getGet('status')           ?? '');
        $roomId = (int) ($this->request->getGet('room_id')        ?? 0);
        $limit  = in_array((int) ($this->request->getGet('limit') ?? 10), [10, 25, 50, 100])
                      ? (int) $this->request->getGet('limit') : 10;
        $offset = ($page - 1) * $limit;

        $rows  = $this->bookings->search($userId, $q, $status, $roomId, $limit, $offset);
        $total = $this->bookings->searchCount($userId, $q, $status, $roomId);

        foreach ($rows as &$r) {
            $r['id']             = (int)  $r['id'];
            $r['room_id']        = (int)  $r['room_id'];
            $r['attendees_count'] = (int) $r['attendees_count'];
        }
        unset($r);

        return $this->response->setJSON([
            'data'  => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => (int) ceil($total / $limit),
            'limit' => $limit,
        ]);
    }

    public function exportXlsx(): \CodeIgniter\HTTP\ResponseInterface
    {
        $user   = $this->currentUser();
        $userId = (int) $user['id'];

        $q      = trim($this->request->getGet('q')         ?? '');
        $status = trim($this->request->getGet('status')    ?? '');
        $roomId = (int) ($this->request->getGet('room_id') ?? 0);

        $rows = $this->bookings->search($userId, $q, $status, $roomId, 5000, 0);

        $statusLabel = static fn(string $s): string => match($s) {
            'approved'  => 'Aprovada',
            'rejected'  => 'Recusada',
            'cancelled' => 'Cancelada',
            'absent'    => 'Ausente',
            default     => 'Pendente',
        };

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reservas');

        $sheet->fromArray(['Título', 'Ambiente', 'Prédio', 'Data', 'Horário', 'Status'], null, 'A1');
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                       'startColor' => ['rgb' => '1E40AF']],
        ]);

        $row = 2;
        foreach ($rows as $r) {
            $horario = substr($r['start_time'], 0, 5) . ' – ' . substr($r['end_time'], 0, 5);
            $date    = $r['date'] ? date('d/m/Y', strtotime($r['date'])) : '';
            $sheet->fromArray([
                $r['title'],
                $r['room_name']     ?? '',
                $r['building_name'] ?? '',
                $date,
                $horario,
                $statusLabel($r['status']),
            ], null, 'A' . $row);

            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':F' . $row)
                    ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }
            $row++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="reservas_' . date('Y-m-d') . '.xlsx"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody($content);
    }

    public function exportPdf(): void
    {
        $user   = $this->currentUser();
        $userId = (int) $user['id'];

        $q      = trim($this->request->getGet('q')         ?? '');
        $status = trim($this->request->getGet('status')    ?? '');
        $roomId = (int) ($this->request->getGet('room_id') ?? 0);

        $rows = $this->bookings->search($userId, $q, $status, $roomId, 5000, 0);

        $html = view('bookings/pdf_export', [
            'rows'        => $rows,
            'institution' => $this->institution,
            'generatedAt' => date('d/m/Y H:i'),
        ]);

        $options = new \Dompdf\Options();
        $options->setChroot(ROOTPATH);
        $options->setIsRemoteEnabled(false);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('reservas_' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
        exit;
    }

    // ── New booking ─────────────────────────────────────────────────

    public function create(): string
    {
        $institutionId = $this->institution['id'] ?? 0;

        $rooms = $this->rooms->activeForInstitution($institutionId);

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

        $forUsers = [];
        $user = $this->currentUser();
        if ($user['role'] !== 'role_requester') {
            $userModel = new \App\Models\UserModel();
            $forUsers = $userModel
                ->where('institution_id', $institutionId)
                ->where('is_active', 1)
                ->orderBy('name', 'ASC')
                ->findAll();
        }

        return view('bookings/create', $this->viewData([
            'pageTitle'   => 'Nova Reserva',
            'rooms'       => $rooms,
            'restoreData' => $restoreData,
            'forUsers'    => $forUsers,
        ]));
    }

    public function availableRooms(): \CodeIgniter\HTTP\ResponseInterface
    {
        $institutionId = $this->institution['id'] ?? 0;
        $date          = $this->request->getGet('date')       ?? '';
        $startTime     = $this->request->getGet('start_time') ?? '';
        $endTime       = $this->request->getGet('end_time')   ?? '';

        // New parameter — text search (RN-R12)
        $resourceTerms = array_values(array_filter(
            array_map('trim', (array) ($this->request->getGet('resource_terms') ?? []))
        ));

        // Legacy parameter — search by ID (Admin/Técnico)
        $equipmentIds = array_values(array_filter(
            array_map('intval', (array) ($this->request->getGet('equipment_ids') ?? []))
        ));

        if (!$date || !$startTime || !$endTime || $startTime >= $endTime) {
            return $this->response->setJSON(['rooms' => [], 'equipment' => [], 'room_resources' => []]);
        }

        $rooms = $this->rooms->availableForSlot(
            $institutionId, $date, $startTime, $endTime,
            $equipmentIds,
            $resourceTerms
        );

        // Attach resources — grouped (no id/code) for requester, detailed for admin (RN-R13)
        $user          = $this->currentUser();
        $isRequester   = ($user['role'] === 'role_requester');
        $seen          = [];
        $roomResources = [];

        foreach ($rooms as &$room) {
            if ($isRequester) {
                $allocated         = $this->resources->getGroupedByRoom((int) $room['id']);
                $room['resources'] = $allocated;
                foreach ($allocated as $res) {
                    $key = $res['name'] . '||' . ($res['category'] ?? '');
                    if (!isset($seen[$key])) {
                        $seen[$key]      = true;
                        $roomResources[] = $res;
                    }
                }
            } else {
                $allocated         = $this->resources->allocatedToRoom((int) $room['id']);
                $room['resources'] = array_map(fn($r) => [
                    'id'                 => (int) $r['id'],
                    'name'               => $r['name'],
                    'code'               => $r['code'] ?? '',
                    'allocated_quantity' => (int) $r['allocated_quantity'],
                ], $allocated);
                foreach ($room['resources'] as $res) {
                    if (!isset($seen[$res['id']])) {
                        $seen[$res['id']] = true;
                        $roomResources[]  = $res;
                    }
                }
            }
        }
        unset($room);
        usort($roomResources, fn($a, $b) => strcmp($a['name'], $b['name']));

        // General-stock resources for Step 3 of the form
        if ($isRequester) {
            $resources = $this->resources->getGroupedGeneralStock(
                $institutionId, $date, $startTime, $endTime
            );
        } else {
            $resources = $this->resources->availableForBookingSlot(
                $institutionId, $date, $startTime, $endTime
            );
        }

        return $this->response->setJSON([
            'rooms'          => $rooms,
            'equipment'      => $resources,
            'room_resources' => $roomResources,
        ]);
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

        // Check blackouts
        $startsAt    = $date . ' ' . $startTime;
        $endsAt      = $date . ' ' . $endTime;
        $blackoutHit = $this->blackouts->overlaps($roomId, $institutionId, $startsAt, $endsAt);
        if (!empty($blackoutHit)) {
            $bt = $blackoutHit[0];
            return redirect()->back()->withInput()
                ->with('error', "O ambiente está bloqueado neste período: \"{$bt['title']}\". Escolha outro horário.");
        }

        // Verify room belongs to institution
        $room = $this->rooms->where('institution_id', $institutionId)->find($roomId);
        if (!$room || !$room['is_active']) {
            return redirect()->back()->withInput()
                ->with('error', 'Ambiente inválido ou inativo.');
        }

        // Maintenance check
        if (!empty($room['maintenance_mode'])) {
            $until = $room['maintenance_until'];
            $msg = 'Este ambiente está em manutenção';
            if ($until) {
                $msg .= ' até ' . date('d/m/Y', strtotime($until));
            }
            return redirect()->back()->withInput()->with('error', $msg . '.');
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
            $startNorm = strlen($startTime) === 5 ? $startTime . ':00' : $startTime;
            $endNorm   = strlen($endTime) === 5 ? $endTime . ':00' : $endTime;
            if ($ohRow['open_time'] && $startNorm < $ohRow['open_time']) {
                return redirect()->back()->withInput()
                    ->with('error', 'Horário de início anterior à abertura (' . substr($ohRow['open_time'], 0, 5) . ').');
            }
            if ($ohRow['close_time'] && $endNorm > $ohRow['close_time']) {
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

        // 4. RN-R08 — block requester if they have overdue unreturned resources
        if ($user['role'] === 'role_requester') {
            $resourcePolicy = $this->getResourceSettings();
            if ($resourcePolicy['resource_return_block_requester']
                && $this->bookingResources->hasOverdueReturns(
                    (int) $user['id'],
                    $institutionId,
                    $resourcePolicy['resource_return_deadline_hours']
                )
            ) {
                return redirect()->back()->withInput()
                    ->with('error', 'Você possui recurso(s) com devolução pendente vencida. Regularize antes de criar novas reservas.');
            }
        }

        $recurrenceType    = $this->request->getPost('recurrence_type') ?: 'none';
        $recurrenceEndDate = $this->request->getPost('recurrence_end_date') ?: null;

        $requiresApproval = $policy['requires_approval'];

        // Handle booking on behalf of another user
        $bookedByUserId = null;
        $bookingUserId  = (int) $user['id'];
        if ($user['role'] !== 'role_requester') {
            $forUserId = (int) ($this->request->getPost('for_user_id') ?: 0);
            if ($forUserId && $forUserId !== (int) $user['id']) {
                $bookingUserId  = $forUserId;
                $bookedByUserId = (int) $user['id'];
            }
        }

        $bookingId = $this->bookings->insert([
            'institution_id'     => $institutionId,
            'owner_id'           => $bookingUserId,
            'creator_id'         => $bookedByUserId,
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
            'qr_token'           => bin2hex(random_bytes(32)),
        ]);

        // Save resource requests — only resources allocated to the booked room are accepted
        $equipIds = array_values(array_filter(
            array_map('intval', (array) ($this->request->getPost('equipment_ids') ?? []))
        ));
        if (!empty($equipIds)) {
            $roomResources    = $this->resources->allocatedToRoom($roomId);
            $roomResourceIds  = array_column($roomResources, 'id');

            $db = db_connect();
            foreach ($equipIds as $eqId) {
                if (!in_array($eqId, $roomResourceIds, true)) {
                    continue; // reject resources not allocated to this room
                }
                $qty = (int) ($this->request->getPost("equipment_qty_{$eqId}") ?? 1);
                if ($qty > 0) {
                    $db->table('booking_resources')->insert([
                        'booking_id'  => $bookingId,
                        'resource_id' => $eqId,
                        'quantity'    => $qty,
                        'status'      => 'approved', // room resources don't need technician approval
                        'created_at'  => date('Y-m-d H:i:s'),
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        // Process generic resource requests via AllocationService (RN-R14/R15/R16)
        $resourceRequests = $this->request->getPost('resource_requests') ?? [];
        if (!empty($resourceRequests)) {
            $allocationService = new \App\Services\ResourceAllocationService();
            $allocationErrors  = [];

            foreach ($resourceRequests as $req) {
                $reqName     = trim($req['name']     ?? '');
                $reqCategory = trim($req['category'] ?? '');
                $reqQty      = (int) ($req['quantity'] ?? 0);

                if ($reqQty <= 0 || (empty($reqName) && empty($reqCategory))) {
                    continue;
                }

                try {
                    $allocationService->resolve(
                        $institutionId,
                        $reqName,
                        $reqCategory,
                        $reqQty,
                        $date,
                        $startTime,
                        $endTime,
                        (int) $bookingId
                    );
                } catch (\App\Exceptions\ResourceUnavailableException $e) {
                    $allocationErrors[] = $e->getMessage();
                }
            }

            if (!empty($allocationErrors)) {
                session()->setFlashdata('resource_warnings', $allocationErrors);
            }
        }

        service('audit')->log('booking.created', 'booking', (int) $bookingId);

        if ($bookedByUserId) {
            service('audit')->log('booking.created_on_behalf', 'booking', (int) $bookingId, null, [
                'for_user_id'       => $bookingUserId,
                'booked_by_user_id' => $bookedByUserId,
            ]);
        }

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
                        'owner_id'            => $bookingUserId,
                        'creator_id'          => $bookedByUserId,
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

        $isStaff = $user['role'] !== 'role_requester';
        $isOwner = $booking && (int) $booking['owner_id'] === (int) $user['id'];

        if (!$booking
            || (!$isOwner && !$isStaff)
            || (int) ($booking['institution_id'] ?? 0) !== (int) ($this->institution['id'] ?? 0)
        ) {
            return redirect()->to(base_url('reservas'))->with('error', 'Reserva não encontrada.');
        }

        $room = $this->rooms->find($booking['room_id']);

        $bookedBy = null;
        if (!empty($booking['creator_id'])) {
            $userModel = new \App\Models\UserModel();
            $bookedBy = $userModel->find((int) $booking['creator_id']);
        }

        // RN-R05: load resources with full status for return button eligibility
        $equipItems     = $this->bookingResources->forBooking($id);
        $bookingEndDt   = ($booking['date'] ?? '') . ' ' . ($booking['end_time'] ?? '23:59:59');
        $bookingEnded   = strtotime($bookingEndDt) <= time();

        $existingRating = $this->ratings->forBooking($id);

        // Eligible to rate: approved, date in the past, no rating yet
        $canRate = $booking['status'] === 'approved'
            && $booking['date'] < date('Y-m-d')
            && $existingRating === null;

        // Eligible to check in: approved, today's booking, no check-in yet
        $checkinSettings    = $this->getCheckinSettings();
        $canCheckIn         = false;
        $checkinWindowStart = null;
        if ($booking['status'] === 'approved'
            && $booking['date'] === date('Y-m-d')
            && empty($booking['checkin_at'])
        ) {
            $windowMin   = $checkinSettings['checkin_window_min'];
            $bookingStart = strtotime($booking['date'] . ' ' . $booking['start_time']);
            $bookingEnd   = strtotime($booking['date'] . ' ' . $booking['end_time']);
            $windowOpens  = $bookingStart - ($windowMin * 60);
            $now          = time();
            $checkinWindowStart = date('H:i', $windowOpens);
            $canCheckIn   = ($now >= $windowOpens && $now <= $bookingEnd);
        }

        // QR check-in URL
        $qrCheckinUrl = !empty($booking['qr_token'])
            ? base_url('checkin/' . $booking['qr_token'])
            : null;

        // Recurring series siblings
        $seriesSiblings = [];
        if (!empty($booking['recurrence_parent_id'])) {
            $seriesSiblings = $this->bookings->forSeries((int) $booking['recurrence_parent_id']);
        } elseif (!empty($booking['recurrence_type']) && $booking['recurrence_type'] !== 'none') {
            $seriesSiblings = $this->bookings->forSeries((int) $booking['id']);
        }

        $bookingComments = $this->comments->forBooking($id);

        return view('bookings/show', $this->viewData([
            'pageTitle'          => 'Detalhe da Reserva',
            'booking'            => $booking,
            'room'               => $room,
            'bookedBy'           => $bookedBy,
            'equipItems'         => $equipItems,
            'bookingEnded'       => $bookingEnded,
            'existingRating'     => $existingRating,
            'canRate'            => $canRate,
            'canCheckIn'         => $canCheckIn,
            'checkinWindowStart' => $checkinWindowStart,
            'checkinSettings'    => $checkinSettings,
            'qrCheckinUrl'       => $qrCheckinUrl,
            'seriesSiblings'     => $seriesSiblings,
            'bookingComments'    => $bookingComments,
            'isStaff'            => $isStaff,
        ]));
    }

    // ── Add comment to booking ───────────────────────────────────────

    public function addComment(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $user    = $this->currentUser();
        $booking = $this->bookings->find($id);

        $isStaff = $user['role'] !== 'role_requester';
        $isOwner = $booking && (int) $booking['owner_id'] === (int) $user['id'];

        if (!$booking
            || (!$isOwner && !$isStaff)
            || (int) ($booking['institution_id'] ?? 0) !== (int) ($this->institution['id'] ?? 0)
        ) {
            return redirect()->to(base_url('reservas'))->with('error', 'Reserva não encontrada.');
        }

        $body = trim($this->request->getPost('body') ?? '');
        if (empty($body)) {
            return redirect()->to(base_url('reservas/' . $id))
                ->with('error', 'O comentário não pode estar vazio.');
        }
        if (mb_strlen($body) > 1000) {
            return redirect()->to(base_url('reservas/' . $id))
                ->with('error', 'Comentário muito longo (máximo 1000 caracteres).');
        }

        $this->comments->insert([
            'institution_id' => $booking['institution_id'],
            'booking_id'     => $id,
            'author_id'      => $user['id'],
            'body'           => $body,
        ]);

        service('audit')->log('booking.comment_added', 'booking', $id, ['commenter' => $user['id']]);

        return redirect()->to(base_url('reservas/' . $id) . '#comentarios')
            ->with('success', 'Comentário adicionado.');
    }

    // ── Edit pending booking ─────────────────────────────────────────

    public function edit(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $user    = $this->currentUser();
        $booking = $this->bookings->find($id);

        if (!$booking || $booking['owner_id'] != $user['id']) {
            return redirect()->to(base_url('reservas'))->with('error', 'Reserva não encontrada.');
        }

        if ($booking['status'] !== 'pending') {
            return redirect()->to(base_url('reservas/' . $id))
                ->with('error', 'Apenas reservas pendentes podem ser editadas.');
        }

        $institutionId = $this->institution['id'] ?? 0;
        $rooms         = $this->rooms->activeForInstitution($institutionId);
        $room          = $this->rooms->find($booking['room_id']);
        $user          = $this->currentUser();
        $isRequester   = $user['role'] === 'role_requester';

        // Bloco 1: grouped room resources for requester view (RN-R13)
        $groupedRoomResources = $isRequester
            ? $this->resources->getGroupedByRoom((int) $booking['room_id'])
            : $this->resources->allocatedToRoom((int) $booking['room_id']);

        // Bloco 2: existing general-stock resource allocations for this booking
        $existingResources = $this->bookingResources->forBooking((int) $booking['id']);

        return view('bookings/edit', $this->viewData([
            'pageTitle'            => 'Editar Reserva',
            'booking'              => $booking,
            'rooms'                => $rooms,
            'room'                 => $room,
            'isRequester'          => $isRequester,
            'groupedRoomResources' => $groupedRoomResources,
            'existingResources'    => $existingResources,
        ]));
    }

    public function update(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $user    = $this->currentUser();
        $booking = $this->bookings->find($id);

        if (!$booking || $booking['owner_id'] != $user['id']) {
            return redirect()->to(base_url('reservas'))->with('error', 'Reserva não encontrada.');
        }

        if ($booking['status'] !== 'pending') {
            return redirect()->to(base_url('reservas/' . $id))
                ->with('error', 'Apenas reservas pendentes podem ser editadas.');
        }

        $institutionId = $this->institution['id'] ?? 0;
        $isStaff       = $user['role'] !== 'role_requester';

        // Basic fields validation
        $rules = [
            'title'          => 'required|max_length[300]',
            'attendees_count'=> 'required|integer|greater_than[0]',
        ];

        if ($isStaff) {
            $rules['room_id']    = 'required|integer';
            $rules['date']       = 'required|valid_date[Y-m-d]';
            $rules['start_time'] = 'required';
            $rules['end_time']   = 'required';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $title          = $this->request->getPost('title');
        $description    = $this->request->getPost('description') ?: null;
        $attendeesCount = (int) $this->request->getPost('attendees_count');

        $updateData = [
            'title'           => $title,
            'description'     => $description,
            'attendees_count' => $attendeesCount,
        ];

        // Staff can also change room / date / time
        if ($isStaff) {
            $roomId    = (int) $this->request->getPost('room_id');
            $date      = $this->request->getPost('date');
            $startTime = $this->request->getPost('start_time');
            $endTime   = $this->request->getPost('end_time');

            if ($startTime >= $endTime) {
                return redirect()->back()->withInput()
                    ->with('error', 'O horário de término deve ser posterior ao horário de início.');
            }

            if ($date < date('Y-m-d')) {
                return redirect()->back()->withInput()
                    ->with('error', 'Não é possível agendar para datas passadas.');
            }

            // Conflict check (excluding self)
            if ($this->bookings->hasConflict($roomId, $date, $startTime, $endTime, $id)) {
                return redirect()->back()->withInput()
                    ->with('error', 'Já existe uma reserva para este ambiente no horário selecionado.');
            }

            // Blackout check
            $blackoutHit = $this->blackouts->overlaps($roomId, $institutionId, $date . ' ' . $startTime, $date . ' ' . $endTime);
            if (!empty($blackoutHit)) {
                return redirect()->back()->withInput()
                    ->with('error', 'O ambiente está bloqueado neste período: "' . $blackoutHit[0]['title'] . '".');
            }

            // Room maintenance check
            $room = $this->rooms->where('institution_id', $institutionId)->find($roomId);
            if (!$room || !$room['is_active']) {
                return redirect()->back()->withInput()->with('error', 'Ambiente inválido ou inativo.');
            }
            if (!empty($room['maintenance_mode'])) {
                $msg = 'Este ambiente está em manutenção';
                if ($room['maintenance_until']) {
                    $msg .= ' até ' . date('d/m/Y', strtotime($room['maintenance_until']));
                }
                return redirect()->back()->withInput()->with('error', $msg . '.');
            }

            $updateData['room_id']    = $roomId;
            $updateData['date']       = $date;
            $updateData['start_time'] = $startTime;
            $updateData['end_time']   = $endTime;
        }

        $old = [
            'title'           => $booking['title'],
            'description'     => $booking['description'],
            'attendees_count' => $booking['attendees_count'],
            'room_id'         => $booking['room_id'],
            'date'            => $booking['date'],
            'start_time'      => $booking['start_time'],
            'end_time'        => $booking['end_time'],
        ];

        $this->bookings->update($id, $updateData);

        service('audit')->log('booking.updated', 'booking', $id, $old, $updateData);

        // Process generic resource requests via AllocationService (RN-R14)
        $resourceRequests = $this->request->getPost('resource_requests') ?? [];
        if (!empty($resourceRequests)) {
            $effectiveDate  = $updateData['date']       ?? $booking['date'];
            $effectiveStart = $updateData['start_time'] ?? $booking['start_time'];
            $effectiveEnd   = $updateData['end_time']   ?? $booking['end_time'];

            $allocationService = new \App\Services\ResourceAllocationService();
            $allocationErrors  = [];

            foreach ($resourceRequests as $req) {
                $reqName     = trim($req['name']     ?? '');
                $reqCategory = trim($req['category'] ?? '');
                $reqQty      = (int) ($req['quantity'] ?? 0);

                if ($reqQty <= 0 || (empty($reqName) && empty($reqCategory))) {
                    continue;
                }

                try {
                    $allocationService->resolve(
                        $institutionId,
                        $reqName,
                        $reqCategory,
                        $reqQty,
                        $effectiveDate,
                        $effectiveStart,
                        $effectiveEnd,
                        $id
                    );
                } catch (\App\Exceptions\ResourceUnavailableException $e) {
                    $allocationErrors[] = $e->getMessage();
                }
            }

            if (!empty($allocationErrors)) {
                session()->setFlashdata('resource_warnings', $allocationErrors);
            }
        }

        return redirect()->to(base_url('reservas/' . $id))
            ->with('success', 'Reserva atualizada com sucesso.');
    }

    // ── Rate booking ─────────────────────────────────────────────────

    public function rate(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $user    = $this->currentUser();
        $booking = $this->bookings->find($id);

        if (!$booking || $booking['owner_id'] != $user['id']) {
            return redirect()->to(base_url('reservas'))->with('error', 'Reserva não encontrada.');
        }

        if ($booking['status'] !== 'approved' || $booking['date'] >= date('Y-m-d')) {
            return redirect()->to(base_url('reservas/' . $id))
                ->with('error', 'Esta reserva não pode ser avaliada.');
        }

        if ($this->ratings->forBooking($id) !== null) {
            return redirect()->to(base_url('reservas/' . $id))
                ->with('error', 'Esta reserva já foi avaliada.');
        }

        $rating = (int) $this->request->getPost('rating');
        if ($rating < 1 || $rating > 5) {
            return redirect()->to(base_url('reservas/' . $id))
                ->with('error', 'Avaliação inválida. Selecione entre 1 e 5 estrelas.');
        }

        $this->ratings->insert([
            'institution_id' => $this->institution['id'] ?? 0,
            'booking_id'     => $id,
            'rater_id'       => $user['id'],
            'rating'         => $rating,
            'comment'        => trim($this->request->getPost('comment') ?? '') ?: null,
        ]);

        service('audit')->log('booking.rated', 'booking', $id);

        return redirect()->to(base_url('reservas/' . $id))
            ->with('success', 'Obrigado pela sua avaliação!');
    }

    // ── Export iCal (.ics) ───────────────────────────────────────────

    public function exportIcal(): \CodeIgniter\HTTP\ResponseInterface
    {
        $user          = $this->currentUser();
        $institutionId = $this->institution['id'] ?? 0;

        $bookings = $this->bookings->forUser((int) $user['id'], 'approved');

        $lines   = [];
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//Ambienta//Reservas//PT';
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = 'METHOD:PUBLISH';
        $lines[] = 'X-WR-CALNAME:Minhas Reservas - ' . ($this->institution['name'] ?? 'Ambienta');
        $lines[] = 'X-WR-TIMEZONE:America/Sao_Paulo';

        foreach ($bookings as $bk) {
            $dtStart = str_replace(['-', ':'], '', $bk['date']) . 'T' . str_replace(':', '', substr($bk['start_time'], 0, 5)) . '00';
            $dtEnd   = str_replace(['-', ':'], '', $bk['date']) . 'T' . str_replace(':', '', substr($bk['end_time'],   0, 5)) . '00';
            $uid     = 'booking-' . $bk['id'] . '@ambienta';
            $now     = gmdate('Ymd\THis\Z');
            $summary = addcslashes($bk['title'], '\\,;');
            $location= addcslashes($bk['room_name'] ?? '', '\\,;');

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . $uid;
            $lines[] = 'DTSTAMP:' . $now;
            $lines[] = 'DTSTART;TZID=America/Sao_Paulo:' . $dtStart;
            $lines[] = 'DTEND;TZID=America/Sao_Paulo:' . $dtEnd;
            $lines[] = 'SUMMARY:' . $summary;
            if ($location) {
                $lines[] = 'LOCATION:' . $location;
            }
            if (!empty($bk['description'])) {
                $lines[] = 'DESCRIPTION:' . addcslashes(str_replace(["\r\n", "\n", "\r"], '\\n', $bk['description']), '\\,;');
            }
            $lines[] = 'STATUS:CONFIRMED';
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        $ical = implode("\r\n", $lines) . "\r\n";

        return $this->response
            ->setHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="reservas.ics"')
            ->setBody($ical);
    }

    // ── Cancel booking ───────────────────────────────────────────────

    public function cancel(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $user    = $this->currentUser();
        $booking = $this->bookings->find($id);

        if (!$booking || $booking['owner_id'] != $user['id']) {
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

        $updatedBooking = $this->bookings->find($id);
        $reason    = $updatedBooking['cancelled_reason'] ?? 'Cancelada pelo solicitante';
        $notifRoom = $this->rooms->find($booking['room_id']);
        service('notification')->bookingCancelled($booking, $user, $notifRoom, $reason);

        // Notify next person on waitlist if any
        $this->waitlist->notifyNext($notifRoom, $booking);

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
            ->join('users u',     'u.id = bk.owner_id',  'left')
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
            'reviewer_id'  => $reviewer['id'],
            'reviewed_at'  => date('Y-m-d H:i:s'),
            'review_notes' => $this->request->getPost('notes') ?: null,
        ]);

        service('audit')->log('booking.approved', 'booking', $id);

        $notifBooking = $this->bookings->find($id);
        $notifUser    = (new \App\Models\UserModel())->find($notifBooking['owner_id']);
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
            'reviewer_id'  => $reviewer['id'],
            'reviewed_at'  => date('Y-m-d H:i:s'),
            'review_notes' => $notes,
        ]);

        service('audit')->log('booking.rejected', 'booking', $id);

        $notifBooking = $this->bookings->find($id);
        $notifUser    = (new \App\Models\UserModel())->find($notifBooking['owner_id']);
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
                'reviewer_id' => $reviewer['id'],
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
                'reviewer_id'  => $reviewer['id'],
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

    // ── Check-in ─────────────────────────────────────────────────────

    public function checkIn(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $user    = $this->currentUser();
        $booking = $this->bookings->find($id);

        if (!$booking || $booking['owner_id'] != $user['id']) {
            return redirect()->to(base_url('reservas'))->with('error', 'Reserva não encontrada.');
        }

        if ($booking['status'] !== 'approved') {
            return redirect()->to(base_url('reservas/' . $id))
                ->with('error', 'Esta reserva não está aprovada.');
        }

        if (!empty($booking['checkin_at'])) {
            return redirect()->to(base_url('reservas/' . $id))
                ->with('error', 'Check-in já registrado para esta reserva.');
        }

        if ($booking['date'] !== date('Y-m-d')) {
            return redirect()->to(base_url('reservas/' . $id))
                ->with('error', 'O check-in só pode ser realizado no dia da reserva.');
        }

        $settings    = $this->getCheckinSettings();
        $windowMin   = $settings['checkin_window_min'];
        $bookingStart = strtotime($booking['date'] . ' ' . $booking['start_time']);
        $bookingEnd   = strtotime($booking['date'] . ' ' . $booking['end_time']);
        $windowOpens  = $bookingStart - ($windowMin * 60);
        $now          = time();

        if ($now < $windowOpens) {
            $opens = date('H:i', $windowOpens);
            return redirect()->to(base_url('reservas/' . $id))
                ->with('error', "O check-in abre às {$opens} (até {$windowMin} min antes do início).");
        }

        if ($now > $bookingEnd) {
            return redirect()->to(base_url('reservas/' . $id))
                ->with('error', 'O horário da reserva já encerrou. Check-in não é mais possível.');
        }

        $this->bookings->update($id, ['checkin_at' => date('Y-m-d H:i:s')]);
        service('audit')->log('booking.checkin', 'booking', $id);

        return redirect()->to(base_url('reservas/' . $id))
            ->with('success', 'Check-in realizado com sucesso!');
    }

    // ── QR Check-in (public — token acts as credential) ──────────────

    /**
     * GET /checkin/:token — validates QR token and performs check-in.
     * Public route: no auth required (the 64-char random token is the credential).
     */
    public function qrCheckin(string $token): string
    {
        $booking = $this->bookings->findByQrToken($token);

        if (!$booking) {
            return view('bookings/qr_checkin', [
                'success' => false,
                'message' => 'QR Code inválido ou reserva não encontrada.',
            ]);
        }

        if ($booking['status'] !== 'approved') {
            return view('bookings/qr_checkin', [
                'success' => false,
                'message' => 'Esta reserva não está aprovada (status: ' . $booking['status'] . ').',
                'booking' => $booking,
            ]);
        }

        if (!empty($booking['checkin_at'])) {
            return view('bookings/qr_checkin', [
                'success' => false,
                'message' => 'Check-in já registrado às ' . date('H:i', strtotime($booking['checkin_at'])) . '.',
                'booking' => $booking,
            ]);
        }

        if ($booking['date'] !== date('Y-m-d')) {
            return view('bookings/qr_checkin', [
                'success' => false,
                'message' => 'O check-in via QR só pode ser realizado no dia da reserva (' . date('d/m/Y', strtotime($booking['date'])) . ').',
                'booking' => $booking,
            ]);
        }

        // Load institution settings for check-in window
        $institution = db_connect()->table('institutions')
            ->where('id', $booking['institution_id'])
            ->get()->getRowArray();

        $settings  = json_decode($institution['settings'] ?? '{}', true)['booking'] ?? [];
        $windowMin = (int) ($settings['checkin_window_min'] ?? 15);

        $bookingStart = strtotime($booking['date'] . ' ' . $booking['start_time']);
        $bookingEnd   = strtotime($booking['date'] . ' ' . $booking['end_time']);
        $windowOpens  = $bookingStart - ($windowMin * 60);
        $now          = time();

        if ($now < $windowOpens) {
            return view('bookings/qr_checkin', [
                'success' => false,
                'message' => 'Check-in via QR disponível a partir das ' . date('H:i', $windowOpens) . '.',
                'booking' => $booking,
            ]);
        }

        if ($now > $bookingEnd) {
            return view('bookings/qr_checkin', [
                'success' => false,
                'message' => 'O horário da reserva já encerrou. Check-in não é mais possível.',
                'booking' => $booking,
            ]);
        }

        $this->bookings->update($booking['id'], ['checkin_at' => date('Y-m-d H:i:s')]);
        service('audit')->log('booking.checkin', 'booking', (int) $booking['id']);

        $room = $this->rooms->find($booking['room_id']);

        return view('bookings/qr_checkin', [
            'success' => true,
            'message' => 'Check-in realizado com sucesso!',
            'booking' => $booking,
            'room'    => $room,
        ]);
    }

    // ── Cancel recurring series ───────────────────────────────────────

    /**
     * POST /reservas/:id/cancelar-serie
     * Cancels all future (pending/approved) occurrences in the series, including this one.
     */
    public function cancelSeries(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $user    = $this->currentUser();
        $booking = $this->bookings->find($id);

        if (!$booking || $booking['owner_id'] != $user['id']) {
            return redirect()->to(base_url('reservas'))->with('error', 'Reserva não encontrada.');
        }

        if (empty($booking['recurrence_type']) || $booking['recurrence_type'] === 'none') {
            return redirect()->to(base_url('reservas/' . $id))
                ->with('error', 'Esta reserva não faz parte de uma série recorrente.');
        }

        // Determine the parent ID of the series
        $parentId = !empty($booking['recurrence_parent_id'])
            ? (int) $booking['recurrence_parent_id']
            : (int) $booking['id'];

        // Get all future siblings (date >= today)
        $siblings = $this->bookings->forSeries($parentId);
        $today    = date('Y-m-d');
        $count    = 0;

        foreach ($siblings as $s) {
            if ($s['date'] < $today) {
                continue; // leave past occurrences intact
            }
            if (!in_array($s['status'], ['pending', 'approved'])) {
                continue;
            }

            $this->bookings->update($s['id'], [
                'status'           => 'cancelled',
                'cancelled_at'     => date('Y-m-d H:i:s'),
                'cancelled_reason' => 'Série cancelada pelo solicitante.',
            ]);
            service('audit')->log('booking.cancelled', 'booking', (int) $s['id']);
            $count++;
        }

        return redirect()->to(base_url('reservas'))
            ->with('success', "{$count} ocorrência(s) futuras da série canceladas.");
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

    private function getCheckinSettings(): array
    {
        $s = $this->institution['settings_decoded']['booking'] ?? [];
        return [
            'checkin_window_min'      => (int)  ($s['checkin_window_min']      ?? 15),
            'auto_cancel_no_checkin'  => (bool) ($s['auto_cancel_no_checkin']  ?? false),
        ];
    }

    /** RN-R08 — resource return policy settings. */
    private function getResourceSettings(): array
    {
        $s = $this->institution['settings_decoded']['resources'] ?? [];
        return [
            'resource_return_deadline_hours'  => (int)  ($s['resource_return_deadline_hours']  ?? 1),
            'resource_return_block_requester' => (bool) ($s['resource_return_block_requester'] ?? true),
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

    // ── Waitlist ─────────────────────────────────────────────────────

    /** GET /reservas/lista-espera — show current user's waitlist entries */
    public function myWaitlist(): string
    {
        $user    = $this->currentUser();
        $entries = $this->waitlist->forUser((int) $user['id']);

        return view('bookings/waitlist', $this->viewData([
            'pageTitle' => 'Minha Lista de Espera',
            'entries'   => $entries,
        ]));
    }

    /** POST /reservas/lista-espera — join waitlist for a slot */
    public function joinWaitlist(): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $user          = $this->currentUser();

        $roomId    = (int)   $this->request->getPost('room_id');
        $date      = (string)$this->request->getPost('date');
        $startsAt  = (string)$this->request->getPost('start_time');
        $endsAt    = (string)$this->request->getPost('end_time');
        $notes     = trim($this->request->getPost('notes') ?? '');

        if (!$roomId || !$date || !$startsAt || !$endsAt) {
            return redirect()->back()->with('error', 'Dados inválidos para entrar na lista de espera.');
        }

        if ($date < date('Y-m-d')) {
            return redirect()->back()->with('error', 'Não é possível entrar na lista de espera para datas passadas.');
        }

        $room = $this->rooms->where('institution_id', $institutionId)->find($roomId);
        if (!$room) {
            return redirect()->back()->with('error', 'Ambiente não encontrado.');
        }

        if ($this->waitlist->hasEntry($roomId, $date, $startsAt, $endsAt, (int) $user['id'])) {
            return redirect()->back()->with('error', 'Você já está na lista de espera para este horário.');
        }

        $this->waitlist->addEntry($institutionId, $roomId, $date, $startsAt, $endsAt, (int) $user['id'], $notes);

        return redirect()->to(base_url('reservas/lista-espera'))
            ->with('success', 'Você entrou na lista de espera. Será notificado quando uma vaga abrir.');
    }

    /** POST /reservas/lista-espera/:id/sair — leave waitlist */
    public function leaveWaitlist(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $user = $this->currentUser();

        if (!$this->waitlist->removeEntry($id, (int) $user['id'])) {
            return redirect()->to(base_url('reservas/lista-espera'))
                ->with('error', 'Entrada não encontrada ou você não tem permissão para removê-la.');
        }

        return redirect()->to(base_url('reservas/lista-espera'))
            ->with('success', 'Você saiu da lista de espera.');
    }
}
