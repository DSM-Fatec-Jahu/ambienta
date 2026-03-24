<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Minha Lista de Espera</h1>
    <p class="page-subtitle">Você será notificado quando uma vaga abrir nos horários abaixo</p>
  </div>
  <a href="<?= base_url('reservas/nova') ?>" class="btn-primary">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    Nova Reserva
  </a>
</div>

<?php if (empty($entries)): ?>
  <div class="card">
    <div class="card-body py-16 flex flex-col items-center text-center gap-3">
      <svg class="w-12 h-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0"/>
      </svg>
      <p class="text-sm font-medium text-slate-500">Nenhuma entrada na lista de espera</p>
      <p class="text-xs text-slate-400">Quando um ambiente estiver ocupado, você pode entrar na lista para ser avisado.</p>
    </div>
  </div>

<?php else: ?>
  <div class="card divide-y divide-slate-100">
    <?php foreach ($entries as $entry): ?>
      <div class="flex items-start gap-4 px-4 py-4">

        <!-- Date block -->
        <div class="flex-shrink-0 w-12 text-center">
          <p class="text-lg font-bold text-slate-800 leading-none">
            <?= date('d', strtotime($entry['date'])) ?>
          </p>
          <p class="text-2xs font-medium text-slate-400 uppercase">
            <?= strftime('%b', strtotime($entry['date'])) ?>
          </p>
        </div>

        <!-- Info -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-slate-800">
            <?= esc($entry['room_name'] ?? 'Ambiente') ?>
            <?php if (!empty($entry['room_code'])): ?>
              <span class="font-normal text-slate-400">(<?= esc($entry['room_code']) ?>)</span>
            <?php endif; ?>
          </p>
          <p class="text-xs text-slate-500 mt-0.5">
            <?php if (!empty($entry['building_name'])): ?>
              <?= esc($entry['building_name']) ?> &middot;
            <?php endif; ?>
            <?= substr($entry['starts_at'], 0, 5) ?> – <?= substr($entry['ends_at'], 0, 5) ?>
          </p>
          <?php if (!empty($entry['notes'])): ?>
            <p class="text-xs text-slate-400 mt-1 italic"><?= esc($entry['notes']) ?></p>
          <?php endif; ?>
          <p class="text-2xs text-slate-400 mt-1">
            Entrou em <?= date('d/m/Y H:i', strtotime($entry['created_at'])) ?>
            <?php if (!empty($entry['notified_at'])): ?>
              &middot; <span class="text-green-600 font-medium">Notificado em <?= date('d/m H:i', strtotime($entry['notified_at'])) ?></span>
            <?php endif; ?>
          </p>
        </div>

        <!-- Actions -->
        <div class="flex-shrink-0">
          <form method="POST"
                action="<?= base_url('reservas/lista-espera/' . $entry['id'] . '/sair') ?>"
                onsubmit="return confirm('Sair da lista de espera para este horário?')">
            <?= csrf_field() ?>
            <button type="submit"
                    class="text-xs text-red-500 hover:text-red-700 hover:underline transition-colors">
              Sair da fila
            </button>
          </form>
        </div>

      </div>
    <?php endforeach; ?>
  </div>

  <p class="text-xs text-slate-400 mt-3 text-center">
    Entradas expiram automaticamente quando a data passar.
  </p>
<?php endif; ?>

<?= $this->endSection() ?>
