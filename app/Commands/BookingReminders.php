<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class BookingReminders extends BaseCommand
{
    protected $group       = 'Booking';
    protected $name        = 'booking:reminders';
    protected $description = 'Envia e-mails de lembrete D-1 para reservas aprovadas agendadas para amanhã.';

    public function run(array $params): void
    {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        CLI::write("Verificando reservas para {$tomorrow}...", 'yellow');

        $db = db_connect();

        $rows = $db->table('bookings bk')
            ->select('bk.id, bk.title, bk.date, bk.start_time, bk.end_time, bk.attendees_count,
                      r.name AS room_name, b.name AS building_name,
                      u.name AS user_name, u.email AS user_email')
            ->join('rooms r',    'r.id = bk.room_id',    'left')
            ->join('buildings b','b.id = r.building_id', 'left')
            ->join('users u',    'u.id = bk.user_id',    'left')
            ->where('bk.status', 'approved')
            ->where('bk.date', $tomorrow)
            ->where('bk.deleted_at IS NULL')
            ->where('u.email IS NOT NULL')
            ->get()->getResultArray();

        if (empty($rows)) {
            CLI::write('Nenhuma reserva aprovada encontrada para amanhã.', 'green');
            return;
        }

        CLI::write(count($rows) . ' reserva(s) encontrada(s).', 'cyan');

        $sent   = 0;
        $failed = 0;
        $notif  = service('notification');

        foreach ($rows as $row) {
            try {
                $booking = [
                    'id'              => $row['id'],
                    'title'           => $row['title'],
                    'date'            => $row['date'],
                    'start_time'      => $row['start_time'],
                    'end_time'        => $row['end_time'],
                    'attendees_count' => $row['attendees_count'],
                ];
                $user = [
                    'name'  => $row['user_name'],
                    'email' => $row['user_email'],
                ];
                $room = [
                    'name'          => $row['room_name'],
                    'building_name' => $row['building_name'],
                ];

                if ($notif->bookingReminder($booking, $user, $room)) {
                    $sent++;
                    CLI::write("  ✓ #{$row['id']} → {$row['user_email']}", 'green');
                } else {
                    $failed++;
                    CLI::write("  ✗ #{$row['id']} → {$row['user_email']} (falhou)", 'red');
                }
            } catch (\Throwable $e) {
                $failed++;
                CLI::write("  ✗ #{$row['id']} erro: {$e->getMessage()}", 'red');
            }
        }

        CLI::write("Concluído: {$sent} enviado(s), {$failed} falha(s).", $failed > 0 ? 'yellow' : 'green');
    }
}
