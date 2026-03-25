<?= $this->extend('layouts/app') ?>

<?= $this->section('head') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$csvUrl = base_url('admin/relatorios/equipamentos/exportar-csv')
    . '?date_from=' . urlencode($dateFrom)
    . '&date_to='   . urlencode($dateTo);
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Uso de Equipamentos</h1>
    <p class="page-subtitle">Equipamentos mais requisitados por reservas no período</p>
  </div>
  <?php if (!empty($rows)): ?>
  <a href="<?= $csvUrl ?>" class="btn-secondary btn-sm">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
    Exportar CSV
  </a>
  <?php endif; ?>
</div>

<!-- Date filter -->
<form method="GET" action="<?= base_url('admin/relatorios/equipamentos') ?>" class="card mb-4">
  <div class="card-body">
    <div class="flex flex-wrap items-end gap-3">
      <div>
        <label class="form-label">De</label>
        <input type="date" name="date_from" value="<?= esc($dateFrom) ?>" class="form-input">
      </div>
      <div>
        <label class="form-label">Até</label>
        <input type="date" name="date_to" value="<?= esc($dateTo) ?>" class="form-input">
      </div>
      <button type="submit" class="btn-primary btn-sm">Aplicar</button>
    </div>
  </div>
</form>

<?php if (empty($rows)): ?>
  <div class="card">
    <div class="empty-state">
      <p class="empty-state-title">Nenhum equipamento utilizado no período</p>
      <p class="empty-state-description">Nenhuma reserva com equipamento foi encontrada entre <?= date('d/m/Y', strtotime($dateFrom)) ?> e <?= date('d/m/Y', strtotime($dateTo)) ?>.</p>
    </div>
  </div>
<?php else: ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

  <!-- Summary cards -->
  <div class="grid grid-cols-2 gap-4">
    <?php
      $totalEquipTypes = count($rows);
      $totalBookings   = array_sum(array_column($rows, 'total_bookings'));
      $totalQty        = array_sum(array_column($rows, 'total_quantity'));
      $topItem         = $rows[0] ?? null;
    ?>
    <div class="card">
      <div class="card-body text-center">
        <p class="text-2xl font-bold text-primary"><?= $totalEquipTypes ?></p>
        <p class="text-xs text-slate-500 mt-0.5">Tipos de equipamento</p>
      </div>
    </div>
    <div class="card">
      <div class="card-body text-center">
        <p class="text-2xl font-bold text-slate-800"><?= number_format($totalBookings) ?></p>
        <p class="text-xs text-slate-500 mt-0.5">Reservas com equipamento</p>
      </div>
    </div>
    <div class="card">
      <div class="card-body text-center">
        <p class="text-2xl font-bold text-slate-800"><?= number_format($totalQty) ?></p>
        <p class="text-xs text-slate-500 mt-0.5">Unidades solicitadas</p>
      </div>
    </div>
    <div class="card">
      <div class="card-body text-center">
        <p class="text-sm font-bold text-slate-800 truncate" title="<?= esc($topItem['equipment_name'] ?? '—') ?>">
          <?= esc($topItem ? (mb_strlen($topItem['equipment_name']) > 18 ? mb_substr($topItem['equipment_name'], 0, 16) . '…' : $topItem['equipment_name']) : '—') ?>
        </p>
        <p class="text-xs text-slate-500 mt-0.5">Mais solicitado</p>
      </div>
    </div>
  </div>

  <!-- Bar chart -->
  <div class="card">
    <div class="card-header">
      <h2 class="text-sm font-semibold text-slate-900">Reservas por equipamento</h2>
    </div>
    <div class="card-body">
      <canvas id="equipChart" style="max-height:240px"></canvas>
    </div>
  </div>

</div>

<!-- Table -->
<div class="card overflow-hidden">
  <div class="card-header">
    <h2 class="text-sm font-semibold text-slate-900">Detalhamento</h2>
    <span class="text-xs text-slate-400"><?= $totalEquipTypes ?> equipamento(s)</span>
  </div>
  <div class="overflow-x-auto">
    <table class="table-base">
      <thead>
        <tr>
          <th>#</th>
          <th>Equipamento</th>
          <th>Patrimônio</th>
          <th class="text-center">Reservas</th>
          <th class="text-center">Qtd. total</th>
          <th>Demanda relativa</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $idx => $r):
          $pct = $totalBookings > 0 ? round($r['total_bookings'] / $totalBookings * 100) : 0;
        ?>
        <tr>
          <td class="text-slate-400 text-xs"><?= $idx + 1 ?></td>
          <td class="font-medium"><?= esc($r['equipment_name']) ?></td>
          <td class="text-slate-500 text-xs font-mono"><?= esc($r['equipment_code'] ?? '—') ?></td>
          <td class="text-center font-semibold"><?= number_format($r['total_bookings']) ?></td>
          <td class="text-center"><?= number_format($r['total_quantity']) ?></td>
          <td class="w-40">
            <div class="flex items-center gap-2">
              <div class="flex-1 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                <div class="bg-primary h-1.5 rounded-full" style="width:<?= $pct ?>%"></div>
              </div>
              <span class="text-xs text-slate-500 w-8 text-right"><?= $pct ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<?= $this->endSection() ?>

<?php if (!empty($rows)): ?>
<?= $this->section('scripts') ?>
<script>
(function () {
  const labels = <?= json_encode(array_column($rows, 'equipment_name')) ?>;
  const data   = <?= json_encode(array_map('intval', array_column($rows, 'total_bookings'))) ?>;

  const ctx = document.getElementById('equipChart');
  if (!ctx) return;

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Reservas',
        data,
        backgroundColor: 'rgba(99,102,241,0.7)',
        borderColor: 'rgba(99,102,241,1)',
        borderWidth: 1,
        borderRadius: 4,
      }],
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: {
          beginAtZero: true,
          ticks: { precision: 0, font: { size: 11 } },
          grid: { color: 'rgba(0,0,0,0.04)' },
        },
        y: {
          ticks: {
            font: { size: 11 },
            callback: (val, idx) => {
              const l = labels[idx];
              return l.length > 22 ? l.substring(0, 20) + '…' : l;
            },
          },
          grid: { display: false },
        },
      },
    },
  });
})();
</script>
<?= $this->endSection() ?>
<?php endif; ?>
