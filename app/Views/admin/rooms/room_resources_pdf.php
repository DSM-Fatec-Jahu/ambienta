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
  .badge { display:inline-block; padding:1px 6px; border-radius:3px; font-size:6.5pt; font-weight:bold;
           background:#eff6ff; color:#1d4ed8; }
  .footer { margin-top:14px; font-size:7pt; color:#94a3b8; text-align:center; }
</style>
</head>
<body>
  <div class="header">
    <h1><?= esc($institution['name'] ?? 'Sistema') ?> — Recursos do Ambiente: <?= esc($room['name']) ?></h1>
    <p>Gerado em: <?= $generatedAt ?> &nbsp;|&nbsp; Total: <?= count($rows) ?> recurso(s) alocado(s)</p>
  </div>

  <table>
    <thead>
      <tr>
        <th>Recurso</th>
        <th>Código / Patrimônio</th>
        <th style="text-align:center">Qtd. Alocada</th>
        <th>Alocado por</th>
        <th>Data de Alocação</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= esc($r['name']) ?></td>
        <td>
          <?php if (!empty($r['code'])): ?>
            <span class="badge"><?= esc($r['code']) ?></span>
          <?php else: ?>
            <span style="color:#cbd5e1">—</span>
          <?php endif; ?>
        </td>
        <td style="text-align:center; font-weight:bold"><?= (int) $r['allocated_quantity'] ?></td>
        <td><?= esc($r['allocated_by_name'] ?? '—') ?></td>
        <td><?= $r['allocated_at'] ? date('d/m/Y H:i', strtotime($r['allocated_at'])) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="footer">Sistema — <?= $generatedAt ?></div>
</body>
</html>
