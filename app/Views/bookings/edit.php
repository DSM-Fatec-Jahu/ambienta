<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div class="flex items-center gap-3">
    <a href="<?= base_url('reservas/' . $booking['id']) ?>" class="btn-ghost btn-sm p-1.5" aria-label="Voltar">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
      </svg>
    </a>
    <div>
      <h1 class="page-title">Editar Reserva</h1>
      <p class="page-subtitle">Reserva #<?= $booking['id'] ?> — apenas campos em aberto podem ser alterados</p>
    </div>
  </div>
</div>

<?php if (session()->has('error')): ?>
  <div class="alert-error mb-4"><?= esc(session('error')) ?></div>
<?php endif; ?>

<form method="POST" action="<?= base_url('reservas/' . $booking['id'] . '/editar') ?>"
      class="max-w-2xl space-y-4">
  <?= csrf_field() ?>

  <!-- Título -->
  <div class="card">
    <div class="card-header">
      <h2 class="text-sm font-semibold text-slate-900">Informações da reserva</h2>
    </div>
    <div class="card-body space-y-4">

      <div>
        <label for="title" class="form-label">Título <span class="text-red-500">*</span></label>
        <input type="text" id="title" name="title"
               value="<?= esc(old('title', $booking['title'])) ?>"
               class="form-input" maxlength="300" required>
      </div>

      <div>
        <label for="description" class="form-label">Descrição <span class="text-slate-400">(opcional)</span></label>
        <textarea id="description" name="description" rows="3"
                  class="form-input resize-none"><?= esc(old('description', $booking['description'] ?? '')) ?></textarea>
      </div>

      <div>
        <label for="attendees_count" class="form-label">Número de participantes <span class="text-red-500">*</span></label>
        <input type="number" id="attendees_count" name="attendees_count" min="1"
               value="<?= esc(old('attendees_count', $booking['attendees_count'])) ?>"
               class="form-input w-32" required>
      </div>

    </div>
  </div>

  <?php
    $isStaff = ($currentUser['role'] ?? '') !== 'role_requester';
  ?>

  <?php if ($isStaff): ?>
  <!-- Staff: room / date / time -->
  <div class="card">
    <div class="card-header">
      <h2 class="text-sm font-semibold text-slate-900">Ambiente e horário</h2>
      <span class="badge-pending text-xs">Apenas gestores</span>
    </div>
    <div class="card-body space-y-4">

      <div>
        <label for="room_id" class="form-label">Ambiente <span class="text-red-500">*</span></label>
        <select id="room_id" name="room_id" class="form-input" required>
          <?php foreach ($rooms as $r): ?>
            <option value="<?= $r['id'] ?>"
              <?= old('room_id', $booking['room_id']) == $r['id'] ? 'selected' : '' ?>>
              <?= esc($r['name']) ?><?= !empty($r['code']) ? ' (' . esc($r['code']) . ')' : '' ?>
              — <?= esc($r['building_name'] ?? '') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label for="date" class="form-label">Data <span class="text-red-500">*</span></label>
          <input type="date" id="date" name="date"
                 value="<?= esc(old('date', $booking['date'])) ?>"
                 min="<?= date('Y-m-d') ?>"
                 class="form-input" required>
        </div>
        <div>
          <label for="start_time" class="form-label">Início <span class="text-red-500">*</span></label>
          <input type="time" id="start_time" name="start_time"
                 value="<?= esc(old('start_time', substr($booking['start_time'], 0, 5))) ?>"
                 class="form-input" required>
        </div>
        <div>
          <label for="end_time" class="form-label">Término <span class="text-red-500">*</span></label>
          <input type="time" id="end_time" name="end_time"
                 value="<?= esc(old('end_time', substr($booking['end_time'], 0, 5))) ?>"
                 class="form-input" required>
        </div>
      </div>

    </div>
  </div>
  <?php else: ?>
  <!-- Read-only summary for non-staff -->
  <div class="card">
    <div class="card-body">
      <p class="text-xs text-slate-500 mb-3">Os dados de ambiente e horário não podem ser alterados. Para mudar, entre em contato com a equipe responsável.</p>
      <dl class="grid grid-cols-2 gap-3 text-xs">
        <div>
          <dt class="font-semibold text-slate-400 uppercase tracking-wide">Ambiente</dt>
          <dd class="mt-0.5 text-slate-700"><?= esc($room['name'] ?? '—') ?></dd>
        </div>
        <div>
          <dt class="font-semibold text-slate-400 uppercase tracking-wide">Data</dt>
          <dd class="mt-0.5 text-slate-700"><?= date('d/m/Y', strtotime($booking['date'])) ?></dd>
        </div>
        <div>
          <dt class="font-semibold text-slate-400 uppercase tracking-wide">Horário</dt>
          <dd class="mt-0.5 text-slate-700">
            <?= substr($booking['start_time'], 0, 5) ?> – <?= substr($booking['end_time'], 0, 5) ?>
          </dd>
        </div>
      </dl>
    </div>
  </div>
  <?php endif; ?>

  <!-- Bloco 1: Recursos fixos do ambiente (somente leitura — RN-R02/RN-R13) -->
  <?php if (!empty($groupedRoomResources)): ?>
  <div class="card">
    <div class="card-header">
      <h2 class="text-sm font-semibold text-slate-900">Recursos do ambiente</h2>
    </div>
    <div class="card-body">
      <p class="form-hint mb-3">Recursos fixos deste ambiente — disponíveis automaticamente durante a reserva.</p>
      <div class="flex flex-wrap gap-2">
        <?php if ($isRequester): ?>
          <?php foreach ($groupedRoomResources as $res): ?>
            <span class="badge-secondary text-xs px-2.5 py-1">
              <?= esc($res['name']) ?>
              <?php if ((int)$res['total_quantity'] > 1): ?>
                <span class="text-slate-400">(<?= (int)$res['total_quantity'] ?>)</span>
              <?php endif; ?>
            </span>
          <?php endforeach; ?>
        <?php else: ?>
          <?php foreach ($groupedRoomResources as $eq): ?>
            <span class="badge-secondary text-xs px-2.5 py-1">
              <?= esc($eq['name']) ?>
              <?php if (!empty($eq['code'])): ?>
                <span class="text-slate-400 font-mono">#<?= esc($eq['code']) ?></span>
              <?php endif; ?>
              <span class="text-slate-400">(<?= (int)$eq['allocated_quantity'] ?> un.)</span>
            </span>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Bloco 2: Recursos adicionais já alocados — ajustar quantidades (RN-R14) -->
  <?php if ($isRequester && !empty($existingResources)): ?>
  <?php
    // Group existing booking_resources by name+category for requester (no id/code)
    $grouped = [];
    foreach ($existingResources as $er) {
        $key = ($er['resource_name'] ?? '') . '||' . ($er['resource_category'] ?? '');
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'name'     => $er['resource_name']     ?? '',
                'category' => $er['resource_category'] ?? null,
                'quantity' => 0,
                'status'   => $er['status'],
            ];
        }
        $grouped[$key]['quantity'] += (int)$er['quantity'];
    }
  ?>
  <div class="card">
    <div class="card-header">
      <h2 class="text-sm font-semibold text-slate-900">Recursos adicionais solicitados</h2>
    </div>
    <div class="card-body space-y-2">
      <p class="form-hint">Recursos do estoque geral já vinculados a esta reserva.</p>
      <div class="divide-y divide-slate-100">
        <?php foreach (array_values($grouped) as $idx => $res): ?>
        <div class="flex items-center justify-between gap-3 py-2.5">
          <div>
            <span class="text-sm font-medium text-slate-800"><?= esc($res['name']) ?></span>
            <?php if ($res['category']): ?>
              <span class="text-xs text-slate-400 ml-1">(<?= esc($res['category']) ?>)</span>
            <?php endif; ?>
          </div>
          <input type="number"
                 name="resource_requests[<?= $idx ?>][quantity]"
                 min="0"
                 value="<?= (int)$res['quantity'] ?>"
                 class="w-16 text-center border border-slate-300 rounded-lg text-sm p-1 focus:ring-2 focus:ring-primary/40 focus:border-primary">
          <input type="hidden" name="resource_requests[<?= $idx ?>][name]"     value="<?= esc($res['name']) ?>">
          <input type="hidden" name="resource_requests[<?= $idx ?>][category]" value="<?= esc($res['category'] ?? '') ?>">
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="flex gap-3">
    <button type="submit" class="btn-primary">Salvar alterações</button>
    <a href="<?= base_url('reservas/' . $booking['id']) ?>" class="btn-secondary">Cancelar</a>
  </div>

</form>

<!-- AUDITADO: sem vazamento de patrimônio para Solicitante em 2026-03-29 -->

<?= $this->endSection() ?>
