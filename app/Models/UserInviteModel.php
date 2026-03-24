<?php

namespace App\Models;

use CodeIgniter\Model;

class UserInviteModel extends Model
{
    protected $table      = 'user_invites';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'institution_id',
        'invited_by',
        'email',
        'role',
        'token',
        'expires_at',
        'accepted_at',
    ];

    protected $useTimestamps = false;

    // ── Finders ─────────────────────────────────────────────────────────────

    public function findByToken(string $token): ?array
    {
        return $this->where('token', $token)->first();
    }

    public function findPendingForEmail(string $email, int $institutionId): ?array
    {
        return $this
            ->where('email', $email)
            ->where('institution_id', $institutionId)
            ->where('accepted_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Creates a new invite token (64-char hex) expiring in 72 hours.
     * Invalidates any previous pending invite for the same email.
     */
    public function createInvite(int $institutionId, int $invitedBy, string $email, string $role): array
    {
        // Invalidate old pending invites for this email in this institution
        $this->where('institution_id', $institutionId)
             ->where('email', $email)
             ->where('accepted_at', null)
             ->set('expires_at', date('Y-m-d H:i:s'))
             ->update();

        $token = bin2hex(random_bytes(32));

        $id = $this->insert([
            'institution_id' => $institutionId,
            'invited_by'     => $invitedBy,
            'email'          => $email,
            'role'           => $role,
            'token'          => $token,
            'expires_at'     => date('Y-m-d H:i:s', strtotime('+72 hours')),
        ]);

        return $this->find($id);
    }

    /** Marks an invite as accepted. */
    public function accept(int $id): void
    {
        $this->update($id, ['accepted_at' => date('Y-m-d H:i:s')]);
    }

    /** Returns true if the invite is still valid (not expired, not accepted). */
    public function isValid(array $invite): bool
    {
        if (!empty($invite['accepted_at'])) {
            return false;
        }
        return strtotime($invite['expires_at']) > time();
    }
}
