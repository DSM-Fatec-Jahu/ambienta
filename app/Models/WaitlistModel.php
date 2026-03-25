<?php

namespace App\Models;

use CodeIgniter\Model;

class WaitlistModel extends Model
{
    protected $table         = 'booking_waitlists';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;

    protected $allowedFields = [
        'institution_id',
        'room_id',
        'date',
        'starts_at',
        'ends_at',
        'waiter_id',
        'notes',
        'notified_at',
        'created_at',
    ];

    /**
     * Returns all waitlist entries for a specific slot, ordered by creation time (FIFO).
     */
    public function forSlot(int $roomId, string $date, string $startsAt, string $endsAt): array
    {
        return $this->where('room_id', $roomId)
                    ->where('date', $date)
                    ->where('starts_at', $startsAt)
                    ->where('ends_at', $endsAt)
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }

    /**
     * Returns all waitlist entries for a user, with room info (upcoming only).
     */
    public function forUser(int $userId): array
    {
        return db_connect()
            ->table('booking_waitlists wl')
            ->select('wl.*, r.name AS room_name, r.code AS room_code, b.name AS building_name')
            ->join('rooms r',     'r.id = wl.room_id',    'left')
            ->join('buildings b', 'b.id = r.building_id', 'left')
            ->where('wl.waiter_id', $userId)
            ->where('wl.date >=', date('Y-m-d'))
            ->orderBy('wl.date ASC, wl.starts_at ASC')
            ->get()->getResultArray();
    }

    /**
     * Checks whether a user is already on the waitlist for a given slot.
     */
    public function hasEntry(int $roomId, string $date, string $startsAt, string $endsAt, int $userId): bool
    {
        return $this->where('room_id', $roomId)
                    ->where('date', $date)
                    ->where('starts_at', $startsAt)
                    ->where('ends_at', $endsAt)
                    ->where('waiter_id', $userId)
                    ->countAllResults() > 0;
    }

    /**
     * Adds a user to the waitlist for a slot.
     */
    public function addEntry(
        int    $institutionId,
        int    $roomId,
        string $date,
        string $startsAt,
        string $endsAt,
        int    $userId,
        string $notes = ''
    ): int {
        return (int) $this->insert([
            'institution_id' => $institutionId,
            'room_id'        => $roomId,
            'date'           => $date,
            'starts_at'      => $startsAt,
            'ends_at'        => $endsAt,
            'waiter_id'      => $userId,
            'notes'          => $notes ?: null,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Removes a user's waitlist entry (only if they own it).
     */
    public function removeEntry(int $id, int $userId): bool
    {
        return $this->where('id', $id)
                    ->where('waiter_id', $userId)
                    ->delete() !== false;
    }

    /**
     * Notifies the next person in the waitlist for a slot.
     * Marks their entry as notified and creates an in-app notification + email.
     *
     * @param array $room     The room array (id, name, …)
     * @param array $booking  The freed booking (for date/time reference)
     */
    public function notifyNext(array $room, array $booking): void
    {
        $entries = $this->forSlot(
            (int) $room['id'],
            $booking['date'],
            $booking['start_time'],
            $booking['end_time']
        );

        // Find the first un-notified entry
        $next = null;
        foreach ($entries as $entry) {
            if (empty($entry['notified_at'])) {
                $next = $entry;
                break;
            }
        }

        if ($next === null) {
            return;
        }

        // Mark as notified
        $this->update($next['id'], ['notified_at' => date('Y-m-d H:i:s')]);

        // Load the waiting user
        $user = (new \App\Models\UserModel())->find((int) $next['waiter_id']);
        if (!$user) {
            return;
        }

        // Send notification (email + in-app)
        service('notification')->waitlistAvailable($next, $user, $room, $booking);
    }
}
