<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Horários de Funcionamento</h1>
    <p class="page-subtitle">Defina os dias e horários em que reservas podem ser solicitadas.</p>
  </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success mb-6"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<form method="POST" action="<?= base_url('admin/horarios') ?>">
  <?= csrf_field() ?>

  <div class="card overflow-hidden">
    <!-- Header -->
    <div class="hidden md:grid grid-cols-12 gap-4 px-6 py-3 bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wide">
      <div class="col-span-3">Dia</div>
      <div class="col-span-2">Aberto</div>
      <div class="col-span-3">Abertura</div>
      <div class="col-span-3">Fechamento</div>
      <div class="col-span-1 text-center" title="Requer confirmação extra">Conf.</div>
    </div>

    <?php foreach ($rows as $row):
      $d = (int) $row['day_of_week'];
    ?>
    <div class="grid grid-cols-12 gap-4 items-center px-6 py-4 border-b border-slate-100 last:border-0"
         x-data="{ open: <?= $row['is_open'] ? 'true' : 'false' ?> }">

      <!-- Day name -->
      <div class="col-span-3 font-medium text-slate-800 text-sm">
        <?= esc($dayNames[$d]) ?>
      </div>

      <!-- Is open toggle -->
      <div class="col-span-2 flex items-center gap-2">
        <input type="hidden"   name="is_open_<?= $d ?>" value="0">
        <input type="checkbox" name="is_open_<?= $d ?>" value="1" id="open_<?= $d ?>"
               <?= $row['is_open'] ? 'checked' : '' ?>
               x-model="open"
               class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
        <label for="open_<?= $d ?>" class="text-sm text-slate-700 cursor-pointer">Aberto</label>
      </div>

      <!-- Open time -->
      <div class="col-span-3">
        <input type="time" name="open_time_<?= $d ?>"
               value="<?= esc(substr($row['open_time'] ?? '08:00', 0, 5)) ?>"
               x-bind:disabled="!open"
               x-bind:class="open ? '' : 'opacity-30 cursor-not-allowed'"
               class="form-input text-sm">
      </div>

      <!-- Close time -->
      <div class="col-span-3">
        <input type="time" name="close_time_<?= $d ?>"
               value="<?= esc(substr($row['close_time'] ?? '18:00', 0, 5)) ?>"
               x-bind:disabled="!open"
               x-bind:class="open ? '' : 'opacity-30 cursor-not-allowed'"
               class="form-input text-sm">
      </div>

      <!-- Requires extra confirmation -->
      <div class="col-span-1 flex justify-center">
        <input type="hidden"   name="requires_extra_<?= $d ?>" value="0">
        <input type="checkbox" name="requires_extra_<?= $d ?>" value="1"
               <?= $row['requires_extra_confirmation'] ? 'checked' : '' ?>
               x-bind:disabled="!open"
               title="Exige confirmação extra neste dia"
               class="w-4 h-4 text-purple-600 rounded border-slate-300 focus:ring-purple-500">
      </div>

    </div>
    <?php endforeach; ?>
  </div>

  <!-- Legend -->
  <p class="text-xs text-slate-500 mt-3">
    <strong>Conf.</strong> = Requer confirmação extra (reservas neste dia ficam pendentes mesmo com aprovação automática).
  </p>

  <div class="mt-6 flex justify-end">
    <button type="submit" class="btn-primary">Salvar horários</button>
  </div>
</form>

<?= $this->endSection() ?>
