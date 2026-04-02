<?php

namespace App\Models;

use CodeIgniter\Model;

class BuildingModel extends Model
{
    protected $table          = 'buildings';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'institution_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name'           => 'required|max_length[200]',
        'institution_id' => 'required|integer',
    ];

    public function forInstitution(int $institutionId): static
    {
        return $this->where('institution_id', $institutionId);
    }

    public function activeForInstitution(int $institutionId): array
    {
        return $this->where('institution_id', $institutionId)
                    ->where('is_active', 1)
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }

    // ── Search / pagination ──────────────────────────────────────────

    public function search(int $institutionId, string $q, int $status, int $limit, int $offset): array
    {
        return $this->_searchQuery($institutionId, $q, $status)
            ->orderBy('t.name', 'ASC')
            ->limit($limit, $offset)
            ->get()->getResultArray();
    }

    public function searchCount(int $institutionId, string $q, int $status): int
    {
        return (int) $this->_searchQuery($institutionId, $q, $status)
            ->countAllResults();
    }

    private function _searchQuery(int $institutionId, string $q, int $status): \CodeIgniter\Database\BaseBuilder
    {
        $qb = $this->db->table('buildings t')
            ->select('t.*')
            ->where('t.institution_id', $institutionId)
            ->where('t.deleted_at IS NULL');

        if ($q !== '') {
            $qb->groupStart()
                ->like('t.name', $q)
                ->orLike('t.code', $q)
                ->orLike('t.description', $q)
            ->groupEnd();
        }

        if ($status === 1)     { $qb->where('t.is_active', 1); }
        elseif ($status === 2) { $qb->where('t.is_active', 0); }

        return $qb;
    }
}
