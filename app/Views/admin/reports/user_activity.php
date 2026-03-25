<?= $this->extend('layouts/app') ?>

<?= $this->section('head') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$roleLabels = [
  'role_admin'         => 'Admin',
  'role_director'      => 'Diretor',
  'role_vice_director' => 'Vice-diretor',
  'role_coordinator'   => 'Coordenador',
  'role_technician'    => 'Técnico',
  'role_requester'     => 'Professor',
];
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Atividade por Usuário</h1>
    <p class="page-subtitle">Estatísticas de reservas agrupadas por solicitante</p>
  </div>
  <?php if (!empty($rows)): ?>
  <a href="<?= base_url('admin/relatorios/usuarios/exportar-csv?date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo)) ?>"
     class="btn-secondary">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
    </svg>
    Exportar CSV
  </a>
  <?php endif; ?>
</div>

<!-- ── Filters ──────────────────────────────────────────────────── -->
<form method="GET" action="<?= base_url('admin/relatorios/usuarios') ?>"
      class="card card-body mb-5 flex flex-wrap items-end gap-4">
  <div>
    <label class="form-label">De</label>
    <input type="date" name="date_from" value="<?= esc($dateFrom) ?>" class="form-input w-40">
  </div>
  <div>
    <label class="form-label">Até</label>
    <input type="date" name="date_to"   value="<?= esc($dateTo) ?>"   class="form-input w-40">
  </div>
  <button type="submit" class="btn-primary">Filtrar</button>
</form>

<?php if (empty($rows)): ?>
<div class="card card-body text-center py-12">
  <p class="text-slate-400">Nenhum dado no período selecionado.</p>
</div>
<?php else: ?>

<!-- ── Summary cards ────────────────────────────────────────────── -->
<?php
$totalAll    = array_sum(array_column($rows, 'total'));
$totalApproved = array_sum(array_column($rows, 'total_approved'));
$totalAbsent   = array_sum(array_column($rows, 'total_absent'));
$totalRejected = array_sum(array_column($rows, 'total_rejected'));
?>
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
  <div class="card card-body">
    <p class="text-2xs text-slate-400 uppercase tracking-wide font-semibold">Usuários</p>
    <p class="text-2xl font-bold text-slate-800 mt-1"><?= count($rows) ?></p>
    <p class="text-xs text-slate-400">com reservas no período</p>
  </div>
  <div class="card card-body">
    <p class="text-2xs text-slate-400 uppercase tracking-wide font-semibold">Total de Reservas</p>
    <p class="text-2xl font-bold text-slate-800 mt-1"><?= $totalAll ?></p>
    <p class="text-xs text-slate-400">no período</p>
  </div>
  <div class="card card-body">
    <p class="text-2xs text-slate-400 uppercase tracking-wide font-semibold">Aprovadas</p>
    <p class="text-2xl font-bold text-emerald-600 mt-1"><?= $totalApproved ?></p>
    <p class="text-xs text-slate-400"><?= $totalAll > 0 ? round(($totalApproved / $totalAll) * 100, 1) : 0 ?>% do total</p>
  </div>
  <div class="card card-body">
    <p class="text-2xs text-slate-400 uppercase tracking-wide font-semibold">Ausências</p>
    <p class="text-2xl font-bold text-red-500 mt-1"><?= $totalAbsent ?></p>
    <p class="text-xs text-slate-400">
      <?= $totalApproved > 0 ? round(($totalAbsent / $totalApproved) * 100, 1) : 0 ?>% das aprovadas
    </p>
  </div>
</div>

<!-- ── Chart ────────────────────────────────────────────────────── -->
<?php $top10 = array_slice($rows, 0, 10); ?>
<div class="card mb-5">
  <div class="card-header">
    <h2 class="text-sm font-semibold text-slate-900">Top <?= count($top10) ?> usuários por reservas</h2>
  </div>
  <div class="card-body">
    <canvas id="userChart" height="200"></canvas>
  </div>
</div>

