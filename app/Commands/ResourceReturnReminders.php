<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\BookingResourceModel;

/**
 * RN-R09 — Dispara notificações de devolução pendente vencida.
 *
 * Para cada institution ativa, encontra booking_resources com:
 *   - status = 'approved'
 *   - booking encerrado há mais de resource_return_deadline_hours
 *   - notified_at IS NULL  (garante envio único por evento)
 *
 * Notifica todos os técnicos ativos + solicitante da reserva.
 * Marca notified_at após o envio para evitar reenvio em execuções futuras.
 *
 * Cron sugerido (a cada 15 min):
 *   * /15 * * * *  php /path/to/spark resource:return-reminders >> /dev/null 2>&1
 */
class ResourceReturnReminders extends BaseCommand
{
    protected $group       = 'Resource';
    protected $name        = 'resource:return-reminders';
    protected $description = 'Envia notificações de devolução pendente vencida (RN-R09).';

    public function run(array $params): void
    {
        $db    = db_connect();
        $notif = service('notification');
        $model = new BookingResourceModel();

        $institutions = $db->table('institutions')
            ->where('is_active', 1)
            ->get()->getResultArray();

        $total  = 0;
        $errors = 0;

        foreach ($institutions as $institution) {
            $settings      = json_decode($institution['settings'] ?? '{}', true);
            $deadlineHours = (int) ($settings['resources']['resource_return_deadline_hours'] ?? 1);

            $overdue = $model->overdueUnnotified((int) $institution['id'], $deadlineHours);

            if (empty($overdue)) {
                continue;
            }

            CLI::write("[{$institution['name']}] {$deadlineHours}h threshold — " . count($overdue) . " pendente(s)", 'yellow');

            foreach ($overdue as $br) {
                try {
                    $resource  = $db->table('resources')->where('id', $br['resource_id'])->get()->getRowArray();
                    $booking   = $db->table('bookings')->where('id', $br['booking_id'])->get()->getRowArray();
                    $requester = [
                        'id'    => $br['requester_id'],
                        'name'  => $br['requester_name'] ?? 'Solicitante',
                        'email' => $br['requester_email'] ?? '',
                    ];

                    if (!$resource || !$booking) {
                        CLI::write("  ! BR#{$br['id']} — recurso ou reserva não encontrado, pulando.", 'yellow');
                        continue;
                    }

                    $notif->resourceReturnOverdue($booking, $requester, $resource, (int) $br['quantity']);
                    $model->markNotified((int) $br['id']);

                    CLI::write(
                        "  ✓ BR#{$br['id']} — {$br['resource_name']} → {$br['requester_name']}",
                        'green'
                    );
                    $total++;
                } catch (\Throwable $e) {
                    $errors++;
                    CLI::write("  ✗ BR#{$br['id']} erro: {$e->getMessage()}", 'red');
                    log_message('error', "[ResourceReturnReminders] BR#{$br['id']}: {$e->getMessage()}");
                }
            }
        }

        CLI::write(
            "Concluído: {$total} notificação(ões) enviada(s)" . ($errors > 0 ? ", {$errors} erro(s)." : '.'),
            $errors > 0 ? 'yellow' : 'green'
        );
    }
}
