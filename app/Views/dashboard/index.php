<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<!-- Page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Bem-vindo, <?= esc($currentUser['name'] ?? 'Usuário') ?>!</p>
  </div>
</div>

<!-- Stat cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">

  <div class="stat-card">
    <div class="stat-icon bg-primary-light">
      <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
    </div>
    <div>
      <p class="stat-label">Minhas reservas hoje</p>
      <p class="stat-value"><?= (int) ($stats['my_today'] ?? 0) ?></p>
    </div>
  </div>

  <?php if ($isStaff): ?>
  <a href="<?= base_url('reservas/pendentes') ?>" class="stat-card hover:ring-2 hover:ring-warning/40 transition-all">
    <div class="stat-icon bg-amber-50">
      <svg class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0"/>
      </svg>
    </div>
    <div>
      <p class="stat-label">Pendentes de aprovação</p>
      <p class="stat-value"><?= $pendingCount ?></p>
    </div>
  </a>

  <div class="stat-card">
    <div class="stat-icon bg-emerald-50">
      <svg class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0"/>
      </svg>
    </div>
    <div>
      <p class="stat-label">Taxa de aprovação (30d)</p>
      <p class="stat-value"><?= $institutionSummary['approvalRate'] ?? 0 ?>%</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon bg-primary-light">
      <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
      </svg>
    </div>
    <div>
      <p class="stat-label">Total reservas (30d)</p>
      <p class="stat-value"><?= $institutionSummary['total'] ?? 0 ?></p>
    </div>
  </div>

  <?php else: ?>

  <div class="stat-card">
    <div class="stat-icon bg-emerald-50">
      <svg class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
             M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
    </div>
    <div>
      <p class="stat-label">Próxima reserva aprovada</p>
      <?php if (!empty($stats['next'])): ?>
        <p class="text-sm font-semibold text-slate-800 mt-1">
          <?= date('d/m', strtotime($stats['next']['date'])) ?>
          · <?= substr($stats['next']['start_time'], 0, 5) ?>
        </p>
        <p class="text-xs text-slate-400 truncate"><?= esc($stats['next']['room_name'] ?? '') ?></p>
      <?php else: ?>
        <p class="text-sm text-slate-400 mt-1">—</p>
      <?php endif; ?>
    </div>
  </div>

  <a href="<?= base_url('admin/ambientes') ?>" class="stat-card hover:ring-2 hover:ring-primary/30 transition-all">
    <div class="stat-icon bg-primary-light">
      <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
      </svg>
    </div>
    <div>
      <p class="stat-label">Ambientes ativos</p>
      <p class="stat-value"><?= $activeRooms ?></p>
    </div>
  </a>

  <?php endif; ?>

</div>

<!-- Analytics (staff only) -->
<?php if ($isStaff): ?>
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">

  <!-- Bar chart: bookings per weekday -->
  <div class="card xl:col-span-2">
    <div class="card-header">
      <h2 class="text-sm font-semibold text-slate-900">Reservas por dia da semana</h2>
      <span class="text-xs text-slate-400">últimos 30 dias</span>
    </div>
    <div class="card-body">
      <canvas id="weekdayChart" height="120"></canvas>
    </div>
  </div>

  <!-- Top rooms -->
  <div class="card">
    <div class="card-header">
      <h2 class="text-sm font-semibold text-slate-900">Ambientes mais reservados</h2>
      <span class="text-xs text-slate-400">30 dias</span>
    </div>
    <div class="card-body">
      <?php if (!empty($institutionSummary['topRooms'])): ?>
        <ul class="space-y-2">
          <?php
          $maxRoomTotal = max(array_column($institutionSummary['topRooms'], 'total')) ?: 1;
          foreach ($institutionSummary['topRooms'] as $room):
            $pct = round($room['total'] / $maxRoomTotal * 100);
          ?>
          <li>
            <div class="flex items-center justify-between text-xs mb-0.5">
              <span class="text-slate-700 font-medium truncate max-w-[160px]"><?= esc($room['room_name']) ?></span>
              <span class="text-slate-400 ml-2 flex-shrink-0"><?= $room['total'] ?></span>
            </div>
            <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
              <div class="h-full bg-primary rounded-full" style="width: <?= $pct ?>%"></div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="text-xs text-slate-400">Nenhuma reserva no período.</p>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Status breakdown -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
  <?php
  $breakdown = [
    ['label' => 'Aprovadas',   'value' => $institutionSummary['approved']  ?? 0, 'color' => 'text-success',  'bg' => 'bg-emerald-50'],
    ['label' => 'Recusadas',   'value' => $institutionSummary['rejected']  ?? 0, 'color' => 'text-danger',   'bg' => 'bg-red-50'],
    ['label' => 'Canceladas',  'value' => $institutionSummary['cancelled'] ?? 0, 'color' => 'text-slate-500','bg' => 'bg-slate-50'],
    ['label' => 'Amb. ativos', 'value' => $activeRooms,                          'color' => 'text-primary',  'bg' => 'bg-primary-light'],
  ];
  foreach ($breakdown as $b):
  ?>
  <div class="stat-card">
    <div class="stat-icon <?= $b['bg'] ?>">
      <svg class="w-4 h-4 <?= $b['color'] ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
      </svg>
    </div>
    <div>
      <p class="stat-label"><?= $b['label'] ?></p>
      <p class="stat-value <?= $b['color'] ?>"><?= $b['value'] ?></p>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Quick actions -->
<div class="card">
  <div class="card-header">
    <h2 class="text-sm font-semibold text-slate-900">Ações rápidas</h2>
  </div>
  <div class="card-body flex flex-wrap gap-3">
    <a href="<?= base_url('reservas/nova') ?>" class="btn-primary">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Nova Reserva
    </a>
    <a href="<?= base_url('reservas') ?>" class="btn-secondary">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
             M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
      Minhas Reservas
    </a>
    <a href="<?= base_url('agenda') ?>" class="btn-secondary">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      Ver Agenda
    </a>
    <?php if ($isStaff && $pendingCount > 0): ?>
    <a href="<?= base_url('reservas/pendentes') ?>" class="btn-secondary text-warning border-warning/30 bg-amber-50 hover:bg-amber-100">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0"/>
      </svg>
      <?= $pendingCount ?> pendente<?= $pendingCount !== 1 ? 's' : '' ?> de aprovação
    </a>
    <?php endif; ?>
    <?php if ($isStaff): ?>
    <a href="<?= base_url('admin/relatorios') ?>" class="btn-secondary">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      Relatórios
    </a>
    <?php endif; ?>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if ($isStaff && !empty($weekdayCounts)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
  const labels = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
  const data   = <?= json_encode(array_values($weekdayCounts)) ?>;

  const ctx = document.getElementById('weekdayChart');
  if (!ctx) return;

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Reservas',
        data,
        backgroundColor: 'rgba(99,102,241,0.15)',
        borderColor:     'rgba(99,102,241,0.8)',
        borderWidth: 2,
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => ` ${ctx.parsed.y} reserva${ctx.parsed.y !== 1 ? 's' : ''}`
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1, color: '#94a3b8', font: { size: 11 } },
          grid:  { color: '#f1f5f9' },
        },
        x: {
          ticks: { color: '#64748b', font: { size: 11 } },
          grid:  { display: false },
        }
      }
    }
  });
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
