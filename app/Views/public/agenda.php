<?= $this->extend('layouts/public') ?>
<?= $this->section('head') ?>
<?php /* FullCalendar v6: CSS is injected automatically by the JS bundle */ ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Agenda</h1>
    <p class="text-sm text-slate-500 mt-0.5">Reservas aprovadas de todos os ambientes</p>
  </div>
</div>

<!-- Filters -->
<div class="card mb-4">
  <div class="card-body">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <div>
        <label for="filter-building" class="form-label">Prédio</label>
        <select id="filter-building" class="form-input">
          <option value="">Todos os prédios</option>
        </select>
      </div>
      <div>
        <label for="filter-room" class="form-label">Ambiente</label>
        <select id="filter-room" class="form-input">
          <option value="">Todos os ambientes</option>
        </select>
      </div>
      <div>
        <label for="filter-equipment-name" class="form-label">Recurso (nome)</label>
        <input type="text" id="filter-equipment-name" class="form-input" placeholder="Ex: projetor">
      </div>
      <div>
        <label for="filter-patrimony" class="form-label">Código de patrimônio</label>
        <input type="text" id="filter-patrimony" class="form-input" placeholder="Ex: PRJ-001">
      </div>
    </div>
    <div class="mt-3 flex gap-2">
      <button id="btn-filter" class="btn-primary btn-sm">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
        </svg>
        Filtrar
      </button>
      <button id="btn-clear" class="btn-secondary btn-sm">Limpar</button>
    </div>
  </div>
</div>

<!-- Calendar -->
<div class="card">
  <div class="card-body p-4">
    <div id="calendar"></div>
  </div>
</div>

<!-- Event detail modal -->
<div id="event-modal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
    <div class="flex items-center justify-between p-5 border-b border-slate-100">
      <h3 class="font-semibold text-slate-900" id="modal-title">Detalhes da Reserva</h3>
      <button onclick="closeModal()"
              class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600"
              aria-label="Fechar">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
    <div class="p-5 space-y-3 text-sm" id="modal-body"></div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
const EVENTS_URL  = '<?= base_url('api/agenda/events') ?>';
const FILTERS_URL = '<?= base_url('api/agenda/filters') ?>';

// ── Populate filter dropdowns ──────────────────────────────────────────────
let allRooms = [];

async function loadFilters() {
  try {
    const res  = await fetch(FILTERS_URL);
    const data = await res.json();

    const bSel = document.getElementById('filter-building');
    data.buildings.forEach(b => {
      const opt = new Option(b.name + (b.code ? ` (${b.code})` : ''), b.id);
      bSel.appendChild(opt);
    });

    allRooms = data.rooms;
    populateRooms('');
  } catch { /* silent */ }
}

function populateRooms(buildingId) {
  const rSel = document.getElementById('filter-room');
  rSel.innerHTML = '<option value="">Todos os ambientes</option>';
  allRooms
    .filter(r => !buildingId || String(r.building_id) === String(buildingId))
    .forEach(r => {
      const label = r.name + (r.code ? ` (${r.code})` : '') +
                    (r.building_name ? ` — ${r.building_name}` : '');
      rSel.appendChild(new Option(label, r.id));
    });
}

document.getElementById('filter-building').addEventListener('change', function () {
  populateRooms(this.value);
});

// ── FullCalendar ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
  loadFilters();

  const ptBr = {
    code: 'pt-br',
    week: { dow: 0, doy: 6 },
    buttonText: {
      prev: 'Anterior', next: 'Próximo', today: 'Hoje',
      month: 'Mês', week: 'Semana', day: 'Dia', list: 'Lista',
    },
    weekText: 'Sm',
    allDayText: 'dia todo',
    moreLinkText: n => `+ mais ${n}`,
    noEventsText: 'Nenhum evento',
  };

  const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
    locale: ptBr,
    initialView: 'dayGridMonth',
    headerToolbar: {
      left:   'prev,next today',
      center: 'title',
      right:  'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
    },
    height: 'auto',
    nowIndicator: true,
    events: {
      url:    EVENTS_URL,
      method: 'GET',
      extraParams: getFilters,
      failure() { /* silent */ },
    },
    eventClick(info) { showModal(info.event); },
    eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
    loading(isLoading) {
      document.getElementById('calendar').style.opacity = isLoading ? '0.6' : '1';
    },
  });

  calendar.render();
  window._calendar = calendar;

  document.getElementById('btn-filter').addEventListener('click', () => calendar.refetchEvents());
  document.getElementById('btn-clear').addEventListener('click', () => {
    ['filter-building','filter-room','filter-equipment-name','filter-patrimony']
      .forEach(id => { document.getElementById(id).value = ''; });
    populateRooms('');
    calendar.refetchEvents();
  });
});

function getFilters() {
  return {
    building_id:    document.getElementById('filter-building').value,
    room_id:        document.getElementById('filter-room').value,
    equipment_name: document.getElementById('filter-equipment-name').value,
    patrimony_code: document.getElementById('filter-patrimony').value,
  };
}

// ── Event modal ────────────────────────────────────────────────────────────
function showModal(event) {
  const p = event.extendedProps;
  const room = p.room_name + (p.building_name ? ` — ${p.building_name}` : '');

  document.getElementById('modal-title').textContent = p.purpose ?? event.title;
  document.getElementById('modal-body').innerHTML = `
    <div class="flex gap-2">
      <span class="font-medium text-slate-500 w-28 flex-shrink-0">Ambiente</span>
      <span class="text-slate-900">${esc(room)}</span>
    </div>
    <div class="flex gap-2">
      <span class="font-medium text-slate-500 w-28 flex-shrink-0">Horário</span>
      <span class="text-slate-900">${formatDatetime(event.start)} – ${formatTime(event.end)}</span>
    </div>
    ${p.attendees_count ? `
    <div class="flex gap-2">
      <span class="font-medium text-slate-500 w-28 flex-shrink-0">Participantes</span>
      <span class="text-slate-900">${p.attendees_count} pessoa(s)</span>
    </div>` : ''}
    ${p.description ? `
    <div class="flex gap-2">
      <span class="font-medium text-slate-500 w-28 flex-shrink-0">Descrição</span>
      <span class="text-slate-600 italic">${esc(p.description)}</span>
    </div>` : ''}
  `;
  document.getElementById('event-modal').classList.remove('hidden');
}

function closeModal() {
  document.getElementById('event-modal').classList.add('hidden');
}

function esc(str) {
  return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function formatDatetime(d) {
  return d ? d.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) : '—';
}

function formatTime(d) {
  return d ? d.toLocaleTimeString('pt-BR', { timeStyle: 'short' }) : '—';
}

document.getElementById('event-modal').addEventListener('click', function (e) {
  if (e.target === this) closeModal();
});
</script>
<?= $this->endSection() ?>
