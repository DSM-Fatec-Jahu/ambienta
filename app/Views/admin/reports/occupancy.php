<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Ocupação por Sala</h1>
    <p class="page-subtitle">Horas reservadas por ambiente no período</p>
  </div>
  <a href="<?= base_url('admin/relatorios/ocupacao/exportar-csv?date_from=' . $dateFrom . '&date_to=' . $dateTo) ?>"
     class="btn-secondary">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
    Exportar CSV
  </a>
</div>

<!-- Filters -->
<div class="card mb-6">
  <div class="card-body">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
      <div>
        <label class="form-label">Data inicial</label>
        <input type="date" name="date_from" value="<?= $dateFrom ?>" class="form-input">
      </div>
      <div>
        <label class="form-label">Data final</label>
        <input type="date" name="date_to" value="<?= $dateTo ?>" class="form-input">
      </div>
      <div>
        <button type="submit" class="btn-primary">Filtrar</button>
      </div>
    </form>
  </div>
</div>

<?php
$refHours = $days * 8;
$maxHours = !empty($rows) ? max(array_column($rows, 'total_hours')) : 1;
?>

<?php if (empty($rows)): ?>
  <div class="card">
    <div class="empty-state">
      <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
      </svg>
      <p class="empty-state-title">Nenhuma reserva aprovada no período</p>
    </div>
  </div>
<?php else: ?>

<!-- Chart -->
<div class="card mb-6">
  <div class="card-header">
    <h2 class="card-title">Horas reservadas por sala</h2>
    <span class="text-xs text-slate-500"><?= date('d/m/Y', strtotime($dateFrom)) ?> – <?= date('d/m/Y', strtotime($dateTo)) ?></span>
  </div>
  <div class="card-body">
    <canvas id="occupancyChart" style="max-height:320px"></canvas>
  </div>
</div>

<!-- Table -->
<div class="card overflow-hidden">
  <div class="overflow-x-auto">
    <table class="table-base">
      <thead>
        <tr>
          <th>Sala</th>
          <th>Prédio</th>
          <th class="text-center">Capacidade</th>
          <th class="text-center">Reservas</th>
          <th class="text-center">Horas reservadas</th>
          <th class="text-center">Ref. disponível*</th>
          <th class="text-center">% Ocupação</th>
          <th>Barra</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r):
            $pct = $refHours > 0 ? min(100, round(($r['total_hours'] / $refHours) * 100, 1)) : 0;
            $barColor = $pct >= 80 ? 'bg-red-500' : ($pct >= 50 ? 'bg-amber-400' : 'bg-emerald-500');
        ?>
        <tr>
          <td>
            <div class="font-medium text-slate-900"><?= esc($r['room_name']) ?></div>
            <?php if ($r['room_code']): ?>
              <div class="text-xs text-slate-400"><?= esc($r['room_code']) ?></div>
            <?php endif; ?>
          </td>
          <td class="text-slate-600"><?= esc($r['building_name'] ?? '—') ?></td>
          <td class="text-center text-slate-600"><?= $r['capacity'] ?: '—' ?></td>
          <td class="text-center font-semibold text-slate-800"><?= $r['total_bookings'] ?></td>
          <td class="text-center font-semibold text-slate-800"><?= number_format((float)$r['total_hours'], 1, ',', '.') ?>h</td>
          <td class="text-center text-slate-500"><?= $refHours ?>h</td>
          <td class="text-center">
            <span class="font-bold <?= $pct >= 80 ? 'text-red-600' : ($pct >= 50 ? 'text-amber-600' : 'text-emerald-600') ?>"><?= $pct ?>%</span>
          </td>
          <td class="w-40">
            <div class="w-full bg-slate-100 rounded-full h-2">
              <div class="<?= $barColor ?> h-2 rounded-full" style="width:<?= $pct ?>%"></div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer">
    <p class="text-xs text-slate-400">* Referência: <?= $days ?> dia(s) × 8 h/dia = <?= $refHours ?>h por sala. A ocupação real depende dos horários de funcionamento configurados.</p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
  const labels = <?= json_encode(array_column($rows, 'room_name')) ?>;
  const data   = <?= json_encode(array_map(fn($r) => round((float)$r['total_hours'], 1), $rows)) ?>;
  const colors = data.map(v => {
    const pct = <?= $refHours ?> > 0 ? (v / <?= $refHours ?>) * 100 : 0;
    return pct >= 80 ? 'rgba(239,68,68,0.75)' : pct >= 50 ? 'rgba(245,158,11,0.75)' : 'rgba(16,185,129,0.75)';
  });

  new Chart(document.getElementById('occupancyChart'), {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Horas reservadas',
        data,
        backgroundColor: colors,
        borderRadius: 4,
      }],
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { title: { display: true, text: 'Horas' }, beginAtZero: true },
      },
    },
  });
})();
</script>

<?php endif; ?>

<?= $this->endSection() ?>
