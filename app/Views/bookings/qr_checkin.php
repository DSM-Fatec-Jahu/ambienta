<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check-in QR &mdash; Ambienta</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/app.css') ?>">
</head>
<body class="h-full bg-[#EEF2F7] flex items-center justify-center p-6">

  <div class="w-full max-w-sm">

    <!-- Logo / brand -->
    <div class="flex justify-center mb-6">
      <div class="h-12 w-12 rounded-2xl bg-primary flex items-center justify-center shadow-lg">
        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
      </div>
    </div>

    <div class="card shadow-xl">
      <div class="card-body text-center py-8 px-6">

        <?php if ($success): ?>
          <!-- Success -->
          <div class="flex justify-center mb-4">
            <div class="h-16 w-16 rounded-full bg-emerald-100 flex items-center justify-center">
              <svg class="w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0"/>
              </svg>
            </div>
          </div>
          <h1 class="text-xl font-bold text-slate-900 mb-1">Check-in realizado!</h1>
          <p class="text-sm text-slate-500 mb-5">Presença registrada com sucesso.</p>

          <?php if (!empty($booking) && !empty($room)): ?>
          <div class="bg-slate-50 rounded-xl p-4 text-left text-sm space-y-2 mb-5">
            <div class="flex justify-between">
              <span class="text-slate-500">Reserva</span>
              <span class="font-semibold text-slate-800">#<?= $booking['id'] ?></span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Ambiente</span>
              <span class="font-semibold text-slate-800"><?= esc($room['name'] ?? '—') ?></span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Horário</span>
              <span class="font-semibold text-slate-800">
                <?= substr($booking['start_time'], 0, 5) ?>–<?= substr($booking['end_time'], 0, 5) ?>
              </span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Check-in</span>
              <span class="font-semibold text-emerald-600"><?= date('H:i', strtotime(date('Y-m-d H:i:s'))) ?></span>
            </div>
          </div>
          <?php endif; ?>

        <?php else: ?>
          <!-- Failure -->
          <div class="flex justify-center mb-4">
            <div class="h-16 w-16 rounded-full bg-red-100 flex items-center justify-center">
              <svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0"/>
              </svg>
            </div>
          </div>
          <h1 class="text-xl font-bold text-slate-900 mb-1">Check-in não realizado</h1>
          <p class="text-sm text-slate-500 mb-5"><?= esc($message) ?></p>

          <?php if (!empty($booking)): ?>
          <div class="bg-slate-50 rounded-xl p-4 text-left text-sm space-y-2 mb-5">
            <div class="flex justify-between">
              <span class="text-slate-500">Reserva</span>
              <span class="font-semibold text-slate-800">#<?= $booking['id'] ?></span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Data</span>
              <span class="font-semibold text-slate-800"><?= date('d/m/Y', strtotime($booking['date'])) ?></span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Horário</span>
              <span class="font-semibold text-slate-800">
                <?= substr($booking['start_time'], 0, 5) ?>–<?= substr($booking['end_time'], 0, 5) ?>
              </span>
            </div>
          </div>
          <?php endif; ?>

        <?php endif; ?>

        <a href="<?= base_url('login') ?>" class="btn-primary w-full justify-center">
          Acessar o sistema
        </a>

      </div>
    </div>

    <p class="text-center text-xs text-slate-400 mt-4">Ambienta &mdash; Sistema de Reservas</p>
  </div>

</body>
</html>
