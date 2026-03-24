<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingCommentModel extends Model
{
    protected $table      = 'booking_comments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'institution_id',
        'booking_id',
        'user_id',
        'body',
    ];

    protected $useTimestamps = true;
    protected $updatedField  = '';   // no updated_at column
    protected $createdField  = 'created_at';

    /**
     * Returns all comments for a booking, including the commenter's name/avatar.
     */
    public function forBooking(int $bookingId): array
    {
        return $this->db->table('booking_comments bc')
            ->select('bc.*, u.name AS author_name, u.avatar_path, u.role AS author_role')
            ->join('users u', 'u.id = bc.user_id', 'left')
            ->where('bc.booking_id', $bookingId)
            ->orderBy('bc.created_at', 'ASC')
            ->get()->getResultArray();
    }
}
