<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:DejaVu Sans,sans-serif; font-size:8pt; color:#1e293b; margin:1cm; }
  .header { margin-bottom:14px; border-bottom:2px solid #1E40AF; padding-bottom:10px; }
  .header h1 { font-size:13pt; color:#1e40af; }
  .header p  { font-size:7.5pt; color:#64748b; margin-top:3px; }
  table { width:100%; border-collapse:collapse; }
  thead th { background:#1e40af; color:#fff; padding:5px; text-align:left; font-size:7.5pt; }
  tbody tr:nth-child(even) { background:#f8fafc; }
  tbody td { padding:4px 5px; border-bottom:1px solid #e2e8f0; font-size:7.5pt; vertical-align:middle; }
  .badge { display:inline-block; padding:1px 6px; border-radius:3px; font-size:6.5pt; font-weight:bold; }
  .badge-pending   { background:#fef3c7; color:#92400e; }
  .badge-approved  { background:#d1fae5; color:#065f46; }
  .badge-rejected  { background:#fee2e2; color:#991b1b; }
  .badge-cancelled { background:#f1f5f9; color:#475569; }
  .badge-absent    { background:#fef3c7; color:#92400e; }
  .footer { margin-top:14px; font-size:7pt; color:#94a3b8; text-align:center; }
</style>
</head>
<body>

  <div class="header">
    <h1><?= esc($institution['name'] ?? 'Sistema') ?> — Minhas Reservas</h1>
    <p>Gerado em: <?= $generatedAt ?> &nbsp;|&nbsp; Total: <?= count($rows) ?> registro(s)</p>
  </div>

  <table>
    <thead>
      <tr>
        <th>Título</th>
        <th>Ambiente</th>
        <th>Prédio</th>
        <th>Data</th>
        <th>Horário</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $statusLabel = static fn(string $s): string => match($s) {
        'approved'  => 'Aprovada',
        'rejected'  => 'Recusada',
        'cancelled' => 'Cancelada',
        'absent'    => 'Ausente',
        default     => 'Pendente',
      };
      foreach ($rows as $r):
        $horario = substr($r['start_time'], 0, 5) . ' – ' . substr($r['end_time'], 0, 5);
        $date    = $r['date'] ? date('d/m/Y', strtotime($r['date'])) : '';
        $badgeCls = 'badge badge-' . $r['status'];
      ?>
      <tr>
        <td><?= esc($r['title']) ?></td>
        <td><?= esc($r['room_name'] ?? '—') ?></td>
        <td><?= esc($r['building_name'] ?? '—') ?></td>
        <td><?= $date ?></td>
        <td><?= $horario ?></td>
        <td><span class="<?= $badgeCls ?>"><?= $statusLabel($r['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="footer">Sistema — <?= $generatedAt ?></div>

</body>
</html>
