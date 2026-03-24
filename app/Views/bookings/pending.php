<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<style>[x-cloak]{display:none!important}</style>

<!-- Hidden batch-approve form (populated by Alpine on submit) -->
<form id="batch-form" method="POST" action="">
  <?= csrf_field() ?>
</form>

<div x-data="batchManager()">

<div class="page-header">
  <div>
    <h1 class="page-title">Aprovação de Reservas</h1>
    <p class="page-subtitle">Solicitações aguardando análise</p>
  </div>
  <?php if (!empty($items)): ?>
    <span class="badge-pending text-sm px-3 py-1"><?= count($items) ?> pendente<?= count($items) !== 1 ? 's' : '' ?></span>
  <?php endif; ?>
</div>

<?php if (empty($items)): ?>
  <div class="card">
    <div class="empty-state">
      <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <p class="empty-state-title">Nenhuma reserva pendente</p>
      <p class="empty-state-description">Todas as solicitações foram analisadas.</p>
    </div>
  </div>
<?php else: ?>

  <!-- Select-all row -->
  <div class="mb-3 flex items-center gap-3">
    <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
      <input type="checkbox" @change="toggleAll($event.target.checked)"
             class="rounded border-slate-300 text-primary">
      Selecionar todas
    </label>
  </div>

  <!-- Batch action bar (visible when at least one is selected) -->
  <div x-show="selectedCount > 0" x-cloak
       class="mb-4 flex flex-wrap items-center gap-3 rounded-lg border border-primary/30 bg-primary/5 p-3">
    <span class="text-sm font-medium text-slate-700" x-text="`${selectedCount} reserva(s) selecionada(s)`"></span>
    <div class="ml-auto flex gap-2">
      <button type="button" @click="submitBatch('<?= base_url('reservas/lote/aprovar') ?>')"
              class="btn btn-sm btn-primary">
        Aprovar selecionadas
      </button>
      <button type="button" @click="openRejectModal()"
              class="btn btn-sm btn-danger">
        Recusar selecionadas
      </button>
    </div>
  </div>

  <div class="space-y-4">
    <?php foreach ($items as $bk): ?>
    <div class="card" x-data="{ showApprove: false, showReject: false }">

      <!-- Card header -->
      <div class="card-header">
        <div class="flex items-start gap-3 flex-1 min-w-0">
          <!-- Per-card checkbox (standalone, not inside any form) -->
          <input type="checkbox" name="ids[]" value="<?= $bk['id'] ?>"
                 @change="toggleId(<?= $bk['id'] ?>, $event.target.checked)"
                 class="mt-1 rounded border-slate-300 text-primary flex-shrink-0">
          <div class="flex-1 min-w-0">
            <div class="flex items-start gap-2 flex-wrap">
              <h2 class="text-sm font-semibold text-slate-900"><?= esc($bk['title']) ?></h2>
              <span class="badge-pending">Pendente</span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">
              Solicitado em <?= date('d/m/Y H:i', strtotime($bk['created_at'])) ?>
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
          <button @click="showApprove = !showApprove; showReject = false"
                  :class="showApprove ? 'ring-2 ring-success' : ''"
                  class="btn-secondary btn-sm text-success hover:bg-emerald-50">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Aprovar
          </button>
          <button @click="showReject = !showReject; showApprove = false"
                  :class="showReject ? 'ring-2 ring-danger' : ''"
                  class="btn-secondary btn-sm text-red-600 hover:bg-red-50">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Recusar
          </button>
        </div>
      </div>

      <!-- Booking details -->
      <div class="card-body">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Solicitante</p>
            <p class="text-sm text-slate-900 font-medium mt-0.5"><?= esc($bk['user_name']) ?></p>
            <p class="text-xs text-slate-400 truncate"><?= esc($bk['user_email']) ?></p>
          </div>

          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Ambiente</p>
            <p class="text-sm text-slate-900 font-medium mt-0.5"><?= esc($bk['room_name'] ?? '—') ?></p>
            <p class="text-xs text-slate-400"><?= esc($bk['building_name'] ?? '') ?></p>
          </div>

          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Data</p>
            <p class="text-sm text-slate-900 font-medium mt-0.5">
              <?= date('d/m/Y', strtotime($bk['date'])) ?>
            </p>
            <p class="text-xs text-slate-400"><?= date('l', strtotime($bk['date'])) ?></p>
          </div>

          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Horário</p>
            <p class="text-sm text-slate-900 font-medium mt-0.5">
              <?= substr($bk['start_time'], 0, 5) ?> – <?= substr($bk['end_time'], 0, 5) ?>
            </p>
            <p class="text-xs text-slate-400"><?= $bk['attendees_count'] ?> participante<?= $bk['attendees_count'] != 1 ? 's' : '' ?></p>
          </div>

        </div>

        <?php if ($bk['description']): ?>
          <div class="mt-3 pt-3 border-t border-slate-100">
            <p class="text-xs text-slate-400 mb-1">Descrição:</p>
            <p class="text-sm text-slate-600"><?= esc($bk['description']) ?></p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Approve form -->
      <div x-show="showApprove" x-cloak x-transition
           class="px-5 pb-4 border-t border-emerald-100 bg-emerald-50/50">
        <form method="POST" action="<?= base_url('reservas/' . $bk['id'] . '/aprovar') ?>" class="pt-3">
          <?= csrf_field() ?>
          <label for="approve_notes_<?= $bk['id'] ?>" class="form-label">Observação (opcional)</label>
          <textarea id="approve_notes_<?= $bk['id'] ?>" name="notes" rows="2"
                    class="form-input resize-none mb-3"
                    placeholder="Mensagem ao solicitante..."></textarea>
          <div class="flex gap-2">
            <button type="button" @click="showApprove = false" class="btn-secondary btn-sm">Cancelar</button>
            <button type="submit" class="btn-primary btn-sm bg-success border-success hover:bg-success/90">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              Confirmar Aprovação
            </button>
          </div>
        </form>
      </div>

      <!-- Reject form -->
      <div x-show="showReject" x-cloak x-transition
           class="px-5 pb-4 border-t border-red-100 bg-red-50/50">
        <form method="POST" action="<?= base_url('reservas/' . $bk['id'] . '/recusar') ?>" class="pt-3">
          <?= csrf_field() ?>
          <label for="reject_notes_<?= $bk['id'] ?>" class="form-label form-label-required">
            Motivo da recusa <span class="text-red-500">*</span>
          </label>
          <textarea id="reject_notes_<?= $bk['id'] ?>" name="notes" rows="2"
                    class="form-input resize-none mb-3" required
                    placeholder="Informe o motivo da recusa ao solicitante..."></textarea>
          <div class="flex gap-2">
            <button type="button" @click="showReject = false" class="btn-secondary btn-sm">Cancelar</button>
            <button type="submit" class="btn-danger btn-sm">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
              Confirmar Recusa
            </button>
          </div>
        </form>
      </div>

    </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<?php if (!empty($approved)): ?>
