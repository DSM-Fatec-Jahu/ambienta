<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($pageTitle ?? 'Entrar') ?> &mdash; <?= esc($institution['name'] ?? 'Ambienta') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/app.css') ?>">
</head>
<body class="h-full bg-slate-50">

  <div class="min-h-screen flex">

    <!-- ── Left branding panel (desktop only) ─────────────────────── -->
    <div class="hidden lg:flex lg:w-[480px] xl:w-[520px] flex-shrink-0
                bg-slate-900 flex-col justify-between p-10 relative overflow-hidden">

      <!-- Background decoration -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-primary/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-primary/10 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2
                    w-[600px] h-[600px] bg-primary/5 rounded-full blur-3xl"></div>
      </div>

      <div class="relative z-10">
        <!-- Logo -->
        <div class="flex items-center gap-3 mb-12">
          <?php if (!empty($institution['logo_path'])): ?>
            <img src="<?= base_url(esc($institution['logo_path'])) ?>" alt="Logo" class="h-10 w-auto brightness-0 invert">
          <?php else: ?>
            <div class="h-10 w-10 rounded-xl bg-primary flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
            </div>
          <?php endif; ?>
          <span class="text-white font-bold text-lg">
            <?= esc($institution['name'] ?? 'Ambienta') ?>
          </span>
        </div>

        <!-- Heading -->
        <h1 class="text-3xl xl:text-4xl font-bold text-white leading-tight text-balance mb-4">
          Gerencie ambientes com simplicidade
        </h1>
        <p class="text-slate-400 text-sm leading-relaxed mb-10">
          Reservas, equipamentos e aprovações em um só lugar — acessível para toda a sua equipe.
        </p>

        <!-- Feature list -->
        <ul class="space-y-4">
          <?php
          $features = [
            ['icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
             'text' => 'Agenda pública sem necessidade de login'],
            ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0',
             'text' => 'Fluxo de aprovação com controle por papel'],
            ['icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
             'text' => 'Notificações automáticas por e-mail'],
          ];
          foreach ($features as $f): ?>
          <li class="flex items-center gap-3">
            <div class="h-8 w-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $f['icon'] ?>"/>
              </svg>
            </div>
            <span class="text-sm text-slate-300"><?= esc($f['text']) ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Bottom link -->
      <div class="relative z-10">
        <a href="<?= base_url() ?>"
           class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-300 transition-colors">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Ver agenda pública
        </a>
      </div>
    </div>

    <!-- ── Right form panel ────────────────────────────────────────── -->
    <div class="flex-1 flex flex-col justify-center items-center px-6 py-10 sm:px-10">

      <!-- Mobile logo (shown only on small screens) -->
      <div class="lg:hidden flex items-center gap-2.5 mb-8">
        <div class="h-8 w-8 rounded-lg bg-primary flex items-center justify-center">
          <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
        </div>
        <span class="font-semibold text-slate-800"><?= esc($institution['name'] ?? 'Ambienta') ?></span>
      </div>

      <!-- Form card -->
      <div class="w-full max-w-sm">
        <?= $this->include('components/flash_messages') ?>
        <?= $this->renderSection('content') ?>
      </div>

      <!-- Mobile back link -->
      <p class="lg:hidden mt-8 text-xs text-slate-400">
        <a href="<?= base_url() ?>" class="hover:text-slate-600 transition-colors">
          &larr; Ver agenda pública
        </a>
      </p>
    </div>

  </div>

</body>
</html>
