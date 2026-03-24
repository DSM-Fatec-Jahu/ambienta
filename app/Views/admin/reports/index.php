<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Relatórios</h1>
    <p class="page-subtitle">Análise de reservas por período</p>
  </div>
  <a href="<?= base_url('admin/relatorios/exportar-csv?date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo)) ?>"
     class="btn-secondary">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
    Exportar CSV
  </a>
  <a href="<?= base_url('admin/relatorios/exportar-pdf') ?>?date_from=<?= esc($dateFrom) ?>&date_to=<?= esc($dateTo) ?>"
     class="btn btn-secondary">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.293 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
    </svg>
    Exportar PDF
  </a>
</div>

<!-- Date range filter -->
<form method="GET" action="<?= base_url('admin/relatorios') ?>" class="card mb-6">
  <div class="card-body flex flex-wrap items-end gap-3">
    <div>
      <label class="form-label">De</label>
      <input type="date" name="date_from" value="<?= esc($dateFrom) ?>" class="form-input">
    </div>
    <div>
      <label class="form-label">Até</label>
      <input type="date" name="date_to" value="<?= esc($dateTo) ?>" class="form-input">
    </div>
    <button type="submit" class="btn-primary">Aplicar</button>
    <!-- Shortcuts -->
    <div class="flex gap-1 ml-auto">
      <?php
      $shortcuts = [
        'Este mês'     => [date('Y-m-01'), date('Y-m-d')],
        'Mês passado'  => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
        'Este ano'     => [date('Y-01-01'), date('Y-12-31')],
      ];
      foreach ($shortcuts as $label => [$f, $t]):
      ?>
        <a href="?date_from=<?= $f ?>&date_to=<?= $t ?>"
           class="btn-ghost btn-sm text-xs px-2">
          <?= $label ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</form>

<?php
$total    = array_sum($statusMap);
$approved = (int)($statusMap['approved']  ?? 0);
$pending  = (int)($statusMap['pending']   ?? 0);
$rejected = (int)($statusMap['rejected']  ?? 0);
$cancelled= (int)($statusMap['cancelled'] ?? 0);
$absent   = (int)($statusMap['absent']    ?? 0);
?>

<!-- Status stat cards -->
<div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-4 mb-6">

  <?php
  $cards = [
    ['label' => 'Total',      'value' => $total,     'bg' => 'bg-primary-light',  'text' => 'text-primary'],
    ['label' => 'Aprovadas',  'value' => $approved,  'bg' => 'bg-emerald-50',     'text' => 'text-success'],
    ['label' => 'Pendentes',  'value' => $pending,   'bg' => 'bg-amber-50',       'text' => 'text-warning'],
    ['label' => 'Recusadas',  'value' => $rejected,  'bg' => 'bg-red-50',         'text' => 'text-danger'],
    ['label' => 'Canceladas', 'value' => $cancelled, 'bg' => 'bg-slate-100',      'text' => 'text-slate-500'],
  ];
  foreach ($cards as $c):
  ?>
  <div class="stat-card">
    <div class="stat-icon <?= $c['bg'] ?>">
      <svg class="w-5 h-5 <?= $c['text'] ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
             M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
    </div>
    <div>
      <p class="stat-label"><?= $c['label'] ?></p>
      <p class="stat-value"><?= $c['value'] ?></p>
      <?php if ($total > 0 && $c['label'] !== 'Total'): ?>
        <p class="text-xs text-slate-400"><?= $total > 0 ? round($c['value'] / $total * 100, 1) : 0 ?>%</p>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>

</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

  <!-- Top rooms -->
  <div class="card overflow-hidden">
    <div class="card-header">
      <h2 class="text-sm font-semibold text-slate-900">Ambientes mais reservados</h2>
      <span class="text-xs text-slate-400">reservas aprovadas</span>
    </div>
    <?php if (empty($topRooms)): ?>
      <div class="card-body text-sm text-slate-400 italic">Sem dados no período.</div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="table-base">
          <thead>
            <tr>
              <th>#</th>
              <th>Ambiente</th>
              <th class="text-center">Reservas</th>
              <th class="text-center">Participantes</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topRooms as $i => $rm): ?>
            <tr>
              <td class="text-slate-400 font-bold"><?= $i + 1 ?></td>
              <td>
                <div class="font-medium text-slate-900"><?= esc($rm['room_name'] ?? '—') ?></div>
                <?php if (!empty($rm['building_name'])): ?>
                  <div class="text-xs text-slate-400"><?= esc($rm['building_name']) ?></div>
                <?php endif; ?>
              </td>
              <td class="text-center font-semibold text-slate-700"><?= $rm['total'] ?></td>
              <td class="text-center text-slate-600"><?= number_format($rm['total_attendees']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Top users -->
  <div class="card overflow-hidden">
    <div class="card-header">
      <h2 class="text-sm font-semibold text-slate-900">Maiores solicitantes</h2>
      <span class="text-xs text-slate-400">todas as reservas</span>
    </div>
    <?php if (empty($topUsers)): ?>
      <div class="card-body text-sm text-slate-400 italic">Sem dados no período.</div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="table-base">
          <thead>
            <tr>
              <th>#</th>
              <th>Usuário</th>
              <th class="text-center">Reservas</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topUsers as $i => $u): ?>
            <tr>
              <td class="text-slate-400 font-bold"><?= $i + 1 ?></td>
              <td>
                <div class="font-medium text-slate-900"><?= esc($u['user_name'] ?? '—') ?></div>
                <div class="text-xs text-slate-400"><?= esc($u['email'] ?? '') ?></div>
              </td>
              <td class="text-center font-semibold text-slate-700"><?= $u['total'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- Daily sparkline (simple bar chart) -->
<?php if (!empty($daily)): ?>
<div class="card">
  <div class="card-header">
    <h2 class="text-sm font-semibold text-slate-900">Reservas por dia</h2>
    <span class="text-xs text-slate-400"><?= count($daily) ?> dias com reservas</span>
  </div>
  <div class="card-body overflow-x-auto">
    <?php $maxVal = max($daily) ?: 1; ?>
    <div class="flex items-end gap-1 h-24 min-w-max">
      <?php foreach ($daily as $date => $count): ?>
        <?php $pct = round($count / $maxVal * 100); ?>
        <div class="flex flex-col items-center gap-1 group" title="<?= date('d/m', strtotime($date)) ?>: <?= $count ?> reservas">
          <span class="text-2xs text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity"><?= $count ?></span>
          <div class="w-4 bg-primary rounded-t transition-all"
               style="height: <?= max(4, $pct) ?>%"></div>
          <span class="text-2xs text-slate-400 rotate-45 origin-left whitespace-nowrap">
            <?= date('d/m', strtotime($date)) ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
