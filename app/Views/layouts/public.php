<!DOCTYPE html>
<html lang="pt-BR" class="h-full scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($pageTitle ?? 'Ambienta') ?> &mdash; <?= esc($institution['name'] ?? 'Gestão de Ambientes') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/app.css') ?>">
  <?= $this->renderSection('head') ?>
</head>
<body class="h-full flex flex-col bg-[#EEF2F7]">

  <!-- ── Header ──────────────────────────────────────────────────────── -->
  <header class="sticky top-0 z-30 bg-white/90 backdrop-blur-sm border-b border-slate-100">
    <div class="max-w-screen-xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-4">

      <!-- Logo -->
      <a href="<?= base_url() ?>" class="flex items-center gap-2.5 min-w-0 flex-shrink-0">
        <?php if (!empty($institution['logo_path'])): ?>
          <img src="<?= base_url(esc($institution['logo_path'])) ?>" alt="Logo" class="h-7 w-auto">
        <?php else: ?>
          <div class="h-8 w-8 rounded-lg bg-primary flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
          </div>
        <?php endif; ?>
        <span class="font-semibold text-slate-800 text-sm truncate">
          <?= esc($institution['name'] ?? 'Ambienta') ?>
        </span>
      </a>

      <!-- Nav (desktop) -->
      <nav class="hidden md:flex items-center gap-0.5" aria-label="Navegação pública">
        <?php
        $navLinks = [
          'agenda'       => 'Agenda',
          'predios'      => 'Prédios',
          'ambientes'    => 'Ambientes',
          'equipamentos' => 'Equipamentos',
        ];
        $seg = explode('/', trim(service('uri')->getPath(), '/'))[0] ?? '';
        foreach ($navLinks as $path => $label):
          $active = ($seg === $path);
        ?>
          <a href="<?= base_url($path) ?>"
             class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors
                    <?= $active
                        ? 'text-primary bg-primary-light'
                        : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' ?>">
            <?= esc($label) ?>
          </a>
        <?php endforeach; ?>
      </nav>

      <!-- Right -->
      <div class="flex items-center gap-2">
        <a href="<?= base_url('login') ?>"
           class="btn-primary btn-sm hidden sm:inline-flex">
          Entrar
        </a>
        <!-- Mobile menu button -->
        <button id="pub-menu-btn" type="button"
                class="md:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors"
                aria-label="Menu" aria-expanded="false">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Mobile menu -->
    <div id="pub-menu" class="hidden md:hidden border-t border-slate-100 py-2 px-4 space-y-0.5">
      <?php foreach ($navLinks as $path => $label): ?>
        <a href="<?= base_url($path) ?>"
           class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50">
          <?= esc($label) ?>
        </a>
      <?php endforeach; ?>
      <div class="pt-2 pb-1 border-t border-slate-100 mt-1">
        <a href="<?= base_url('login') ?>" class="btn-primary w-full justify-center">Entrar</a>
      </div>
    </div>
  </header>

  <!-- ── Content ──────────────────────────────────────────────────────── -->
  <main class="flex-1 w-full max-w-screen-xl mx-auto px-4 sm:px-6 py-8">
    <?= $this->include('components/flash_messages') ?>
    <?= $this->renderSection('content') ?>
  </main>

  <!-- ── Footer ───────────────────────────────────────────────────────── -->
  <footer class="border-t border-slate-100 bg-white">
    <div class="max-w-screen-xl mx-auto px-4 sm:px-6 py-5 flex flex-col sm:flex-row items-center justify-between gap-2">
      <p class="text-xs text-slate-400">
        &copy; <?= date('Y') ?> <?= esc($institution['name'] ?? 'Ambienta') ?>
      </p>
      <p class="text-xs text-slate-300">Sistema de Gestão de Ambientes e Reservas</p>
    </div>
  </footer>

  <script>
    document.getElementById('pub-menu-btn')?.addEventListener('click', function () {
      const menu = document.getElementById('pub-menu');
      const open = menu.classList.toggle('hidden');
      this.setAttribute('aria-expanded', String(!open));
    });
  </script>
  <?= $this->renderSection('scripts') ?>
</body>
</html>
