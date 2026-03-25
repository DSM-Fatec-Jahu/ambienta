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

    <!-- ── Recurring series ──────────────────────────────────── -->
    <?php if (!empty($seriesSiblings)): ?>
    <div class="card">
      <div class="card-header">
        <h2 class="text-sm font-semibold text-slate-900">Série recorrente</h2>
        <span class="text-xs text-slate-400"><?= count($seriesSiblings) ?> ocorrência(s)</span>
      </div>
      <div class="overflow-x-auto">
        <table class="table-base">
          <thead>
            <tr>
              <th>Data</th>
              <th>Horário</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($seriesSiblings as $s):
              $sClass = match($s['status']) {
                'approved'  => 'badge-approved',
                'rejected'  => 'badge-rejected',
                'cancelled' => 'badge-cancelled',
                'absent'    => 'badge-absent',
                default     => 'badge-pending',
              };
              $sLabel = match($s['status']) {
                'approved'  => 'Aprovada',
                'rejected'  => 'Recusada',
                'cancelled' => 'Cancelada',
                'absent'    => 'Ausente',
                default     => 'Pendente',
              };
              $isCurrent = ($s['id'] == $booking['id']);
            ?>
            <tr class="<?= $isCurrent ? 'bg-primary/5' : '' ?>">
              <td class="font-medium <?= $isCurrent ? 'text-primary' : '' ?>">
                <?= date('d/m/Y', strtotime($s['date'])) ?>
                <?php if ($isCurrent): ?><span class="ml-1 text-2xs text-primary font-semibold">(esta)</span><?php endif; ?>
              </td>
              <td><?= substr($s['start_time'], 0, 5) ?>–<?= substr($s['end_time'], 0, 5) ?></td>
              <td><span class="<?= $sClass ?> text-xs"><?= $sLabel ?></span></td>
              <td>
                <?php if (!$isCurrent): ?>
                <a href="<?= base_url('reservas/' . $s['id']) ?>"
                   class="text-xs text-primary hover:underline">Ver</a>
                <?php endif; ?>
              </td>
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

    <!-- ── Comments thread ───────────────────────────────────── -->
    <div class="card" id="comentarios">
      <div class="card-header">
        <h2 class="text-sm font-semibold text-slate-900">Comentários</h2>
        <span class="text-xs text-slate-400"><?= count($bookingComments) ?> mensagem(ns)</span>
      </div>

      <?php if (!empty($bookingComments)): ?>
      <div class="divide-y divide-slate-50">
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
        <?php foreach ($bookingComments as $c):
          $authorInitial = strtoupper(substr($c['author_name'] ?? 'U', 0, 1));
          $isMe = ($c['author_id'] == ($currentUser['id'] ?? 0));
        ?>
        <div class="px-4 py-3 flex gap-3">
          <!-- Avatar -->
          <?php if (!empty($c['avatar_path'])): ?>
            <img src="<?= base_url('uploads/avatars/' . esc($c['avatar_path'])) ?>"
                 alt="Avatar" class="h-7 w-7 rounded-full object-cover flex-shrink-0 mt-0.5">
          <?php else: ?>
            <div class="h-7 w-7 rounded-full bg-primary/20 flex items-center justify-center
                        text-xs font-bold text-primary flex-shrink-0 mt-0.5">
              <?= $authorInitial ?>
            </div>
          <?php endif; ?>

          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <span class="text-xs font-semibold text-slate-800">
                <?= esc($c['author_name'] ?? 'Usuário') ?>
                <?php if ($isMe): ?><span class="text-primary">(você)</span><?php endif; ?>
              </span>
              <?php if (isset($roleLabels[$c['author_role']])): ?>
              <span class="text-2xs text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">
                <?= $roleLabels[$c['author_role']] ?>
              </span>
              <?php endif; ?>
              <span class="ml-auto text-2xs text-slate-400 flex-shrink-0">
                <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?>
              </span>
            </div>
            <p class="text-sm text-slate-700 whitespace-pre-line"><?= esc($c['body']) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="card-body text-center py-6">
        <p class="text-xs text-slate-400">Nenhum comentário ainda. Seja o primeiro!</p>
      </div>
      <?php endif; ?>

      <!-- Comment form -->
      <?php if (in_array($booking['status'], ['pending', 'approved', 'rejected', 'cancelled', 'absent'])): ?>
      <div class="card-body border-t border-slate-100">
        <form method="POST" action="<?= base_url('reservas/' . $booking['id'] . '/comentario') ?>">
          <?= csrf_field() ?>
          <textarea name="body" rows="2" maxlength="1000"
                    class="form-input resize-none text-sm mb-2"
                    placeholder="Escreva um comentário ou observação..."></textarea>
          <div class="flex justify-end">
            <button type="submit" class="btn-primary btn-sm">Enviar</button>
          </div>
        </form>
      </div>
      <?php endif; ?>
    </div>

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
        <?php if (!empty($bookedBy)): ?>
        <div class="flex justify-between py-2 border-b border-slate-100">
          <span class="text-sm text-slate-500">Criado por</span>
          <span class="text-sm font-medium text-slate-800"><?= esc($bookedBy['name']) ?></span>
        </div>
        <?php endif; ?>
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
        <?php if (!empty($booking['checkin_at'])): ?>
        <div class="pt-2 border-t border-slate-100">
          <span class="inline-flex items-center gap-1 text-emerald-600 font-medium text-xs">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0"/>
            </svg>
            Check-in realizado
          </span>
          <p class="text-slate-400 text-xs mt-0.5"><?= date('d/m/Y H:i', strtotime($booking['checkin_at'])) ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Check-in -->
    <?php if ($canCheckIn): ?>
    <div class="card border-2 border-emerald-400">
      <div class="card-header bg-emerald-50">
        <h2 class="text-sm font-semibold text-emerald-800">Check-in disponível</h2>
      </div>
      <div class="card-body">
        <p class="text-xs text-slate-600 mb-3">
          O check-in está aberto desde as <strong><?= $checkinWindowStart ?></strong>.
          Confirme sua presença para registrar o uso do ambiente.
        </p>
        <form method="POST" action="<?= base_url('reservas/' . $booking['id'] . '/checkin') ?>">
          <?= csrf_field() ?>
          <button type="submit" class="btn-primary w-full bg-emerald-600 hover:bg-emerald-700 border-emerald-600">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0"/>
            </svg>
            Fazer Check-in
          </button>
        </form>
      </div>
    </div>
    <?php elseif ($booking['status'] === 'approved' && $booking['date'] === date('Y-m-d') && empty($booking['checkin_at'])): ?>
    <div class="card">
      <div class="card-body text-center text-xs text-slate-500">
        <svg class="w-5 h-5 mx-auto text-slate-300 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0"/>
        </svg>
        Check-in disponível a partir das <strong><?= $checkinWindowStart ?></strong>
        (<?= $checkinSettings['checkin_window_min'] ?> min antes do início).
      </div>
    </div>
    <?php endif; ?>

    <!-- QR Code check-in -->
    <?php if (!empty($qrCheckinUrl) && in_array($booking['status'], ['pending', 'approved']) && empty($booking['checkin_at'])): ?>
    <div class="card">
      <div class="card-header">
        <h2 class="text-sm font-semibold text-slate-900">QR Code de Check-in</h2>
        <span class="text-xs text-slate-400">Apresente ao responsável</span>
      </div>
      <div class="card-body flex flex-col items-center gap-3">
        <div id="qr-container" class="p-2 bg-white rounded-lg border border-slate-200 shadow-sm"></div>
        <p class="text-xs text-slate-500 text-center">
          Escaneie para registrar presença no dia da reserva.
        </p>
        <a href="<?= esc($qrCheckinUrl) ?>" target="_blank"
           class="text-xs text-primary hover:underline truncate max-w-full">
          <?= esc($qrCheckinUrl) ?>
        </a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <?php if (in_array($booking['status'], ['pending', 'approved'])): ?>
    <div class="card" x-data="{ showCancel: false, showCancelSeries: false }">
      <div class="card-body space-y-2">
        <?php if ($booking['status'] === 'pending'): ?>
        <a href="<?= base_url('reservas/' . $booking['id'] . '/editar') ?>" class="btn-secondary w-full text-center">
          <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
          </svg>
          Editar Reserva
        </a>
        <?php endif; ?>
        <button @click="showCancel = !showCancel; showCancelSeries = false" class="btn-danger w-full">
          Cancelar Reserva
        </button>

        <?php
          $isSeries = !empty($booking['recurrence_type']) && $booking['recurrence_type'] !== 'none';
          $hasFutureSiblings = false;
          if ($isSeries && !empty($seriesSiblings)) {
              foreach ($seriesSiblings as $s) {
                  if ($s['date'] >= date('Y-m-d') && $s['id'] != $booking['id']) {
                      $hasFutureSiblings = true;
                      break;
                  }
              }
          }
        ?>
        <?php if ($isSeries && $hasFutureSiblings): ?>
        <button @click="showCancelSeries = !showCancelSeries; showCancel = false" class="btn-secondary w-full text-red-600 border-red-200 hover:bg-red-50">
          Cancelar toda a série
        </button>
        <?php endif; ?>

        <div x-show="showCancel" x-cloak class="mt-1" x-transition>
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

        <div x-show="showCancelSeries" x-cloak class="mt-1" x-transition>
          <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-3">
            <p class="text-xs text-red-700 font-medium">Atenção: todas as ocorrências futuras desta série serão canceladas.</p>
          </div>
          <form method="POST" action="<?= base_url('reservas/' . $booking['id'] . '/cancelar-serie') ?>">
            <?= csrf_field() ?>
            <div class="flex gap-2">
              <button type="button" @click="showCancelSeries = false" class="btn-secondary flex-1">Não</button>
              <button type="submit" class="btn-danger flex-1">Cancelar série</button>
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
<?php if (!empty($qrCheckinUrl) && in_array($booking['status'], ['pending', 'approved']) && empty($booking['checkin_at'])): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var container = document.getElementById('qr-container');
  if (container) {
    new QRCode(container, {
      text: <?= json_encode($qrCheckinUrl) ?>,
      width: 160,
      height: 160,
      colorDark: '#1e293b',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.M,
    });
  }
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
