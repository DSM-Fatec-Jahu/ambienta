<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\RoomBlackoutModel;
use App\Models\RoomModel;

class BlackoutsController extends BaseController
{
    private RoomBlackoutModel $blackouts;
    private RoomModel         $rooms;

    public function __construct()
    {
        $this->blackouts = new RoomBlackoutModel();
        $this->rooms     = new RoomModel();
    }

    public function index(): string
    {
        $institutionId = $this->institution['id'] ?? 0;

        $items = $this->blackouts->forInstitution($institutionId);
        $rooms = $this->rooms->activeForInstitution($institutionId);

        return view('admin/blackouts/index', $this->viewData([
            'pageTitle' => 'Bloqueios de Ambiente',
            'items'     => $items,
            'rooms'     => $rooms,
        ]));
    }

    public function store(): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;
        $user          = $this->currentUser();

        $rules = [
            'title'     => 'required|max_length[200]',
            'starts_at' => 'required',
            'ends_at'   => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $startsAt = $this->request->getPost('starts_at');
        $endsAt   = $this->request->getPost('ends_at');

        if ($endsAt <= $startsAt) {
            return redirect()->back()->withInput()
                ->with('error', 'O término deve ser posterior ao início do bloqueio.');
        }

        $roomId = $this->request->getPost('room_id') ?: null;

        // Ensure room belongs to institution if specified
        if ($roomId !== null) {
            $room = $this->rooms->where('institution_id', $institutionId)->find((int) $roomId);
            if (!$room) {
                return redirect()->back()->withInput()
                    ->with('error', 'Ambiente inválido.');
            }
        }

        $this->blackouts->insert([
            'institution_id' => $institutionId,
            'room_id'        => $roomId !== null ? (int) $roomId : null,
            'title'          => $this->request->getPost('title'),
            'reason'         => trim($this->request->getPost('reason') ?? '') ?: null,
            'starts_at'      => $startsAt,
            'ends_at'        => $endsAt,
            'created_by'     => $user['id'],
        ]);

        service('audit')->log('blackout.created', 'room_blackout', 0);

        return redirect()->to(base_url('admin/bloqueios'))
            ->with('success', 'Bloqueio criado com sucesso.');
    }

    public function delete(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $institutionId = $this->institution['id'] ?? 0;

        $item = $this->blackouts->where('institution_id', $institutionId)->find($id);
        if (!$item) {
            return redirect()->to(base_url('admin/bloqueios'))
                ->with('error', 'Bloqueio não encontrado.');
        }

        $this->blackouts->delete($id);

        service('audit')->log('blackout.deleted', 'room_blackout', $id);

        return redirect()->to(base_url('admin/bloqueios'))
            ->with('success', 'Bloqueio removido com sucesso.');
    }
}