<div class="mt-10">
  <h2 class="text-base font-semibold text-slate-700 mb-3">Reservas aprovadas — marcar ausência</h2>
  <p class="text-xs text-slate-400 mb-4">Reservas aprovadas de hoje e anteriores onde o usuário não compareceu.</p>

  <div class="card overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
      <thead class="bg-slate-50">
        <tr>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Título</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Solicitante</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Ambiente</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Data / Hora</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($approved as $ap): ?>
        <tr class="hover:bg-slate-50">
          <td class="px-4 py-3 font-medium text-slate-900"><?= esc($ap['title']) ?></td>
          <td class="px-4 py-3 text-slate-600"><?= esc($ap['user_name'] ?? '—') ?></td>
          <td class="px-4 py-3 text-slate-600">
            <?= esc($ap['room_name'] ?? '—') ?>
            <?php if (!empty($ap['building_name'])): ?>
              <span class="text-slate-400">· <?= esc($ap['building_name']) ?></span>
            <?php endif; ?>
          </td>
          <td class="px-4 py-3 text-slate-600">
            <?= date('d/m/Y', strtotime($ap['date'])) ?>
            <span class="text-slate-400"><?= substr($ap['start_time'], 0, 5) ?>–<?= substr($ap['end_time'], 0, 5) ?></span>
          </td>
          <td class="px-4 py-3 text-right">
            <form method="POST" action="<?= base_url('reservas/' . $ap['id'] . '/ausente') ?>"
                  onsubmit="return confirm('Marcar como ausente?')">
              <?= csrf_field() ?>
              <button type="submit" class="btn-secondary btn-sm text-purple-700 hover:bg-purple-50">
                Ausente
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Bulk reject modal -->
<div x-show="rejectModalOpen" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
  <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
    <h3 class="mb-4 text-lg font-semibold text-slate-800">Recusar reservas selecionadas</h3>
    <form :action="batchRejectUrl" method="POST">
      <?= csrf_field() ?>
      <template x-for="id in selectedIds" :key="id">
        <input type="hidden" name="ids[]" :value="id">
      </template>
      <div class="mb-4">
        <label class="form-label">Motivo da recusa</label>
        <textarea name="notes" rows="3" required
                  class="form-input"
                  placeholder="Informe o motivo que será enviado a todos os solicitantes..."></textarea>
      </div>
      <div class="flex justify-end gap-3">
        <button type="button" @click="rejectModalOpen = false" class="btn btn-secondary">Cancelar</button>
        <button type="submit" class="btn btn-danger">Recusar selecionadas</button>
      </div>
    </form>
  </div>
</div>

</div><!-- end x-data="batchManager()" -->

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function batchManager() {
  return {
    selectedIds: [],
    rejectModalOpen: false,
    batchRejectUrl: '<?= base_url('reservas/lote/recusar') ?>',
    get selectedCount() { return this.selectedIds.length; },
    toggleId(id, checked) {
      if (checked) { this.selectedIds.push(id); }
      else { this.selectedIds = this.selectedIds.filter(i => i !== id); }
    },
    toggleAll(checked) {
      const boxes = document.querySelectorAll('input[name="ids[]"]');
      boxes.forEach(b => {
        b.checked = checked;
        const id = parseInt(b.value);
        if (checked && !this.selectedIds.includes(id)) {
          this.selectedIds.push(id);
        }
      });
      if (!checked) this.selectedIds = [];
    },
    submitBatch(url) {
      if (this.selectedIds.length === 0) return;
      const form = document.getElementById('batch-form');
      // Remove any previously appended id inputs
      form.querySelectorAll('input[data-batch-id]').forEach(el => el.remove());
      this.selectedIds.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'ids[]';
        inp.value = id;
        inp.dataset.batchId = id;
        form.appendChild(inp);
      });
      form.action = url;
      form.submit();
    },
    openRejectModal() {
      if (this.selectedIds.length === 0) return;
      this.rejectModalOpen = true;
    }
  }
}
</script>
<?= $this->endSection() ?>
