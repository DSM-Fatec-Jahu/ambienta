<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Cancela automaticamente reservas aprovadas que não tiveram check-in registrado
 * e cujo horário já encerrou, quando a opção auto_cancel_no_checkin está habilitada.
 *
 * Uso: php spark booking:auto-cancel
 */
class AutoCancelNoCheckin extends BaseCommand
{
    protected $group       = 'Booking';
    protected $name        = 'booking:auto-cancel';
    protected $description = 'Cancela reservas aprovadas sem check-in após o término do horário (quando habilitado nas configurações).';

    public function run(array $params): void
    {
        $now = date('Y-m-d H:i:s');
        CLI::write("Verificando reservas sem check-in até {$now}...", 'yellow');

        $db = db_connect();

        // Busca todas as instituições que têm auto_cancel_no_checkin habilitado
        $institutions = $db->table('institutions')
            ->where('deleted_at IS NULL')
            ->get()->getResultArray();

        $totalCancelled = 0;

        foreach ($institutions as $institution) {
            $settings = json_decode($institution['settings'] ?? '{}', true) ?? [];
            $autoCancelEnabled = (bool) ($settings['booking']['auto_cancel_no_checkin'] ?? false);

            if (!$autoCancelEnabled) {
                continue;
            }

            $institutionId = $institution['id'];

            // Reservas aprovadas, sem check-in, cujo horário já encerrou
            $today = date('Y-m-d');
            $rows = $db->table('bookings bk')
                ->select('bk.id, bk.title, bk.date, bk.start_time, bk.end_time,
                          r.name AS room_name, b.name AS building_name,
                          u.name AS user_name, u.email AS user_email')
                ->join('rooms r',    'r.id = bk.room_id',    'left')
                ->join('buildings b','b.id = r.building_id', 'left')
                ->join('users u',    'u.id = bk.user_id',    'left')
                ->where('bk.institution_id', $institutionId)
                ->where('bk.status', 'approved')
                ->where('bk.checkin_at IS NULL')
                ->where('bk.deleted_at IS NULL')
                ->groupStart()
                    ->where('bk.date <', $today)
                    ->orGroupStart()
                        ->where('bk.date', $today)
                        ->where("CONCAT(bk.date, ' ', bk.end_time) <", $now)
                    ->groupEnd()
                ->groupEnd()
                ->get()->getResultArray();

            if (empty($rows)) {
                continue;
            }

            $rowCount = count($rows);
            CLI::write("[{$institution['name']}] {$rowCount} reserva(s) sem check-in encontrada(s).", 'cyan');

            $notif = service('notification');

            foreach ($rows as $row) {
                $db->table('bookings')
                    ->where('id', $row['id'])
                    ->update([
                        'status'           => 'cancelled',
                        'cancelled_at'     => $now,
                        'cancelled_reason' => 'Cancelada automaticamente: ausência de check-in.',
                    ]);

                service('audit')->log('booking.auto_cancel', 'booking', (int) $row['id']);

                $booking = [
                    'id'         => $row['id'],
                    'title'      => $row['title'],
                    'date'       => $row['date'],
                    'start_time' => $row['start_time'],
                    'end_time'   => $row['end_time'],
                ];
                $user = [
                    'name'  => $row['user_name'],
                    'email' => $row['user_email'],
                ];
                $room = [
                    'name'          => $row['room_name'],
                    'building_name' => $row['building_name'],
                ];

                if ($row['user_email']) {
                    $notif->bookingAutoCancel($booking, $user, $room);
                }

                CLI::write("  ✓ Cancelada reserva #{$row['id']} ({$row['user_email']})", 'green');
                $totalCancelled++;
            }
        }

        CLI::write("Concluído: {$totalCancelled} reserva(s) cancelada(s).", $totalCancelled > 0 ? 'yellow' : 'green');
    }
}
