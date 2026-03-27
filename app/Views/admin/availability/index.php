<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Disponibilidade Diária</h1>
    <p class="page-subtitle">Grade de ocupação por sala e horário</p>
  </div>
</div>

<!-- ── Filters ───────────────────────────────────────────────── -->
<form method="GET" action="<?= base_url('admin/disponibilidade') ?>"
      class="card card-body mb-5 flex flex-wrap items-end gap-4">

  <!-- Date -->
  <div>
    <label for="date" class="form-label">Data</label>
    <input type="date" id="date" name="date" value="<?= esc($date) ?>"
           class="form-input w-44">
  </div>

  <!-- Building filter -->
  <?php if (!empty($buildings)): ?>
  <div>
    <label for="building_id" class="form-label">Prédio</label>
    <select id="building_id" name="building_id" class="form-input w-48">
      <option value="">Todos os prédios</option>
      <?php foreach ($buildings as $b): ?>
        <option value="<?= $b['id'] ?>" <?= $buildingId == $b['id'] ? 'selected' : '' ?>>
          <?= esc($b['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>

  <!-- Equipment filter -->
  <?php if (!empty($equipmentList)): ?>
  <div x-data="{ open: false }" class="relative">
    <label class="form-label">Recursos</label>
    <button type="button" @click="open = !open"
            class="form-input w-52 text-left flex items-center justify-between">
      <span class="truncate text-sm">
        <?php
          $eqNames = array_filter(array_map(function($e) use ($equipmentIds) {
            return in_array($e['id'], $equipmentIds) ? esc($e['name']) : null;
          }, $equipmentList));
          echo !empty($eqNames) ? implode(', ', $eqNames) : 'Todos os recursos';
        ?>
      </span>
      <svg class="w-4 h-4 shrink-0 ml-1 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
      </svg>
    </button>
    <div x-show="open" @click.outside="open = false" x-cloak
         class="absolute z-30 mt-1 w-64 bg-white border border-slate-200 rounded-lg shadow-lg p-2 max-h-60 overflow-y-auto">
      <?php foreach ($equipmentList as $eq): ?>
      <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-slate-50 cursor-pointer text-sm">
        <input type="checkbox" name="equipment_ids[]" value="<?= $eq['id'] ?>"
               <?= in_array($eq['id'], $equipmentIds) ? 'checked' : '' ?>
               class="rounded text-primary">
        <span><?= esc($eq['name']) ?></span>
      </label>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Actions -->
  <div class="flex items-center gap-2">
    <button type="submit" class="btn-primary">Ver disponibilidade</button>
    <a href="?date=<?= date('Y-m-d') ?>" class="btn-secondary">Hoje</a>
    <a href="?date=<?= date('Y-m-d', strtotime($date . ' -1 day')) ?><?= $buildingId ? '&building_id='.$buildingId : '' ?><?= !empty($equipmentIds) ? '&'.http_build_query(['equipment_ids' => $equipmentIds]) : '' ?>"
       class="btn-ghost btn-sm p-2" title="Dia anterior">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
      </svg>
    </a>
    <a href="?date=<?= date('Y-m-d', strtotime($date . ' +1 day')) ?><?= $buildingId ? '&building_id='.$buildingId : '' ?><?= !empty($equipmentIds) ? '&'.http_build_query(['equipment_ids' => $equipmentIds]) : '' ?>"
       class="btn-ghost btn-sm p-2" title="Próximo dia">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
      </svg>
    </a>
  </div>
</form>

<!-- ── Summary ─────────────────────────────────────────────────── -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
  <div class="card card-body">
    <p class="text-2xs text-slate-400 uppercase tracking-wide font-semibold">Data</p>
    <p class="text-lg font-bold text-slate-800 mt-1">
      <?= date('d/m/Y', strtotime($date)) ?>
    </p>
    <p class="text-xs text-slate-400"><?= ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][(int)date('w', strtotime($date))] ?></p>
  </div>
  <div class="card card-body">
    <p class="text-2xs text-slate-400 uppercase tracking-wide font-semibold">Salas</p>
    <p class="text-lg font-bold text-slate-800 mt-1"><?= count($rooms) ?></p>
    <p class="text-xs text-slate-400">
      <?= ($buildingId || !empty($equipmentIds)) ? 'filtradas' : 'ativas' ?>
    </p>
  </div>
  <div class="card card-body">
    <p class="text-2xs text-slate-400 uppercase tracking-wide font-semibold">Reservas</p>
    <p class="text-lg font-bold text-slate-800 mt-1"><?= $totalBookings ?></p>
    <p class="text-xs text-slate-400">no dia</p>
  </div>
  <div class="card card-body">
    <p class="text-2xs text-slate-400 uppercase tracking-wide font-semibold">Funcionamento</p>
    <?php if ($isClosed): ?>
      <p class="text-lg font-bold text-red-500 mt-1">Fechado</p>
    <?php else: ?>
      <p class="text-lg font-bold text-slate-800 mt-1"><?= $dayOpen ?> – <?= $dayClose ?></p>
    <?php endif; ?>
  </div>
</div>

<!-- ── Legend ──────────────────────────────────────────────────── -->
<div class="flex items-center gap-4 mb-3 text-xs">
  <div class="flex items-center gap-1.5">
    <div class="h-3 w-5 rounded bg-primary/80"></div>
    <span class="text-slate-500">Aprovada</span>
  </div>
  <div class="flex items-center gap-1.5">
    <div class="h-3 w-5 rounded bg-amber-400/80"></div>
    <span class="text-slate-500">Pendente</span>
  </div>
  <div class="flex items-center gap-1.5">
    <div class="h-3 w-5 rounded bg-slate-100"></div>
    <span class="text-slate-500">Disponível</span>
  </div>
</div>

<?php if (empty($rooms)): ?>
<div class="card card-body text-center py-12">
  <p class="text-slate-400">Nenhuma sala encontrada para os filtros selecionados.</p>
</div>
<?php elseif (empty($slots)): ?>
<div class="card card-body text-center py-12">
  <p class="text-slate-400">Horários de funcionamento não configurados para este dia.</p>
</div>
<?php else: ?>

<!-- ── Grid ────────────────────────────────────────────────────── -->
<div class="card overflow-hidden" x-data="availGrid()">

  <div class="overflow-x-auto">
    <table class="w-full text-xs border-collapse" style="min-width: <?= (count($slots) * 80 + 180) ?>px">
      <thead>
        <tr class="bg-slate-50 border-b border-slate-200">
          <th class="sticky left-0 bg-slate-50 z-10 text-left px-3 py-2 font-semibold text-slate-600 w-44 border-r border-slate-200">
            Sala
          </th>
          <?php foreach ($slots as $slot): ?>
          <th class="px-1 py-2 font-medium text-slate-400 text-center w-20 border-r border-slate-100 last:border-r-0">
            <?= $slot ?>
          </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($rooms as $room):
          $roomBookings = $bookingsByRoom[$room['id']] ?? [];
          $totalSlots   = count($slots);
          $skipUntil    = -1;
        ?>
        <tr class="hover:bg-slate-50/50 transition-colors">
          <!-- Room name (sticky) -->
          <td class="sticky left-0 bg-white z-10 px-3 py-2 border-r border-slate-200 hover:bg-slate-50">
            <div class="font-semibold text-slate-800 leading-tight truncate max-w-[160px]"
                 title="<?= esc($room['name']) ?>">
              <?= esc($room['name']) ?>
            </div>
            <?php if (!empty($room['code'])): ?>
              <div class="text-slate-400 text-2xs"><?= esc($room['code']) ?></div>
            <?php endif; ?>
            <?php if (!empty($room['building_name'])): ?>
              <div class="text-slate-400 text-2xs truncate"><?= esc($room['building_name']) ?></div>
            <?php endif; ?>
          </td>

          <!-- Slots -->
          <?php foreach ($slots as $slotIdx => $slot):
            // Skip slots already covered by a previous booking's colspan
            if ($slotIdx <= $skipUntil) continue;

            $slotStart = $slot;
            $slotEnd   = sprintf('%02d:00', (int)$slot + 1);

            // Find booking overlapping this slot
            $hit = null;
            foreach ($roomBookings as $bk) {
              $bStart = substr($bk['start_time'], 0, 5);
              $bEnd   = substr($bk['end_time'],   0, 5);
              if ($bStart < $slotEnd && $bEnd > $slotStart) {
                $hit = $bk;
                break;
              }
            }

            if ($hit):
              // Calculate how many consecutive slots this booking spans
              $bStart = substr($hit['start_time'], 0, 5);
              $bEnd   = substr($hit['end_time'],   0, 5);
              $span   = 0;
              for ($j = $slotIdx; $j < $totalSlots; $j++) {
                $sStart = $slots[$j];
                $sEnd   = sprintf('%02d:00', (int)$slots[$j] + 1);
                if ($bStart < $sEnd && $bEnd > $sStart) {
                  $span++;
                } else {
                  break;
                }
              }
              $span      = max(1, $span);
              $skipUntil = $slotIdx + $span - 1;

              $bgClass   = $hit['status'] === 'approved'
                ? 'bg-primary/15 border border-primary/30'
                : 'bg-amber-50 border border-amber-200';
              $textClass  = $hit['status'] === 'approved' ? 'text-primary' : 'text-amber-700';
              $subClass   = $hit['status'] === 'approved' ? 'text-primary/70' : 'text-amber-600';
          ?>
          <td class="p-0.5 border-r border-slate-100 last:border-r-0"
              colspan="<?= $span ?>">
            <div class="<?= $bgClass ?> rounded flex flex-col justify-center px-2 py-1 min-h-[2.5rem]
                        cursor-pointer overflow-hidden"
                 @mouseenter="show($event, <?= json_encode([
                   'title'     => $hit['title'],
                   'user'      => $hit['user_name'] ?? '',
                   'start'     => substr($hit['start_time'], 0, 5),
                   'end'       => substr($hit['end_time'],   0, 5),
                   'status'    => $hit['status'],
                   'id'        => $hit['id'],
                   'attendees' => $hit['attendees_count'],
                 ]) ?>)"
                 @mouseleave="hide()">
              <div class="flex items-baseline justify-between gap-1 min-w-0">
                <span class="text-2xs font-semibold leading-tight truncate <?= $textClass ?>">
                  <?= esc($hit['title']) ?>
                </span>
                <span class="text-2xs font-medium leading-tight shrink-0 <?= $textClass ?> opacity-75 tabular-nums">
                  <?= substr($hit['start_time'], 0, 5) ?>–<?= substr($hit['end_time'], 0, 5) ?>
                </span>
              </div>
              <span class="text-2xs leading-tight truncate <?= $subClass ?> mt-0.5">
                <?= esc($hit['user_name'] ?? '') ?>
              </span>
            </div>
          </td>
          <?php else: ?>
          <td class="p-0.5 border-r border-slate-100 last:border-r-0">
            <div class="min-h-[2.5rem] rounded"></div>
          </td>
          <?php endif; ?>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Tooltip -->
  <div x-show="tooltip.visible" x-cloak
       :style="'top:' + tooltip.y + 'px; left:' + tooltip.x + 'px'"
       class="fixed z-50 bg-slate-900 text-white text-xs rounded-lg shadow-lg p-3 w-52
              pointer-events-none"
       x-transition:enter="transition ease-out duration-100"
       x-transition:enter-start="opacity-0 scale-95"
       x-transition:enter-end="opacity-100 scale-100">
    <p class="font-semibold truncate mb-1" x-text="tooltip.title"></p>
    <p class="text-slate-300" x-text="tooltip.start + ' – ' + tooltip.end"></p>
    <p class="text-slate-300" x-text="tooltip.user"></p>
    <p class="text-slate-300" x-text="tooltip.attendees + ' participante(s)'"></p>
    <span class="inline-block mt-1 text-2xs px-1.5 py-0.5 rounded font-semibold"
          :class="tooltip.status === 'approved' ? 'bg-emerald-500' : 'bg-amber-500'"
          x-text="tooltip.status === 'approved' ? 'Aprovada' : 'Pendente'">
    </span>
  </div>
</div>

<script>
function availGrid() {
  return {
    tooltip: { visible: false, title: '', user: '', start: '', end: '', status: '', attendees: 0, x: 0, y: 0 },
    show(event, data) {
      const rect = event.target.closest('[\\@mouseenter]')?.getBoundingClientRect() ?? event.target.getBoundingClientRect();
      this.tooltip = {
        visible:  true,
        title:    data.title,
        user:     data.user,
        start:    data.start,
        end:      data.end,
        status:   data.status,
        attendees:data.attendees,
        x: Math.min(rect.left + 8, window.innerWidth - 220),
        y: rect.bottom + 6,
      };
    },
    hide() { this.tooltip.visible = false; },
  };
}
</script>

<?php endif; ?>

<?= $this->endSection() ?>
