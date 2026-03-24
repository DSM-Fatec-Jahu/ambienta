<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Bloqueios de Ambiente</h1>
    <p class="page-subtitle">Bloqueie ambientes para manutenção, eventos ou outros impedimentos</p>
  </div>
  <button onclick="document.getElementById('modal-new').classList.remove('hidden')" class="btn-primary">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    Novo Bloqueio
  </button>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert-success mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert-error mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<!-- Blackouts list -->
<div class="card">
  <div class="card-header">
    <h2 class="text-sm font-semibold text-slate-900">Bloqueios cadastrados</h2>
    <span class="text-xs text-slate-400"><?= count($items) ?> registro(s)</span>
  </div>

  <?php if (empty($items)): ?>
    <div class="card-body text-center py-10 text-slate-400 text-sm">
      Nenhum bloqueio cadastrado.
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead>
          <tr>
            <th>Título</th>
            <th>Ambiente</th>
            <th>Início</th>
            <th>Término</th>
            <th>Motivo</th>
            <th>Criado por</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <?php
              $isPast = $item['ends_at'] < date('Y-m-d H:i:s');
              $isGlobal = empty($item['room_id']);
            ?>
            <tr class="<?= $isPast ? 'opacity-50' : '' ?>">
              <td>
                <span class="font-medium text-slate-900"><?= esc($item['title']) ?></span>
                <?php if ($isPast): ?>
                  <span class="ml-1 text-2xs text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">expirado</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($isGlobal): ?>
                  <span class="badge-pending text-xs px-2 py-0.5">Todos os ambientes</span>
                <?php else: ?>
                  <span class="text-sm text-slate-700"><?= esc($item['room_name'] ?? '—') ?></span>
                  <?php if (!empty($item['room_code'])): ?>
                    <span class="text-xs text-slate-400 ml-1"><?= esc($item['room_code']) ?></span>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
              <td class="text-sm text-slate-600 tabular-nums">
                <?= date('d/m/Y H:i', strtotime($item['starts_at'])) ?>
              </td>
              <td class="text-sm text-slate-600 tabular-nums">
                <?= date('d/m/Y H:i', strtotime($item['ends_at'])) ?>
              </td>
              <td class="text-sm text-slate-500 max-w-[200px] truncate">
                <?= esc($item['reason'] ?? '—') ?>
              </td>
              <td class="text-sm text-slate-500">
                <?= esc($item['created_by_name'] ?? '—') ?>
              </td>
              <td class="text-right">
                <form method="POST"
                      action="<?= base_url('admin/bloqueios/' . $item['id'] . '/delete') ?>"
                      onsubmit="return confirm('Remover este bloqueio?')">
                  <?= csrf_field() ?>
                  <button type="submit"
                          class="text-xs text-red-600 hover:text-red-800 font-medium transition-colors">
                    Remover
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

<!-- ── Modal: Novo Bloqueio ──────────────────────────────────────────── -->
<div id="modal-new"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
     x-data>
  <div class="bg-white rounded-xl shadow-xl w-full max-w-lg">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
      <h3 class="font-semibold text-slate-900">Novo Bloqueio</h3>
      <button onclick="document.getElementById('modal-new').classList.add('hidden')"
              class="text-slate-400 hover:text-slate-600 transition-colors">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <form method="POST" action="<?= base_url('admin/bloqueios') ?>">
      <?= csrf_field() ?>
      <div class="p-6 space-y-4">

        <div>
          <label for="b_title" class="form-label form-label-required">Título</label>
          <input type="text" id="b_title" name="title" value="<?= old('title') ?>"
                 class="form-input" maxlength="200" placeholder="Ex: Manutenção elétrica"
                 required>
        </div>

        <div>
          <label for="b_room" class="form-label">Ambiente</label>
          <select id="b_room" name="room_id" class="form-input">
            <option value="">— Todos os ambientes —</option>
            <?php foreach ($rooms as $room): ?>
              <option value="<?= $room['id'] ?>" <?= old('room_id') == $room['id'] ? 'selected' : '' ?>>
                <?= esc($room['name']) ?>
                <?= !empty($room['code']) ? '(' . esc($room['code']) . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
          <p class="form-hint">Deixe em branco para bloquear todos os ambientes.</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="b_starts" class="form-label form-label-required">Início</label>
            <input type="datetime-local" id="b_starts" name="starts_at"
                   value="<?= old('starts_at') ?>"
                   class="form-input" required>
          </div>
          <div>
            <label for="b_ends" class="form-label form-label-required">Término</label>
            <input type="datetime-local" id="b_ends" name="ends_at"
                   value="<?= old('ends_at') ?>"
                   class="form-input" required>
          </div>
        </div>

        <div>
          <label for="b_reason" class="form-label">Motivo / Observação</label>
          <textarea id="b_reason" name="reason" rows="2"
                    class="form-input" maxlength="500"
                    placeholder="Descrição opcional do motivo do bloqueio"><?= old('reason') ?></textarea>
        </div>

      </div>

      <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50 rounded-b-xl">
        <button type="button"
                onclick="document.getElementById('modal-new').classList.add('hidden')"
                class="btn-ghost">
          Cancelar
        </button>
        <button type="submit" class="btn-primary">Criar Bloqueio</button>
      </div>
    </form>
  </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
<script>
  document.getElementById('modal-new').classList.remove('hidden');
</script>
<?php endif; ?>

<?= $this->endSection() ?>
