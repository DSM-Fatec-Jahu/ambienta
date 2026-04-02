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
  .badge-active   { background:#dcfce7; color:#166534; }
  .badge-inactive { background:#fee2e2; color:#991b1b; }
  .badge-code     { background:#dbeafe; color:#1e40af; }
  .badge-stock    { background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; }
  .footer { margin-top:14px; font-size:7pt; color:#94a3b8; text-align:center; }
</style>
</head>
<body>
  <div class="header">
    <h1><?= esc($institution['name'] ?? 'Sistema') ?> — Relatório de Recursos</h1>
    <p>Gerado em: <?= $generatedAt ?> &nbsp;|&nbsp; Total: <?= count($rows) ?> registro(s)</p>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:22%">Nome</th>
        <th style="width:14%">Categoria</th>
        <th style="width:12%">Patrimônio</th>
        <th style="width:8%; text-align:center">Qtd.</th>
        <th style="width:26%">Localização</th>
        <th style="width:10%">Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <?php
          $localizacao = !empty($r['current_room_name'])
              ? esc($r['current_room_name']) . (!empty($r['current_room_abbr']) ? ' (' . esc($r['current_room_abbr']) . ')' : '')
              : null;
        ?>
        <tr>
          <td><?= esc($r['name']) ?></td>
          <td><?= esc($r['category'] ?? '—') ?></td>
          <td>
            <?php if (!empty($r['code'])): ?>
              <span class="badge badge-code"><?= esc($r['code']) ?></span>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td style="text-align:center; font-weight:600"><?= (int) $r['quantity_total'] ?></td>
          <td>
            <?php if ($localizacao): ?>
              <?= $localizacao ?>
            <?php else: ?>
              <span class="badge badge-stock">Estoque geral</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($r['is_active']): ?>
              <span class="badge badge-active">Ativo</span>
            <?php else: ?>
              <span class="badge badge-inactive">Inativo</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="footer">Sistema — <?= $generatedAt ?></div>
</body>
</html>
