<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Disponibilidade Diária</h1>
    <p class="page-subtitle">Grade de ocupação por sala e horário</p>
  </div>
</div>

<!-- ── Date picker ──────────────────────────────────────────────── -->
<form method="GET" action="<?= base_url('admin/disponibilidade') ?>"
      class="card card-body mb-5 flex flex-wrap items-end gap-4">
  <div>
    <label for="date" class="form-label">Data</label>
    <input type="date" id="date" name="date" value="<?= esc($date) ?>"
           class="form-input w-44">
  </div>
  <div class="flex items-center gap-2">
    <button type="submit" class="btn-primary">Ver disponibilidade</button>
    <a href="?date=<?= date('Y-m-d') ?>" class="btn-secondary">Hoje</a>
    <a href="?date=<?= date('Y-m-d', strtotime($date . ' -1 day')) ?>" class="btn-ghost btn-sm p-2" title="Dia anterior">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
      </svg>
    </a>
    <a href="?date=<?= date('Y-m-d', strtotime($date . ' +1 day')) ?>" class="btn-ghost btn-sm p-2" title="Próximo dia">
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
    <p class="text-xs text-slate-400">ativas</p>
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
  <p class="text-slate-400">Nenhuma sala ativa cadastrada.</p>
</div>
<?php elseif (empty($slots)): ?>
<div class="card card-body text-center py-12">
  <p class="text-slate-400">Horários de funcionamento não configurados para este dia.</p>
</div>
<?php else: ?>

<!-- ── Grid ────────────────────────────────────────────────────── -->
<div class="card overflow-hidden" x-data="availGrid()">

  <div class="overflow-x-auto">
    <table class="w-full text-xs border-collapse" style="min-width: <?= (count($slots) * 52 + 180) ?>px">
      <thead>
        <tr class="bg-slate-50 border-b border-slate-200">
          <th class="sticky left-0 bg-slate-50 z-10 text-left px-3 py-2 font-semibold text-slate-600 w-44 border-r border-slate-200">
            Sala
          </th>
          <?php foreach ($slots as $slot): ?>
          <th class="px-1 py-2 font-medium text-slate-400 text-center w-[52px] border-r border-slate-100 last:border-r-0">
            <?= $slot ?>
          </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($rooms as $room):
          $roomBookings = $bookingsByRoom[$room['id']] ?? [];
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
            // Check if any booking covers this slot
            $slotStart = $slot;          // e.g. "08:00"
            $slotEnd   = sprintf('%02d:00', (int)$slot + 1); // e.g. "09:00"

            $hit = null;
            foreach ($roomBookings as $bk) {
              $bStart = substr($bk['start_time'], 0, 5);
              $bEnd   = substr($bk['end_time'],   0, 5);
              // Booking overlaps this slot if it starts before slot ends AND ends after slot starts
              if ($bStart < $slotEnd && $bEnd > $slotStart) {
                $hit = $bk;
                break;
              }
            }

            $bgClass = match($hit['status'] ?? '') {
              'approved' => 'bg-primary/15 border border-primary/30',
              'pending'  => 'bg-amber-50 border border-amber-200',
              default    => '',
            };
          ?>
          <td class="p-0.5 border-r border-slate-100 last:border-r-0">
            <?php if ($hit): ?>
              <div class="<?= $bgClass ?> rounded h-8 flex items-center justify-center
                          cursor-pointer group relative overflow-hidden"
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
                <span class="text-2xs font-semibold truncate px-1
                             <?= $hit['status'] === 'approved' ? 'text-primary' : 'text-amber-700' ?>">
                  <?= mb_strimwidth(esc($hit['title']), 0, 8, '…') ?>
                </span>
              </div>
            <?php else: ?>
              <div class="h-8 rounded"></div>
            <?php endif; ?>
          </td>
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
