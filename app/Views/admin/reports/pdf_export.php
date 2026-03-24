<?php
$statusColors = [
    'pending'   => '#F59E0B',
    'approved'  => '#10B981',
    'rejected'  => '#EF4444',
    'cancelled' => '#6B7280',
    'absent'    => '#8B5CF6',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #1e293b; }
  .header { margin-bottom: 16px; border-bottom: 2px solid #3B82F6; padding-bottom: 10px; }
  .header h1 { font-size: 14pt; color: #1e40af; }
  .header p { font-size: 8pt; color: #64748b; margin-top: 2px; }
  .meta { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 7.5pt; color: #475569; }
  table { width: 100%; border-collapse: collapse; }
  thead th { background: #1e40af; color: #fff; padding: 5px 4px; text-align: left; font-size: 7.5pt; }
  tbody tr:nth-child(even) { background: #F8FAFC; }
  tbody td { padding: 4px; border-bottom: 1px solid #e2e8f0; vertical-align: top; font-size: 7.5pt; }
  .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; color: #fff; font-size: 6.5pt; font-weight: bold; }
  .summary { margin-bottom: 14px; }
  .summary table { width: auto; }
  .summary th, .summary td { padding: 4px 10px; }
  .summary thead th { font-size: 7.5pt; }
  .footer { margin-top: 14px; font-size: 7pt; color: #94a3b8; text-align: center; }
  .no-data { text-align: center; padding: 20px; color: #94a3b8; }
</style>
</head>
<body>

<div class="header">
  <h1>Relatório de Reservas — <?= esc($institution['name'] ?? 'Ambienta') ?></h1>
  <p>Período: <?= date('d/m/Y', strtotime($dateFrom)) ?> a <?= date('d/m/Y', strtotime($dateTo)) ?> &nbsp;|&nbsp; Gerado em: <?= $generatedAt ?></p>
</div>

<?php
// Summary by status
$statusCounts = [];
foreach ($rows as $r) {
    $s = $r['status'];
    $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
}
?>

<?php if (!empty($statusCounts)): ?>
<div class="summary">
  <table>
    <thead>
      <tr>
        <?php foreach ($statusCounts as $st => $cnt): ?>
        <th><?= esc($statusLabels[$st] ?? $st) ?></th>
        <?php endforeach; ?>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <?php foreach ($statusCounts as $cnt): ?>
        <td><?= $cnt ?></td>
        <?php endforeach; ?>
        <td><strong><?= count($rows) ?></strong></td>
      </tr>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php if (empty($rows)): ?>
<p class="no-data">Nenhuma reserva encontrada no período.</p>
<?php else: ?>
<table>
  <thead>
    <tr>
      <th style="width:4%">#</th>
      <th style="width:16%">Título</th>
      <th style="width:8%">Data</th>
      <th style="width:8%">Horário</th>
      <th style="width:14%">Ambiente</th>
      <th style="width:14%">Solicitante</th>
      <th style="width:8%">Status</th>
      <th style="width:28%">Obs.</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= $r['id'] ?></td>
      <td><?= esc($r['title']) ?></td>
      <td><?= date('d/m/Y', strtotime($r['date'])) ?></td>
      <td><?= substr($r['start_time'], 0, 5) ?>–<?= substr($r['end_time'], 0, 5) ?></td>
      <td><?= esc($r['room_name'] ?? '') ?> <?= $r['room_code'] ? '(' . esc($r['room_code']) . ')' : '' ?><br><span style="color:#64748b"><?= esc($r['building_name'] ?? '') ?></span></td>
      <td><?= esc($r['user_name'] ?? '') ?><br><span style="color:#64748b;font-size:6.5pt"><?= esc($r['user_email'] ?? '') ?></span></td>
      <td>
        <span class="badge" style="background:<?= $statusColors[$r['status']] ?? '#94a3b8' ?>">
          <?= esc($statusLabels[$r['status']] ?? $r['status']) ?>
        </span>
      </td>
      <td style="color:#475569"><?= esc($r['review_notes'] ?? $r['cancelled_reason'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<div class="footer">
  Ambienta — Sistema de Reserva de Ambientes &nbsp;|&nbsp; <?= $generatedAt ?>
</div>

</body>
</html>
