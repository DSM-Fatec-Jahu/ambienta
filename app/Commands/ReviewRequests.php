<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\BookingRatingModel;
use App\Models\RoomModel;

/**
 * Sends review-request emails for bookings that happened yesterday and have no rating.
 *
 * Usage:
 *   php spark booking:review-requests
 *
 * Schedule (cron example):
 *   0 9 * * * /usr/bin/php /var/www/html/spark booking:review-requests >> /var/log/ambienta-reviews.log 2>&1
 */
class ReviewRequests extends BaseCommand
{
    protected $group       = 'Booking';
    protected $name        = 'booking:review-requests';
    protected $description = 'Sends review-request e-mails for bookings completed yesterday.';

    public function run(array $params): void
    {
        $db          = db_connect();
        $ratingModel = new BookingRatingModel();
        $roomModel   = new RoomModel();

        // Collect distinct institution IDs
        $institutions = $db->table('institutions')
            ->select('id')
            ->where('deleted_at IS NULL')
            ->get()->getResultArray();

        $totalSent   = 0;
        $totalErrors = 0;

        foreach ($institutions as $inst) {
            $institutionId = (int) $inst['id'];
            $pending       = $ratingModel->bookingsNeedingReview($institutionId);

            foreach ($pending as $row) {
                $booking = [
                    'id'             => $row['booking_id'],
                    'title'          => $row['title'],
                    'date'           => $row['date'],
                    'start_time'     => $row['start_time'],
                    'end_time'       => $row['end_time'],
                ];

                $user = [
                    'id'    => $row['user_id'],
                    'name'  => $row['user_name'],
                    'email' => $row['user_email'],
                ];

                $room = ['name' => $row['room_name']];

                $ok = service('notification')->bookingReviewRequest($booking, $user, $room);

                if ($ok) {
                    $totalSent++;
                    CLI::write("  Sent to {$user['email']} (booking #{$booking['id']})", 'green');
                } else {
                    $totalErrors++;
                    CLI::write("  Failed for {$user['email']} (booking #{$booking['id']})", 'red');
                }
            }
        }

        CLI::write("Done. Sent: {$totalSent} | Errors: {$totalErrors}", 'cyan');
    }
}
