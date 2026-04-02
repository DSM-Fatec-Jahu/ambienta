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
  .badge-active   { background:#d1fae5; color:#065f46; }
  .badge-inactive { background:#f1f5f9; color:#475569; }
  .badge-google   { background:#dbeafe; color:#1e40af; }
  .footer { margin-top:14px; font-size:7pt; color:#94a3b8; text-align:center; }
</style>
</head>
<body>

  <div class="header">
    <h1><?= esc($institution['name'] ?? 'Sistema') ?> — Usuários</h1>
    <p>Gerado em: <?= $generatedAt ?> &nbsp;|&nbsp; Total: <?= count($rows) ?> registro(s)</p>
  </div>

  <table>
    <thead>
      <tr>
        <th>Nome</th>
        <th>E-mail</th>
        <th>Perfil</th>
        <th>SSO</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $u): ?>
      <tr>
        <td><?= esc($u['name']) ?></td>
        <td><?= esc($u['email']) ?></td>
        <td><?= esc($rolesList[$u['role']] ?? $u['role']) ?></td>
        <td>
          <?php if ($u['google_id']): ?>
            <span class="badge badge-google">Google</span>
          <?php else: ?>
            Local
          <?php endif; ?>
        </td>
        <td>
          <?php if ($u['is_active']): ?>
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
