<?php

namespace App\Models;

use CodeIgniter\Model;

class RoomBlackoutModel extends Model
{
    protected $table      = 'room_blackouts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'institution_id',
        'room_id',
        'title',
        'reason',
        'starts_at',
        'ends_at',
        'creator_id',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';   // sem updated_at
    protected $deletedField  = '';   // sem soft delete

    // ── Queries ─────────────────────────────────────────────────────

    /**
     * Retorna bloqueios ativos que sobrepoem o período informado para o ambiente.
     * Considera também bloqueios globais (room_id IS NULL) da instituição.
     */
    public function overlaps(int $roomId, int $institutionId, string $startsAt, string $endsAt): array
    {
        return $this->db->table('room_blackouts')
            ->where('institution_id', $institutionId)
            ->groupStart()
                ->where('room_id', $roomId)
                ->orWhere('room_id IS NULL')
            ->groupEnd()
            ->where('starts_at <', $endsAt)
            ->where('ends_at >', $startsAt)
            ->get()->getResultArray();
    }

    /**
     * Retorna todos os bloqueios futuros/ativos para um ambiente.
     */
    public function activeForRoom(int $roomId, int $institutionId): array
    {
        return $this->db->table('room_blackouts')
            ->where('institution_id', $institutionId)
            ->groupStart()
                ->where('room_id', $roomId)
                ->orWhere('room_id IS NULL')
            ->groupEnd()
            ->where('ends_at >', date('Y-m-d H:i:s'))
            ->orderBy('starts_at', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Lista todos os bloqueios da instituição, com nome do ambiente e do criador.
     */
    public function forInstitution(int $institutionId): array
    {
        return $this->db->table('room_blackouts rb')
            ->select('rb.*, r.name AS room_name, r.code AS room_code, u.name AS creator_name')
            ->join('rooms r',  'r.id = rb.room_id',    'left')
            ->join('users u',  'u.id = rb.creator_id', 'left')
            ->where('rb.institution_id', $institutionId)
            ->orderBy('rb.starts_at', 'DESC')
            ->get()->getResultArray();
    }
}
