<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'institution_id',
        'name',
        'email',
        'cellphone',
        'password_hash',
        'google_id',
        'avatar_url',
        'avatar_path',
        'role',
        'is_active',
        'login_attempts',
        'locked_until',
        'password_reset_token',
        'password_reset_expires_at',
        'last_login_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name'           => 'required|max_length[200]',
        'email'          => 'required|valid_email|max_length[320]',
        'institution_id' => 'required|integer',
    ];

    // ── Finders ────────────────────────────────────────────────────

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    public function findByGoogleId(string $googleId): ?array
    {
        return $this->where('google_id', $googleId)->first();
    }

    public function findByResetToken(string $token): ?array
    {
        return $this
            ->where('password_reset_token', $token)
            ->where('password_reset_expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }

    // ── Login attempt tracking ──────────────────────────────────────

    /**
     * Returns true if the account is currently locked out.
     */
    public function isLockedOut(array $user): bool
    {
        if (empty($user['locked_until'])) {
            return false;
        }

        return strtotime($user['locked_until']) > time();
    }

    /**
     * Increments failed login attempts; locks for 15 min after 5 failures.
     */
    public function incrementLoginAttempts(int $userId): void
    {
        $user = $this->find($userId);
        if (!$user) {
            return;
        }

        $attempts = (int) $user['login_attempts'] + 1;
        $data     = ['login_attempts' => $attempts];

        if ($attempts >= 5) {
            $data['locked_until'] = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        }

        $this->update($userId, $data);
    }

    /**
     * Resets login attempts and clears lockout after a successful login.
     */
    public function resetLoginAttempts(int $userId): void
    {
        $this->update($userId, [
            'login_attempts' => 0,
            'locked_until'   => null,
            'last_login_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    // ── Password reset ──────────────────────────────────────────────

    public function setResetToken(int $userId, string $token): void
    {
        $this->update($userId, [
            'password_reset_token'      => $token,
            'password_reset_expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);
    }

    public function clearResetToken(int $userId): void
    {
        $this->update($userId, [
            'password_reset_token'      => null,
            'password_reset_expires_at' => null,
        ]);
    }

    // ── Search / pagination ──────────────────────────────────────────

    public function search(int $institutionId, string $q, string $role, int $status, int $limit, int $offset): array
    {
        return $this->_searchQuery($institutionId, $q, $role, $status)
            ->orderBy('t.name', 'ASC')
            ->limit($limit, $offset)
            ->get()->getResultArray();
    }

    public function searchCount(int $institutionId, string $q, string $role, int $status): int
    {
        return (int) $this->_searchQuery($institutionId, $q, $role, $status)
            ->countAllResults();
    }

    private function _searchQuery(int $institutionId, string $q, string $role, int $status): \CodeIgniter\Database\BaseBuilder
    {
        $qb = $this->db->table('users t')
            ->select('t.id, t.name, t.email, t.role, t.is_active, t.google_id, t.avatar_url, t.created_at, t.last_login_at')
            ->where('t.institution_id', $institutionId)
            ->where('t.deleted_at IS NULL');

        if ($q !== '') {
            $qb->groupStart()
                ->like('t.name', $q)
                ->orLike('t.email', $q)
            ->groupEnd();
        }

        if ($role !== '')  { $qb->where('t.role', $role); }
        if ($status === 1) { $qb->where('t.is_active', 1); }
        elseif ($status === 2) { $qb->where('t.is_active', 0); }

        return $qb;
    }

    // ── SSO helper ──────────────────────────────────────────────────

    /**
     * Creates or updates a user from Google OAuth2 data.
     * New users get role_requester by default.
     */
    public function upsertFromGoogle(array $googleData, int $institutionId): array
    {
        $existing = $this->findByGoogleId($googleData['google_id']);

        if ($existing) {
            $this->update($existing['id'], [
                'name'       => $googleData['name'],
                'avatar_url' => $googleData['avatar_url'] ?? null,
                'email'      => $googleData['email'],
            ]);
            return $this->find($existing['id']);
        }

        // Check by email (account already exists, link Google)
        $byEmail = $this->findByEmail($googleData['email']);
        if ($byEmail) {
            $this->update($byEmail['id'], [
                'google_id'  => $googleData['google_id'],
                'avatar_url' => $googleData['avatar_url'] ?? null,
            ]);
            return $this->find($byEmail['id']);
        }

        // Brand new user
        $id = $this->insert([
            'institution_id' => $institutionId,
            'name'           => $googleData['name'],
            'email'          => $googleData['email'],
            'google_id'      => $googleData['google_id'],
            'avatar_url'     => $googleData['avatar_url'] ?? null,
            'role'           => 'role_requester',
            'is_active'      => 1,
        ]);

        return $this->find($id);
    }
}
