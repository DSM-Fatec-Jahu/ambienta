<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header mb-6">
  <div>
    <h1 class="page-title">Agenda</h1>
    <p class="page-subtitle">Suas reservas e todas as aprovadas na instituição</p>
  </div>
  <a href="<?= base_url('reservas/nova') ?>" class="btn-primary">Nova Reserva</a>
</div>

<!-- Legend -->
<div class="flex flex-wrap gap-3 mb-4 text-xs">
  <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-full bg-amber-500"></span> Pendente (sua)</span>
  <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-full" style="background:#1A8C5B"></span> Aprovada (sua)</span>
  <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-full bg-red-600"></span> Recusada (sua)</span>
  <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-full bg-slate-500"></span> Cancelada / Ausente</span>
  <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-sm border-2" style="border-color:#1A8C5B"></span> Aprovada (outros)</span>
</div>

<!-- Filters -->
<div class="card mb-4">
  <div class="card-body">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div>
        <label class="form-label">Prédio</label>
        <select id="filter-building" class="form-input">
          <option value="">Todos os prédios</option>
          <?php foreach ($buildings as $b): ?>
            <option value="<?= $b['id'] ?>"><?= esc($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label">Ambiente</label>
        <select id="filter-room" class="form-input">
          <option value="">Todos os ambientes</option>
          <?php foreach ($rooms as $r): ?>
            <option value="<?= $r['id'] ?>" data-building="<?= $r['building_id'] ?>">
              <?= esc($r['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="flex items-end">
        <button id="btn-filter" class="btn-primary w-full">Filtrar</button>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body p-4">
    <div id="auth-calendar"></div>
  </div>
</div>

<!-- Event detail popover -->
<div id="event-popover"
     class="hidden fixed z-50 bg-white border border-slate-200 rounded-lg shadow-xl p-4 w-72 text-sm">
  <button id="popover-close" class="absolute top-2 right-3 text-slate-400 hover:text-slate-600 text-lg">&times;</button>
  <div id="popover-content"></div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<script>
(function () {
  const API_URL   = '<?= base_url('api/reservas/agenda-events') ?>';
  const popover   = document.getElementById('event-popover');
  const popContent= document.getElementById('popover-content');
  const btnFilter = document.getElementById('btn-filter');
  const selBldg   = document.getElementById('filter-building');
  const selRoom   = document.getElementById('filter-room');
  const allRoomOpts = Array.from(selRoom.options);

  // Building filter cascades to rooms
  selBldg.addEventListener('change', function () {
    const bId = this.value;
    Array.from(selRoom.options).forEach(o => o.remove());
    allRoomOpts.forEach(o => {
      if (!bId || !o.dataset.building || o.dataset.building === bId) {
        selRoom.add(o.cloneNode(true));
      }
    });
  });

  function buildUrl(info) {
    const params = new URLSearchParams({
      start:       info.startStr,
      end:         info.endStr,
      building_id: selBldg.value,
      room_id:     selRoom.value,
    });
    return API_URL + '?' + params.toString();
  }

  const calendar = new FullCalendar.Calendar(document.getElementById('auth-calendar'), {
    locale:        'pt-br',
    initialView:   'dayGridMonth',
    height:        'auto',
    headerToolbar: {
      left:   'prev,next today',
      center: 'title',
      right:  'dayGridMonth,timeGridWeek,listMonth',
    },
    buttonText: {
      today:     'Hoje',
      month:     'Mês',
      week:      'Semana',
      list:      'Lista',
    },
    events: function (info, successCb, failureCb) {
      fetch(buildUrl(info))
        .then(r => r.json())
        .then(successCb)
        .catch(failureCb);
    },
    eventClick: function (info) {
      const p  = info.event.extendedProps;
      const dt = info.event.startStr.substring(0, 10);
      const st = info.event.startStr.substring(11, 16);
      const et = info.event.endStr.substring(11, 16);

      const statusLabels = {
        pending:   'Aguardando',
        approved:  'Aprovada',
        rejected:  'Recusada',
        cancelled: 'Cancelada',
        absent:    'Ausente',
      };

      popContent.innerHTML = `
        <p class="font-semibold text-slate-900 mb-2 pr-4">${info.event.title}</p>
        <table class="w-full text-xs text-slate-600 space-y-1">
          <tr><td class="font-medium pr-2 py-0.5">Ambiente</td><td>${p.room_name}</td></tr>
          ${p.building_name ? `<tr><td class="font-medium pr-2 py-0.5">Prédio</td><td>${p.building_name}</td></tr>` : ''}
          <tr><td class="font-medium pr-2 py-0.5">Data</td><td>${dt}</td></tr>
          <tr><td class="font-medium pr-2 py-0.5">Horário</td><td>${st} – ${et}</td></tr>
          ${p.status ? `<tr><td class="font-medium pr-2 py-0.5">Status</td><td>${statusLabels[p.status] ?? p.status}</td></tr>` : ''}
          ${!p.is_own ? '<tr><td colspan="2" class="pt-1 text-slate-400 italic">Reserva de outro usuário</td></tr>' : ''}
        </table>`;

      // Position
      const rect = info.el.getBoundingClientRect();
      popover.style.top  = (window.scrollY + rect.bottom + 6) + 'px';
      popover.style.left = Math.min(rect.left, window.innerWidth - 300) + 'px';
      popover.classList.remove('hidden');
      info.jsEvent.stopPropagation();
    },
  });

  calendar.render();

  btnFilter.addEventListener('click', () => calendar.refetchEvents());

  document.getElementById('popover-close').addEventListener('click', () => popover.classList.add('hidden'));
  document.addEventListener('click', e => {
    if (!popover.contains(e.target)) popover.classList.add('hidden');
  });
})();
</script>

<?= $this->endSection() ?>
