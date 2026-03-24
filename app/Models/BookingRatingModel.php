<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingRatingModel extends Model
{
    protected $table      = 'booking_ratings';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'institution_id',
        'booking_id',
        'user_id',
        'rating',
        'comment',
    ];

    protected $useTimestamps = true;
    protected $updatedField  = '';   // no updated_at column
    protected $createdField  = 'created_at';

    protected $validationRules = [
        'booking_id' => 'required|integer',
        'user_id'    => 'required|integer',
        'rating'     => 'required|integer|greater_than[0]|less_than[6]',
    ];

    /**
     * Find rating for a specific booking.
     */
    public function forBooking(int $bookingId): ?array
    {
        return $this->where('booking_id', $bookingId)->first();
    }

    /**
     * Average rating and count for a room.
     */
    public function statsForRoom(int $roomId): array
    {
        $row = $this->db->table('booking_ratings br')
            ->select('AVG(br.rating) AS avg_rating, COUNT(br.id) AS total_ratings')
            ->join('bookings bk', 'bk.id = br.booking_id')
            ->where('bk.room_id', $roomId)
            ->get()->getRowArray();

        return [
            'avg_rating'    => $row ? round((float) $row['avg_rating'], 1) : null,
            'total_ratings' => $row ? (int) $row['total_ratings'] : 0,
        ];
    }

    /**
     * Average ratings per room for an institution (for admin list).
     * Returns assoc: room_id => ['avg_rating', 'total_ratings']
     */
    public function avgByRoomForInstitution(int $institutionId): array
    {
        $rows = $this->db->table('booking_ratings br')
            ->select('bk.room_id, AVG(br.rating) AS avg_rating, COUNT(br.id) AS total_ratings')
            ->join('bookings bk', 'bk.id = br.booking_id')
            ->where('br.institution_id', $institutionId)
            ->groupBy('bk.room_id')
            ->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['room_id']] = [
                'avg_rating'    => round((float) $r['avg_rating'], 1),
                'total_ratings' => (int) $r['total_ratings'],
            ];
        }
        return $map;
    }

    /**
     * Bookings approved yesterday that have no rating yet (for CLI reminder).
     */
    public function bookingsNeedingReview(int $institutionId): array
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        return $this->db->table('bookings bk')
            ->select('bk.id AS booking_id, bk.title, bk.date, bk.start_time, bk.end_time,
                      u.id AS user_id, u.name AS user_name, u.email AS user_email,
                      r.name AS room_name')
            ->join('users u',  'u.id = bk.user_id',  'left')
            ->join('rooms r',  'r.id = bk.room_id',  'left')
            ->join('booking_ratings rt', 'rt.booking_id = bk.id', 'left')
            ->where('bk.institution_id', $institutionId)
            ->where('bk.status', 'approved')
            ->where('bk.date', $yesterday)
            ->where('bk.deleted_at IS NULL')
            ->where('rt.id IS NULL')
            ->get()->getResultArray();
    }
}
