<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Recursos de Reservas</h1>
    <p class="page-subtitle">Gerencie aprovações e devoluções de recursos</p>
  </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert-success mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert-error mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<!-- RN-R04 / RN-R05 notice -->
<div class="mb-4 flex items-start gap-2 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
  <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0"/>
  </svg>
  <span>
    A aprovação ou recusa de recursos <strong>não afeta o status da reserva</strong>.
    Devoluções registradas pelo solicitante requerem <strong>confirmação do técnico</strong>.
  </span>
</div>

<div class="card overflow-hidden" x-data="bookingResourcePanel()">

  <!-- Tabs -->
  <div class="border-b border-slate-200">
    <nav class="-mb-px flex overflow-x-auto">

      <button @click="tab = 'pending'"
              :class="tab === 'pending' ? 'tab-active' : 'tab-inactive'"
              class="whitespace-nowrap px-5 py-3 text-sm font-medium">
        Pendentes de aprovação
        <?php if (!empty($pending)): ?>
          <span class="ml-1.5 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
            <?= count($pending) ?>
          </span>
        <?php endif; ?>
      </button>

      <button @click="tab = 'awaiting'"
              :class="tab === 'awaiting' ? 'tab-active' : 'tab-inactive'"
              class="whitespace-nowrap px-5 py-3 text-sm font-medium">
        Aguardando devolução
        <?php if (!empty($awaitingReturn)): ?>
          <span class="ml-1.5 rounded-full bg-orange-100 px-2 py-0.5 text-xs font-semibold text-orange-800">
            <?= count($awaitingReturn) ?>
          </span>
        <?php endif; ?>
      </button>

      <button @click="tab = 'confirm'"
              :class="tab === 'confirm' ? 'tab-active' : 'tab-inactive'"
              class="whitespace-nowrap px-5 py-3 text-sm font-medium">
        Devoluções a confirmar
        <?php if (!empty($pendingConfirmation)): ?>
          <span class="ml-1.5 rounded-full bg-teal-100 px-2 py-0.5 text-xs font-semibold text-teal-800">
            <?= count($pendingConfirmation) ?>
          </span>
        <?php endif; ?>
      </button>

    </nav>
  </div>

  <!-- ── Tab: Pendentes de aprovação ──────────────────────────────────────── -->
  <div x-show="tab === 'pending'" x-cloak>
    <?php if (empty($pending)): ?>
      <div class="empty-state">
        <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0"/>
        </svg>
        <p class="empty-state-title">Nenhuma requisição pendente</p>
        <p class="empty-state-description">Todas as requisições de recursos foram processadas.</p>
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="table-base">
          <thead>
            <tr>
              <th>Reserva</th>
              <th>Data</th>
              <th>Ambiente</th>
              <th>Recurso</th>
              <th class="text-center">Qtd</th>
              <th>Solicitante</th>
              <th class="text-center w-40">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pending as $br): ?>
            <tr>
              <td>
                <a href="<?= base_url('reservas/' . $br['booking_id']) ?>"
                   class="font-medium text-sky-700 hover:underline">
                  <?= esc($br['booking_title']) ?>
                </a>
                <span class="ml-1.5 <?= $br['booking_status'] === 'approved' ? 'badge-success' : 'badge-warning' ?>">
                  <?= $br['booking_status'] === 'approved' ? 'Aprovada' : 'Pendente' ?>
                </span>
              </td>
              <td class="text-slate-600 whitespace-nowrap">
                <?= date('d/m/Y', strtotime($br['booking_date'])) ?>
                <span class="text-xs text-slate-400 ml-1">
                  <?= substr($br['booking_start'], 0, 5) ?>–<?= substr($br['booking_end'], 0, 5) ?>
                </span>
              </td>
              <td>
                <?php if (!empty($br['room_name'])): ?>
                  <?= esc($br['room_name']) ?>
                  <?php if (!empty($br['room_abbr'])): ?>
                    <span class="text-xs text-slate-400">(<?= esc($br['room_abbr']) ?>)</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-slate-300">—</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="font-medium text-slate-800"><?= esc($br['resource_name']) ?></span>
                <?php if (!empty($br['resource_code'])): ?>
                  <span class="ml-1 badge-primary"><?= esc($br['resource_code']) ?></span>
                <?php endif; ?>
              </td>
              <td class="text-center font-semibold text-slate-700"><?= (int) $br['quantity'] ?></td>
              <td class="text-slate-600"><?= esc($br['requester_name'] ?? '—') ?></td>
              <td class="text-center">
                <div class="flex items-center justify-center gap-2">
                  <!-- Approve -->
                  <form method="POST"
                        action="<?= base_url('admin/recursos-reservas/' . $br['id'] . '/aprovar') ?>"
                        onsubmit="return confirm('Confirmar aprovação deste recurso?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-xs-success" title="Aprovar">
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                      </svg>
                      Aprovar
                    </button>
                  </form>

                  <!-- Reject — opens inline reason form -->
                  <button type="button"
                          class="btn-xs-danger"
                          @click="openReject(<?= $br['id'] ?>)">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Recusar
                  </button>
                </div>
              </td>
            </tr>

            <!-- Inline reject form row -->
            <tr x-show="rejectId === <?= $br['id'] ?>" x-cloak class="bg-red-50">
              <td colspan="7" class="px-4 py-3">
                <form method="POST"
                      action="<?= base_url('admin/recursos-reservas/' . $br['id'] . '/recusar') ?>"
                      class="flex items-end gap-3">
                  <?= csrf_field() ?>
                  <div class="flex-1">
                    <label class="form-label text-red-700">
                      Motivo da recusa <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="rejection_note"
                           required
                           maxlength="500"
                           placeholder="Informe o motivo da recusa…"
                           class="form-input border-red-300 focus:ring-red-400">
                  </div>
                  <button type="submit" class="btn-danger">Confirmar recusa</button>
                  <button type="button" class="btn-secondary" @click="rejectId = null">Cancelar</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── Tab: Aguardando devolução ────────────────────────────────────────── -->
  <div x-show="tab === 'awaiting'" x-cloak>
    <?php if (empty($awaitingReturn)): ?>
      <div class="empty-state">
        <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0"/>
        </svg>
        <p class="empty-state-title">Nenhum recurso aguardando devolução</p>
        <p class="empty-state-description">Todos os recursos de reservas encerradas foram devolvidos.</p>
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="table-base">
          <thead>
            <tr>
              <th>Reserva</th>
              <th>Encerrou em</th>
              <th>Recurso</th>
              <th class="text-center">Qtd</th>
              <th>Solicitante</th>
              <th>Atraso</th>
              <th class="text-center w-44">Ação</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($awaitingReturn as $br):
              $endDatetime = strtotime(($br['booking_date'] ?? '') . ' ' . ($br['booking_end'] ?? '23:59:59'));
              $delayHours  = max(0, (int) round((time() - $endDatetime) / 3600));
            ?>
            <tr>
              <td>
                <a href="<?= base_url('reservas/' . $br['booking_id']) ?>"
                   class="font-medium text-sky-700 hover:underline">
                  <?= esc($br['booking_title']) ?>
                </a>
              </td>
              <td class="text-slate-600 whitespace-nowrap">
                <?= date('d/m/Y', strtotime($br['booking_date'])) ?>
                <span class="text-xs text-slate-400 ml-1"><?= substr($br['booking_end'], 0, 5) ?></span>
              </td>
              <td>
                <span class="font-medium text-slate-800"><?= esc($br['resource_name']) ?></span>
                <?php if (!empty($br['resource_code'])): ?>
                  <span class="ml-1 badge-primary"><?= esc($br['resource_code']) ?></span>
                <?php endif; ?>
              </td>
              <td class="text-center font-semibold text-slate-700"><?= (int) $br['quantity'] ?></td>
              <td class="text-slate-600"><?= esc($br['requester_name'] ?? '—') ?></td>
              <td>
                <?php if ($delayHours > 0): ?>
                  <span class="text-xs font-semibold text-red-600">+<?= $delayHours ?>h</span>
                <?php else: ?>
                  <span class="text-xs text-slate-400">—</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <!-- Forced return by technician — RN-R05 -->
                <form method="POST"
                      action="<?= base_url('reservas/recursos/' . $br['id'] . '/devolver') ?>"
                      onsubmit="return confirm('Registrar devolução forçada deste recurso?')">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn-xs btn-warning whitespace-nowrap">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                    Registrar devolução
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── Tab: Devoluções a confirmar ──────────────────────────────────────── -->
  <div x-show="tab === 'confirm'" x-cloak>
    <?php if (empty($pendingConfirmation)): ?>
      <div class="empty-state">
        <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0"/>
        </svg>
        <p class="empty-state-title">Nenhuma devolução aguardando confirmação</p>
        <p class="empty-state-description">Todas as devoluções registradas já foram confirmadas.</p>
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="table-base">
          <thead>
            <tr>
              <th>Recurso</th>
              <th>Reserva</th>
              <th>Devolvido por</th>
              <th>Em</th>
              <th class="text-center w-48">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendingConfirmation as $br): ?>
            <tr>
              <td>
                <span class="font-medium text-slate-800"><?= esc($br['resource_name']) ?></span>
                <?php if (!empty($br['resource_code'])): ?>
                  <span class="ml-1 badge-primary"><?= esc($br['resource_code']) ?></span>
                <?php endif; ?>
                <span class="ml-1 text-xs text-slate-400">×<?= (int) $br['quantity'] ?></span>
              </td>
              <td>
                <a href="<?= base_url('reservas/' . $br['booking_id']) ?>"
                   class="font-medium text-sky-700 hover:underline">
                  <?= esc($br['booking_title']) ?>
                </a>
                <span class="block text-xs text-slate-400">
                  <?= date('d/m/Y', strtotime($br['booking_date'])) ?>
                </span>
              </td>
              <td class="text-slate-600">
                <?= esc($br['returned_by_name'] ?? $br['requester_name'] ?? '—') ?>
              </td>
              <td class="text-slate-600 whitespace-nowrap text-xs">
                <?= $br['returned_at'] ? date('d/m/Y H:i', strtotime($br['returned_at'])) : '—' ?>
              </td>
              <td class="text-center">
                <div class="flex items-center justify-center gap-2">
                  <!-- Confirm return -->
                  <form method="POST"
                        action="<?= base_url('admin/recursos-reservas/' . $br['id'] . '/confirmar-devolucao') ?>"
                        onsubmit="return confirm('Confirmar recebimento físico deste recurso?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-xs-success" title="Confirmar devolução">
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                      </svg>
                      Confirmar
                    </button>
                  </form>

                  <!-- Reject return — opens inline form -->
                  <button type="button"
                          class="btn-xs-danger"
                          @click="openReturnReject(<?= $br['id'] ?>)">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Rejeitar
                  </button>
                </div>
              </td>
            </tr>

            <!-- Inline reject-return form row -->
            <tr x-show="returnRejectId === <?= $br['id'] ?>" x-cloak class="bg-red-50">
              <td colspan="5" class="px-4 py-3">
                <form method="POST"
                      action="<?= base_url('admin/recursos-reservas/' . $br['id'] . '/rejeitar-devolucao') ?>"
                      class="flex items-end gap-3">
                  <?= csrf_field() ?>
                  <div class="flex-1">
                    <label class="form-label text-red-700">
                      Motivo da rejeição <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="rejection_note"
                           required
                           maxlength="500"
                           placeholder="Ex.: Recurso não localizado no estoque…"
                           class="form-input border-red-300 focus:ring-red-400">
                  </div>
                  <button type="submit" class="btn-danger">Confirmar rejeição</button>
                  <button type="button" class="btn-secondary" @click="returnRejectId = null">Cancelar</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

</div>

<script>
function bookingResourcePanel() {
  return {
    tab: 'pending',
    rejectId: null,
    returnRejectId: null,
    openReject(id) {
      this.rejectId = this.rejectId === id ? null : id;
    },
    openReturnReject(id) {
      this.returnRejectId = this.returnRejectId === id ? null : id;
    },
  };
}
</script>

<?= $this->endSection() ?>
