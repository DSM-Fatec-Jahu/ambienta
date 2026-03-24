<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<?php
$exportQs = http_build_query(array_filter([
  'action'      => $filters['action'],
  'entity_type' => $filters['entityType'],
  'actor_id'    => $filters['actorId'] ?: '',
  'date_from'   => $filters['dateFrom'],
  'date_to'     => $filters['dateTo'],
]));
$exportUrl = base_url('admin/auditoria/exportar-csv') . ($exportQs ? '?' . $exportQs : '');
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Auditoria</h1>
    <p class="page-subtitle">Registro imutável de todas as ações no sistema</p>
  </div>
  <div class="flex items-center gap-3">
    <span class="text-xs text-slate-400"><?= number_format($total) ?> registro<?= $total !== 1 ? 's' : '' ?></span>
    <a href="<?= $exportUrl ?>" class="btn-secondary btn-sm">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      Exportar CSV
    </a>
  </div>
</div>

<!-- Filters -->
<form method="GET" action="<?= base_url('admin/auditoria') ?>" class="card mb-4">
  <div class="card-body">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
      <div>
        <label class="form-label">Ação</label>
        <input type="text" name="action" value="<?= esc($filters['action']) ?>"
               class="form-input" placeholder="Ex: auth.login">
      </div>
      <div>
        <label class="form-label">Tipo de entidade</label>
        <input type="text" name="entity_type" value="<?= esc($filters['entityType']) ?>"
               class="form-input" placeholder="Ex: user, booking">
      </div>
      <div>
        <label class="form-label">Ator</label>
        <select name="actor_id" class="form-input">
          <option value="">Todos</option>
          <?php foreach ($actors as $a): ?>
            <option value="<?= $a['id'] ?>" <?= $filters['actorId'] == $a['id'] ? 'selected' : '' ?>>
              <?= esc($a['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label">De</label>
        <input type="date" name="date_from" value="<?= esc($filters['dateFrom']) ?>" class="form-input">
      </div>
      <div>
        <label class="form-label">Até</label>
        <input type="date" name="date_to" value="<?= esc($filters['dateTo']) ?>" class="form-input">
      </div>
    </div>
    <div class="mt-3 flex gap-2">
      <button type="submit" class="btn-primary btn-sm">Filtrar</button>
      <a href="<?= base_url('admin/auditoria') ?>" class="btn-secondary btn-sm">Limpar</a>
    </div>
  </div>
</form>

<div class="card overflow-hidden">
  <?php if (empty($rows)): ?>
    <div class="empty-state">
      <p class="empty-state-title">Nenhum registro encontrado</p>
      <p class="empty-state-description">Tente ajustar os filtros.</p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="table-base text-xs">
        <thead>
          <tr>
            <th class="w-36">Data / Hora</th>
            <th>Ação</th>
            <th>Entidade</th>
            <th>Ator</th>
            <th>IP</th>
            <th class="w-16 text-right">Detalhes</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <tr x-data="{ open: false }">
            <td class="whitespace-nowrap text-slate-400 font-mono">
              <?= date('d/m/y H:i:s', strtotime($r['created_at'])) ?>
            </td>
            <td>
              <code class="text-xs font-mono text-primary bg-primary-light px-1.5 py-0.5 rounded">
                <?= esc($r['action']) ?>
              </code>
            </td>
            <td class="text-slate-600">
              <?php if ($r['entity_type']): ?>
                <span><?= esc($r['entity_type']) ?></span>
                <?php if ($r['entity_id']): ?>
                  <span class="text-slate-300 ml-1">#<?= $r['entity_id'] ?></span>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-slate-300">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($r['actor_name']): ?>
                <div class="font-medium text-slate-700"><?= esc($r['actor_name']) ?></div>
                <div class="text-slate-400"><?= esc($r['actor_email'] ?? '') ?></div>
              <?php else: ?>
                <span class="text-slate-400 italic">Sistema</span>
              <?php endif; ?>
            </td>
            <td class="font-mono text-slate-400"><?= esc($r['ip_address'] ?? '—') ?></td>
            <td class="text-right">
              <?php if ($r['old_values'] || $r['new_values']): ?>
                <button @click="open = !open"
                        class="btn-ghost btn-sm p-1" aria-label="Ver diff">
                  <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-90' : ''"
                       fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php if ($r['old_values'] || $r['new_values']): ?>
          <tr x-data="{ open: false }" x-show="$el.previousElementSibling.__x && $el.previousElementSibling.__x.$data.open"
              class="bg-slate-50" x-cloak>
            <td colspan="6" class="px-5 py-3">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php if ($r['old_values']): ?>
                  <div>
                    <p class="text-2xs font-semibold uppercase tracking-wide text-slate-400 mb-1">Antes</p>
                    <pre class="text-xs text-slate-600 bg-white border border-slate-200 rounded-lg p-2 overflow-auto max-h-40 font-mono"><?= esc(json_encode(json_decode($r['old_values']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                  </div>
                <?php endif; ?>
                <?php if ($r['new_values']): ?>
                  <div>
                    <p class="text-2xs font-semibold uppercase tracking-wide text-slate-400 mb-1">Depois</p>
                    <pre class="text-xs text-slate-600 bg-white border border-slate-200 rounded-lg p-2 overflow-auto max-h-40 font-mono"><?= esc(json_encode(json_decode($r['new_values']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                  </div>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php
    $totalPages = (int) ceil($total / $perPage);
    if ($totalPages > 1):
      $qs = http_build_query(array_filter([
        'action'      => $filters['action'],
        'entity_type' => $filters['entityType'],
        'actor_id'    => $filters['actorId'] ?: '',
        'date_from'   => $filters['dateFrom'],
        'date_to'     => $filters['dateTo'],
      ]));
    ?>
    <div class="card-footer flex items-center justify-between">
      <p class="text-xs text-slate-400">
        Página <?= $page ?> de <?= $totalPages ?> — <?= number_format($total) ?> registros
      </p>
      <div class="flex items-center gap-1">
        <?php if ($page > 1): ?>
          <a href="?<?= $qs ?>&page=<?= $page - 1 ?>" class="btn-ghost btn-sm px-2">← Anterior</a>
        <?php endif; ?>
        <?php if ($page < $totalPages): ?>
          <a href="?<?= $qs ?>&page=<?= $page + 1 ?>" class="btn-ghost btn-sm px-2">Próxima →</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<!-- Note: the diff toggle uses a sibling selector trick — works fine for tabular data -->
<script>
// Wire the diff rows to the button in the previous row
document.querySelectorAll('table tbody tr').forEach(row => {
  const btn = row.querySelector('button[aria-label="Ver diff"]');
  if (!btn) return;
  const diffRow = row.nextElementSibling;
  if (!diffRow) return;
  diffRow.style.display = 'none';
  btn.addEventListener('click', () => {
    const isVisible = diffRow.style.display !== 'none';
    diffRow.style.display = isVisible ? 'none' : '';
    btn.querySelector('svg').style.transform = isVisible ? '' : 'rotate(90deg)';
  });
});
</script>

<?= $this->endSection() ?>
