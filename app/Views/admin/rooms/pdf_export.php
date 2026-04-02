<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #1e293b;
         margin: 1cm; }
  .header { margin-bottom: 14px; border-bottom: 2px solid #1E40AF; padding-bottom: 10px; }
  .header h1 { font-size: 13pt; color: #1e40af; }
  .header p  { font-size: 7.5pt; color: #64748b; margin-top: 3px; }
  table { width: 100%; border-collapse: collapse; }
  thead th {
    background: #1e40af; color: #fff;
    padding: 5px 5px; text-align: left; font-size: 7.5pt;
  }
  tbody tr:nth-child(even) { background: #f8fafc; }
  tbody td { padding: 4px 5px; border-bottom: 1px solid #e2e8f0; font-size: 7.5pt; vertical-align: middle; }
  .badge {
    display: inline-block; padding: 1px 6px; border-radius: 3px;
    font-size: 6.5pt; font-weight: bold;
  }
  .badge-active   { background: #d1fae5; color: #065f46; }
  .badge-inactive { background: #f1f5f9; color: #64748b; }
  .badge-maint    { background: #fef3c7; color: #92400e; }
  .stars { color: #d97706; font-size: 7pt; }
  .footer { margin-top: 14px; font-size: 7pt; color: #94a3b8; text-align: center; }
  .summary { margin-bottom: 12px; font-size: 7.5pt; color: #475569; }
</style>
</head>
<body>

<div class="header">
  <h1>Ambientes — <?= esc($institution['name'] ?? 'Ambienta') ?></h1>
  <p>Gerado em: <?= $generatedAt ?> &nbsp;|&nbsp; Total: <?= count($rows) ?> ambiente<?= count($rows) !== 1 ? 's' : '' ?></p>
</div>

<?php if (empty($rows)): ?>
  <p style="text-align:center; padding:20px; color:#94a3b8;">Nenhum ambiente encontrado.</p>
<?php else: ?>

<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Nome</th>
      <th>Código</th>
      <th>Prédio / Andar</th>
      <th style="text-align:center">Cap.</th>
      <th style="text-align:center">Emp.</th>
      <th style="text-align:center">Avaliação</th>
      <th style="text-align:center">Status</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r):
      $rData  = $ratingsMap[(int) $r['id']] ?? null;
      $avgRat = ($rData && $rData['total_ratings'] > 0) ? number_format((float) $rData['avg_rating'], 1) : null;

      if ($r['maintenance_mode']) {
          $statusLabel = 'Manutenção';
          $statusClass = 'badge-maint';
      } elseif ($r['is_active']) {
          $statusLabel = 'Ativo';
          $statusClass = 'badge-active';
      } else {
          $statusLabel = 'Inativo';
          $statusClass = 'badge-inactive';
      }

      $building = $r['building_name'] ?? '—';
      if ($r['floor']) $building .= ' / ' . $r['floor'];
    ?>
    <tr>
      <td style="color:#94a3b8"><?= (int) $r['id'] ?></td>
      <td style="font-weight:bold"><?= esc($r['name']) ?></td>
      <td><?= $r['code'] ? esc($r['code']) : '<span style="color:#cbd5e1">—</span>' ?></td>
      <td><?= esc($building) ?></td>
      <td style="text-align:center">
        <?= $r['capacity'] > 0 ? (int) $r['capacity'] : '<span style="color:#cbd5e1">—</span>' ?>
      </td>
      <td style="text-align:center">
        <?= $r['allows_equipment_lending']
              ? '<span style="color:#059669">Sim</span>'
              : '<span style="color:#cbd5e1">Não</span>' ?>
      </td>
      <td style="text-align:center">
        <?php if ($avgRat !== null): ?>
          <span class="stars">&#9733;</span> <?= $avgRat ?>
          <span style="color:#94a3b8">(<?= (int) $rData['total_ratings'] ?>)</span>
        <?php else: ?>
          <span style="color:#cbd5e1">—</span>
        <?php endif; ?>
      </td>
      <td style="text-align:center">
        <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
        <?php if ($r['maintenance_mode'] && $r['maintenance_until']): ?>
          <br><span style="font-size:6pt;color:#92400e">até <?= date('d/m/Y', strtotime($r['maintenance_until'])) ?></span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php endif; ?>

<div class="footer">
  Ambienta — Relatório de Ambientes &nbsp;|&nbsp; <?= $generatedAt ?>
</div>

</body>
</html>
