<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Minhas Reservas</h1>
    <p class="page-subtitle">Histórico e status das suas solicitações</p>
  </div>
  <div class="flex items-center gap-2">
    <a href="<?= base_url('reservas/calendario.ics') ?>"
       class="btn-secondary text-xs"
       title="Exportar reservas aprovadas para Google Calendar, Outlook, etc.">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
      </svg>
      iCal
    </a>
    <a href="<?= base_url('reservas/nova') ?>" class="btn-primary">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Nova Reserva
    </a>
  </div>
</div>

<?php if (!empty($overdueReturnCount) && $overdueReturnCount > 0): ?>
<div class="mb-4 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
  <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
  </svg>
  <span>
    Você tem <strong><?= $overdueReturnCount ?> recurso(s)</strong> com devolução pendente e prazo vencido.
    Regularize para poder criar novas reservas.
  </span>
</div>
<?php endif; ?>

<!-- Status filter tabs -->
<div class="flex items-center gap-1 mb-4 bg-white border border-slate-200 rounded-xl p-1 w-fit">
  <?php
  $tabs = [
    ''           => 'Todas',
    'pending'    => 'Pendentes',
    'approved'   => 'Aprovadas',
    'rejected'   => 'Recusadas',
    'cancelled'  => 'Canceladas',
  ];
  foreach ($tabs as $key => $label):
    $active = ($filter === $key);
  ?>
    <a href="<?= base_url('reservas' . ($key ? '?status=' . $key : '')) ?>"
       class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors
              <?= $active ? 'bg-primary text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' ?>">
      <?= $label ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="card overflow-hidden">
  <?php if (empty($items)): ?>
    <div class="empty-state">
      <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
      <p class="empty-state-title">Nenhuma reserva encontrada</p>
      <p class="empty-state-description">Você ainda não fez reservas<?= $filter ? ' com este status' : '' ?>.</p>
      <a href="<?= base_url('reservas/nova') ?>" class="btn-primary mt-4">Fazer reserva</a>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="table-base">
        <thead>
          <tr>
            <th>Título</th>
            <th>Ambiente</th>
            <th>Data</th>
            <th>Horário</th>
            <th>Status</th>
            <th class="text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $bk): ?>
          <?php
            $badgeClass = match($bk['status']) {
              'approved'  => 'badge-approved',
              'rejected'  => 'badge-rejected',
              'cancelled' => 'badge-cancelled',
              'absent'    => 'badge-absent',
              default     => 'badge-pending',
            };
            $statusLabel = match($bk['status']) {
              'approved'  => 'Aprovada',
              'rejected'  => 'Recusada',
              'cancelled' => 'Cancelada',
              'absent'    => 'Ausente',
              default     => 'Pendente',
            };
          ?>
          <tr>
            <td>
              <div class="font-medium text-slate-900"><?= esc($bk['title']) ?></div>
              <?php if ($bk['review_notes'] && in_array($bk['status'], ['rejected', 'approved'])): ?>
                <div class="text-xs text-slate-400 mt-0.5 truncate max-w-xs"><?= esc($bk['review_notes']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <div class="text-slate-700"><?= esc($bk['room_name'] ?? '—') ?></div>
              <?php if (!empty($bk['building_name'])): ?>
                <div class="text-xs text-slate-400"><?= esc($bk['building_name']) ?></div>
              <?php endif; ?>
            </td>
            <td class="whitespace-nowrap">
              <?= date('d/m/Y', strtotime($bk['date'])) ?>
              <div class="text-xs text-slate-400"><?= date('D', strtotime($bk['date'])) ?></div>
            </td>
            <td class="whitespace-nowrap text-sm">
              <?= substr($bk['start_time'], 0, 5) ?> – <?= substr($bk['end_time'], 0, 5) ?>
            </td>
            <td><span class="<?= $badgeClass ?>"><?= $statusLabel ?></span></td>
            <td class="text-right whitespace-nowrap">
              <div class="flex items-center justify-end gap-1">
                <a href="<?= base_url('reservas/' . $bk['id']) ?>"
                   class="btn-ghost p-2 text-blue-500 hover:bg-blue-50 hover:text-blue-600" aria-label="Ver detalhes">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
                         -1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </a>

                <?php if (in_array($bk['status'], ['pending', 'approved'])): ?>
                  <form method="POST" action="<?= base_url('reservas/' . $bk['id'] . '/cancelar') ?>"
                        x-data
                        @submit.prevent="if(confirm('Cancelar a reserva «<?= esc($bk['title']) ?>»?')) $el.submit()">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-ghost btn-sm px-2 py-1 text-xs text-red-500 hover:bg-red-50">
                      Cancelar
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer text-xs text-slate-400">
      <?= count($items) ?> reserva<?= count($items) !== 1 ? 's' : '' ?>
    </div>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>
