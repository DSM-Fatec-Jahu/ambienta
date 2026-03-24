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
  <?php endif; ?>

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

</div>

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
  </div>
</div>

<?= $this->endSection() ?>
