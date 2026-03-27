<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<!-- Back link -->
<div class="mb-4">
  <a href="<?= base_url('ambientes') ?>" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 transition-colors">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
    </svg>
    Voltar para Ambientes
  </a>
</div>

<!-- Header -->
<div class="flex flex-wrap items-start gap-3 mb-6">
  <div class="flex-1 min-w-0">
    <div class="flex items-center gap-2 flex-wrap">
      <h1 class="text-2xl font-bold text-slate-900"><?= esc($room['name']) ?></h1>
      <?php if (!empty($room['code'])): ?>
        <span class="badge bg-primary-light text-primary border border-primary/20 text-sm">
          <?= esc($room['code']) ?>
        </span>
      <?php endif; ?>
    </div>
    <p class="text-sm text-slate-500 mt-0.5">
      <?= esc($building['name'] ?? '') ?>
    </p>
  </div>
  <a href="<?= base_url('reservas/nova') ?>"
     class="btn-primary btn-sm flex-shrink-0">
    Fazer Reserva
  </a>
</div>

<!-- Two-column layout -->
<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

  <!-- Left column: details -->
  <div class="lg:col-span-2 space-y-4">

    <!-- Room image -->
    <?php if (!empty($room['image_path'])): ?>
      <div class="card overflow-hidden">
        <img src="<?= base_url(esc($room['image_path'])) ?>" alt="<?= esc($room['name']) ?>"
             class="w-full h-48 object-cover">
      </div>
    <?php endif; ?>

    <!-- Details card -->
    <div class="card">
      <div class="card-body divide-y divide-slate-100">
        <h2 class="font-semibold text-slate-900 pb-3">Informações</h2>

        <?php if (!empty($room['capacity'])): ?>
        <div class="flex items-center justify-between py-3">
          <span class="text-sm text-slate-500 flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Capacidade
          </span>
          <span class="text-sm font-medium text-slate-900"><?= esc($room['capacity']) ?> pessoa(s)</span>
        </div>
        <?php endif; ?>

        <?php if (!empty($room['floor'])): ?>
        <div class="flex items-center justify-between py-3">
          <span class="text-sm text-slate-500 flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 10l9-7 9 7v11a1 1 0 01-1 1H4a1 1 0 01-1-1V10z"/>
            </svg>
            Andar
          </span>
          <span class="text-sm font-medium text-slate-900"><?= esc($room['floor']) ?></span>
        </div>
        <?php endif; ?>

        <div class="flex items-center justify-between py-3">
          <span class="text-sm text-slate-500 flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            Prédio
          </span>
          <span class="text-sm font-medium text-slate-900"><?= esc($building['name'] ?? '—') ?></span>
        </div>
      </div>
    </div>

    <!-- Description -->
    <?php if (!empty($room['description'])): ?>
    <div class="card">
      <div class="card-body">
        <h2 class="font-semibold text-slate-900 mb-2">Descrição</h2>
        <p class="text-sm text-slate-600 leading-relaxed"><?= nl2br(esc($room['description'])) ?></p>
      </div>
    </div>
    <?php endif; ?>

    <!-- Equipment list -->
    <?php if (!empty($equipment)): ?>
    <div class="card">
      <div class="card-body">
        <h2 class="font-semibold text-slate-900 mb-3">Recursos disponíveis</h2>
        <ul class="space-y-2">
          <?php foreach ($equipment as $eq): ?>
          <li class="flex items-start gap-2 text-sm">
            <svg class="w-4 h-4 text-primary mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <div>
              <span class="font-medium text-slate-900"><?= esc($eq['name']) ?></span>
              <?php if (!empty($eq['code'])): ?>
                <span class="text-slate-400 ml-1 text-xs">(<?= esc($eq['code']) ?>)</span>
              <?php endif; ?>
              <?php if (!empty($eq['description'])): ?>
                <p class="text-xs text-slate-500 mt-0.5"><?= esc($eq['description']) ?></p>
              <?php endif; ?>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Right column: mini calendar -->
  <div class="lg:col-span-3">
    <div class="card sticky top-20">
      <div class="card-body">
        <h2 class="font-semibold text-slate-900 mb-3">Disponibilidade da semana</h2>
        <div id="mini-calendar"></div>
      </div>
    </div>
  </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const calendarEl = document.getElementById('mini-calendar');
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'timeGridWeek',
    locale: 'pt-br',
    headerToolbar: { left: 'prev,next', center: 'title', right: '' },
    height: 420,
    slotMinTime: '07:00:00',
    slotMaxTime: '22:00:00',
    allDaySlot: false,
    nowIndicator: true,
    events: {
      url: '<?= base_url('api/agenda/events') ?>',
      extraParams: { room_id: <?= (int) $room['id'] ?> },
    },
    eventClick: function () {},
    eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
  });
  calendar.render();
});
</script>
<?= $this->endSection() ?>