<!-- ── Table ────────────────────────────────────────────────────── -->
<div class="card">
  <div class="card-header">
    <h2 class="text-sm font-semibold text-slate-900">Detalhe por usuário</h2>
    <span class="text-xs text-slate-400"><?= count($rows) ?> usuário(s)</span>
  </div>
  <div class="overflow-x-auto">
    <table class="table-base">
      <thead>
        <tr>
          <th>Usuário</th>
          <th>Perfil</th>
          <th class="text-center">Total</th>
          <th class="text-center text-emerald-600">Aprov.</th>
          <th class="text-center text-amber-500">Pend.</th>
          <th class="text-center text-red-500">Recus.</th>
          <th class="text-center text-slate-400">Canc.</th>
          <th class="text-center text-orange-500">Ausente</th>
          <th class="text-right">Taxa aprov.</th>
          <th class="text-right">Taxa ausência</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r):
          $approved  = (int) $r['total_approved'];
          $total     = (int) $r['total'];
          $absent    = (int) $r['total_absent'];
          $decided   = $approved + (int) $r['total_rejected'];
          $approvalRate = $decided > 0 ? round(($approved / $decided) * 100, 1) : null;
          $absenceRate  = $approved > 0 ? round(($absent / $approved) * 100, 1)  : null;
        ?>
        <tr>
          <td>
            <div class="font-medium text-slate-800"><?= esc($r['user_name'] ?? '—') ?></div>
            <div class="text-xs text-slate-400"><?= esc($r['email'] ?? '') ?></div>
          </td>
          <td>
            <span class="text-xs text-slate-500">
              <?= esc($roleLabels[$r['user_role']] ?? $r['user_role']) ?>
            </span>
          </td>
          <td class="text-center font-semibold text-slate-800"><?= $total ?></td>
          <td class="text-center text-emerald-600 font-medium"><?= $approved ?></td>
          <td class="text-center text-amber-500"><?= (int) $r['total_pending'] ?></td>
          <td class="text-center text-red-500"><?= (int) $r['total_rejected'] ?></td>
          <td class="text-center text-slate-400"><?= (int) $r['total_cancelled'] ?></td>
          <td class="text-center text-orange-500"><?= $absent ?></td>
          <td class="text-right">
            <?php if ($approvalRate !== null): ?>
              <div class="flex items-center justify-end gap-2">
                <div class="w-16 bg-slate-100 rounded-full h-1.5">
                  <div class="h-1.5 rounded-full bg-emerald-500"
                       style="width: <?= min(100, $approvalRate) ?>%"></div>
                </div>
                <span class="text-xs font-medium text-slate-700"><?= $approvalRate ?>%</span>
              </div>
            <?php else: ?>
              <span class="text-xs text-slate-400">—</span>
            <?php endif; ?>
          </td>
          <td class="text-right">
            <?php if ($absenceRate !== null): ?>
              <span class="text-xs font-medium <?= $absenceRate > 30 ? 'text-red-600' : ($absenceRate > 10 ? 'text-amber-600' : 'text-slate-600') ?>">
                <?= $absenceRate ?>%
              </span>
            <?php else: ?>
              <span class="text-xs text-slate-400">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->section('scripts') ?>
<script>
(function () {
  const labels   = <?= json_encode(array_map(fn($r) => $r['user_name'] ?? 'N/A', $top10)) ?>;
  const approved = <?= json_encode(array_map(fn($r) => (int)$r['total_approved'],  $top10)) ?>;
  const pending  = <?= json_encode(array_map(fn($r) => (int)$r['total_pending'],   $top10)) ?>;
  const rejected = <?= json_encode(array_map(fn($r) => (int)$r['total_rejected'],  $top10)) ?>;
  const absent   = <?= json_encode(array_map(fn($r) => (int)$r['total_absent'],    $top10)) ?>;

  new Chart(document.getElementById('userChart'), {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label: 'Aprovadas',  data: approved, backgroundColor: '#10b981' },
        { label: 'Pendentes',  data: pending,  backgroundColor: '#f59e0b' },
        { label: 'Recusadas',  data: rejected, backgroundColor: '#ef4444' },
        { label: 'Ausências',  data: absent,   backgroundColor: '#f97316' },
      ],
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: { legend: { position: 'bottom' } },
      scales: {
        x: { stacked: true, ticks: { precision: 0 } },
        y: { stacked: true },
      },
    },
  });
})();
</script>
<?= $this->endSection() ?>

<?php endif; ?>

<?= $this->endSection() ?>
