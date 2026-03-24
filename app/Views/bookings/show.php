<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<?php
$badgeClass = match($booking['status']) {
  'approved'  => 'badge-approved',
  'rejected'  => 'badge-rejected',
  'cancelled' => 'badge-cancelled',
  'absent'    => 'badge-absent',
  default     => 'badge-pending',
};
$statusLabel = match($booking['status']) {
  'approved'  => 'Aprovada',
  'rejected'  => 'Recusada',
  'cancelled' => 'Cancelada',
  'absent'    => 'Ausente',
  default     => 'Pendente',
};
?>

<div class="page-header">
  <div class="flex items-center gap-3">
    <a href="<?= base_url('reservas') ?>" class="btn-ghost btn-sm p-1.5" aria-label="Voltar">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
      </svg>
    </a>
    <div>
      <h1 class="page-title"><?= esc($booking['title']) ?></h1>
      <p class="page-subtitle">Reserva #<?= $booking['id'] ?></p>
    </div>
  </div>
  <span class="<?= $badgeClass ?> text-sm px-3 py-1"><?= $statusLabel ?></span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- Main info -->
  <div class="lg:col-span-2 space-y-4">

    <div class="card">
      <div class="card-header">
        <h2 class="text-sm font-semibold text-slate-900">Detalhes</h2>
      </div>
      <div class="card-body">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">

          <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Ambiente</dt>
            <dd class="mt-1 text-sm text-slate-900 font-medium"><?= esc($room['name'] ?? '—') ?></dd>
            <?php if (!empty($room['code'])): ?>
              <dd class="text-xs text-slate-400"><?= esc($room['code']) ?></dd>
            <?php endif; ?>
          </div>

          <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Data</dt>
            <dd class="mt-1 text-sm text-slate-900 font-medium">
              <?= date('d/m/Y (l)', strtotime($booking['date'])) ?>
            </dd>
          </div>

          <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Horário</dt>
            <dd class="mt-1 text-sm text-slate-900 font-medium">
              <?= substr($booking['start_time'], 0, 5) ?> – <?= substr($booking['end_time'], 0, 5) ?>
            </dd>
          </div>

          <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Participantes</dt>
            <dd class="mt-1 text-sm text-slate-900 font-medium"><?= $booking['attendees_count'] ?> pessoa(s)</dd>
          </div>

          <?php if ($booking['description']): ?>
          <div class="sm:col-span-2">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Descrição</dt>
            <dd class="mt-1 text-sm text-slate-700"><?= nl2br(esc($booking['description'])) ?></dd>
          </div>
          <?php endif; ?>

        </dl>
      </div>
    </div>

    <?php if (!empty($equipItems)): ?>
    <div class="card">
      <div class="card-header">
        <h2 class="text-sm font-semibold text-slate-900">Equipamentos solicitados</h2>
      </div>
      <div class="overflow-x-auto">
        <table class="table-base">
          <thead>
            <tr>
              <th>Equipamento</th>
              <th>Código</th>
              <th class="text-center">Qtd.</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($equipItems as $e): ?>
            <tr>
              <td class="font-medium"><?= esc($e['equipment_name']) ?></td>
              <td><?= esc($e['code'] ?? '—') ?></td>
              <td class="text-center"><?= $e['quantity'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Rating section ─────────────────────────────────────── -->
    <?php if ($canRate): ?>
    <div class="card" x-data="ratingWidget()">
      <div class="card-header">
        <h2 class="text-sm font-semibold text-slate-900">Avaliar reserva</h2>
        <span class="text-xs text-slate-400">Como foi o uso do ambiente?</span>
      </div>
      <div class="card-body">
        <form method="POST" action="<?= base_url('reservas/' . $booking['id'] . '/avaliar') ?>">
          <?= csrf_field() ?>

          <!-- Stars -->
          <div class="flex items-center gap-1 mb-4">
            <?php for ($s = 1; $s <= 5; $s++): ?>
            <button type="button" @click="setRating(<?= $s ?>)"
                    @mouseover="hover = <?= $s ?>" @mouseleave="hover = 0"
                    class="focus:outline-none">
              <svg class="w-8 h-8 transition-colors"
                   :class="(hover || selected) >= <?= $s ?> ? 'text-amber-400' : 'text-slate-200'"
                   fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
              </svg>
            </button>
            <?php endfor; ?>
            <span class="ml-2 text-sm text-slate-500" x-text="ratingLabel"></span>
          </div>

          <input type="hidden" name="rating" :value="selected">

          <label for="rating_comment" class="form-label">Comentário <span class="text-slate-400">(opcional)</span></label>
          <textarea id="rating_comment" name="comment" rows="3"
                    class="form-input resize-none mb-4"
                    placeholder="Descreva sua experiência com o ambiente..."></textarea>

          <button type="submit" class="btn-primary" :disabled="selected === 0"
                  x-bind:class="selected === 0 ? 'opacity-50 cursor-not-allowed' : ''">
            Enviar avaliação
          </button>
        </form>
      </div>
    </div>

    <?php elseif ($existingRating): ?>
    <div class="card">
      <div class="card-header">
        <h2 class="text-sm font-semibold text-slate-900">Sua avaliação</h2>
      </div>
      <div class="card-body">
        <div class="flex items-center gap-1 mb-2">
          <?php for ($s = 1; $s <= 5; $s++): ?>
          <svg class="w-5 h-5 <?= $s <= $existingRating['rating'] ? 'text-amber-400' : 'text-slate-200' ?>"
               fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
          </svg>
          <?php endfor; ?>
          <span class="ml-1 text-sm font-semibold text-slate-700"><?= $existingRating['rating'] ?>/5</span>
        </div>
        <?php if ($existingRating['comment']): ?>
          <p class="text-sm text-slate-600 italic">"<?= esc($existingRating['comment']) ?>"</p>
        <?php endif; ?>
        <p class="text-xs text-slate-400 mt-2">
          Avaliado em <?= date('d/m/Y', strtotime($existingRating['created_at'])) ?>
        </p>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Sidebar: status + actions -->
  <div class="space-y-4">

    <!-- Status card -->
    <div class="card">
      <div class="card-header">
        <h2 class="text-sm font-semibold text-slate-900">Status</h2>
        <span class="<?= $badgeClass ?>"><?= $statusLabel ?></span>
      </div>
      <div class="card-body space-y-2 text-xs text-slate-500">
        <div>
          <span class="font-medium text-slate-600">Criada em:</span>
          <?= date('d/m/Y H:i', strtotime($booking['created_at'])) ?>
        </div>
        <?php if ($booking['reviewed_at']): ?>
        <div>
          <span class="font-medium text-slate-600">Revisada em:</span>
          <?= date('d/m/Y H:i', strtotime($booking['reviewed_at'])) ?>
        </div>
        <?php endif; ?>
        <?php if ($booking['review_notes']): ?>
        <div class="pt-2 border-t border-slate-100">
          <p class="font-medium text-slate-600 mb-1">Observação:</p>
          <p class="text-slate-500 italic"><?= esc($booking['review_notes']) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($booking['cancelled_at']): ?>
        <div class="pt-2 border-t border-slate-100">
          <p class="font-medium text-slate-600 mb-1">Motivo do cancelamento:</p>
          <p class="text-slate-500 italic"><?= esc($booking['cancelled_reason'] ?? '—') ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Actions -->
    <?php if (in_array($booking['status'], ['pending', 'approved'])): ?>
    <div class="card" x-data="{ showCancel: false }">
      <div class="card-body">
        <button @click="showCancel = !showCancel" class="btn-danger w-full">
          Cancelar Reserva
        </button>

        <div x-show="showCancel" x-cloak class="mt-3" x-transition>
          <form method="POST" action="<?= base_url('reservas/' . $booking['id'] . '/cancelar') ?>">
            <?= csrf_field() ?>
            <label for="cancel_reason" class="form-label">Motivo (opcional)</label>
            <textarea id="cancel_reason" name="reason" rows="2"
                      class="form-input resize-none mb-3"
                      placeholder="Informe o motivo do cancelamento..."></textarea>
            <div class="flex gap-2">
              <button type="button" @click="showCancel = false" class="btn-secondary flex-1">Não</button>
              <button type="submit" class="btn-danger flex-1">Confirmar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function ratingWidget() {
  return {
    selected: 0,
    hover: 0,
    labels: ['', 'Péssimo', 'Ruim', 'Regular', 'Bom', 'Excelente'],
    get ratingLabel() { return this.labels[this.hover || this.selected] || ''; },
    setRating(val) { this.selected = val; },
  };
}
</script>
<?= $this->endSection() ?>
