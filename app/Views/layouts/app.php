<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($pageTitle ?? 'Dashboard') ?> &mdash; <?= esc($institution['name'] ?? 'Ambienta') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/app.css') ?>">
  <?= $this->renderSection('head') ?>
</head>
<body class="h-full bg-[#EEF2F7]" x-data="{ sidebarOpen: false }">

  <!-- ── Sidebar overlay (mobile) ──────────────────────────────────── -->
  <div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak
       class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-20 lg:hidden"
       x-transition:enter="transition-opacity duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition-opacity duration-200"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">
  </div>

  <!-- ── Sidebar ───────────────────────────────────────────────────── -->
  <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
         class="fixed top-0 left-0 h-full w-[248px] bg-slate-900 flex flex-col z-30
                transition-transform duration-200 ease-in-out
                lg:translate-x-0">

    <!-- Logo -->
    <div class="h-14 flex items-center gap-3 px-5 border-b border-slate-800 flex-shrink-0">
      <?php if (!empty($institution['logo_path'])): ?>
        <img src="<?= base_url(esc($institution['logo_path'])) ?>" alt="Logo"
             class="h-7 w-auto brightness-0 invert flex-shrink-0">
      <?php else: ?>
        <div class="h-7 w-7 rounded-lg bg-primary flex items-center justify-center flex-shrink-0">
          <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
        </div>
      <?php endif; ?>
      <span class="font-semibold text-white text-sm truncate">
        <?= esc($institution['name'] ?? 'Ambienta') ?>
      </span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto sidebar-scroll py-4 px-3 space-y-0.5">
      <?php
      $userRole    = $currentUser['role'] ?? '';
      $currentPath = ltrim(service('uri')->getPath(), '/');

      $isActive = fn(string $path): bool => str_starts_with($currentPath, $path);
      $canAccess = fn(array $roles): bool =>
          in_array('*', $roles, true) || in_array($userRole, $roles, true);

      // Navigation structure with sections
      $nav = [
        '' => [
          ['path' => 'dashboard', 'label' => 'Dashboard', 'roles' => ['*'],
           'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
          ['path' => 'agenda', 'label' => 'Agenda', 'roles' => ['*'],
           'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ],
        'Reservas' => [
          ['path' => 'reservas', 'label' => 'Minhas Reservas', 'roles' => ['*'],
           'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
          ['path' => 'reservas/nova', 'label' => 'Nova Reserva', 'roles' => ['*'],
           'icon' => 'M12 4v16m8-8H4'],
          ['path' => 'reservas/pendentes', 'label' => 'Pendentes', 'roles' => ['role_technician','role_coordinator','role_vice_director','role_director','role_admin'],
           'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0',
           'badge' => (string)($pendingBadge ?? 0)],
          ['path' => 'reservas/agenda', 'label' => 'Agenda', 'roles' => ['*'],
           'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ],
        'Gestão' => [
          ['path' => 'admin/ambientes', 'label' => 'Ambientes', 'roles' => ['role_technician','role_coordinator','role_vice_director','role_director','role_admin'],
           'icon' => 'M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
          ['path' => 'admin/predios', 'label' => 'Prédios', 'roles' => ['role_technician','role_coordinator','role_vice_director','role_director','role_admin'],
           'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5'],
          ['path' => 'admin/equipamentos', 'label' => 'Equipamentos', 'roles' => ['role_technician','role_coordinator','role_vice_director','role_director','role_admin'],
           'icon' => 'M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18'],
          ['path' => 'admin/usuarios', 'label' => 'Usuários', 'roles' => ['role_coordinator','role_vice_director','role_director','role_admin'],
           'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        ],
        'Administração' => [
          ['path' => 'admin/horarios', 'label' => 'Horários', 'roles' => ['role_director','role_admin'],
           'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0'],
          ['path' => 'admin/feriados', 'label' => 'Feriados', 'roles' => ['role_director','role_admin'],
           'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
          ['path' => 'admin/relatorios', 'label' => 'Relatórios', 'roles' => ['role_director','role_admin'],
           'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
          ['path' => 'admin/auditoria', 'label' => 'Auditoria', 'roles' => ['role_director','role_admin'],
           'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
          ['path' => 'admin/configuracoes', 'label' => 'Configurações', 'roles' => ['role_director','role_admin'],
           'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
        ],
      ];

      foreach ($nav as $section => $items):
        $visibleItems = array_filter($items, fn($i) => $canAccess($i['roles']));
        if (empty($visibleItems)) continue;
      ?>
        <?php if ($section): ?>
          <p class="nav-section-label mt-4 first:mt-0"><?= esc($section) ?></p>
        <?php endif; ?>

        <?php foreach ($visibleItems as $item):
          $active = $isActive($item['path']);
        ?>
          <a href="<?= base_url($item['path']) ?>"
             class="<?= $active ? 'nav-item-active' : 'nav-item-default' ?> group">
            <svg class="w-4 h-4 flex-shrink-0 transition-colors
                        <?= $active ? 'text-white' : 'text-slate-500 group-hover:text-slate-300' ?>"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>"/>
            </svg>
            <span class="flex-1 truncate"><?= esc($item['label']) ?></span>
            <?php if (!empty($item['badge']) && $item['badge'] !== '0'): ?>
              <span class="ml-auto text-2xs bg-primary/80 text-white px-1.5 py-0.5 rounded-full font-semibold">
                <?= esc($item['badge']) ?>
              </span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>

    <!-- User info at bottom -->
    <div class="flex-shrink-0 border-t border-slate-800 p-3">
      <a href="<?= base_url('logout') ?>"
         class="flex items-center gap-3 px-3 py-2.5 rounded-lg group
                transition-colors hover:bg-slate-800 cursor-pointer"
         onclick="return confirm('Deseja sair do sistema?')">
        <?php if (!empty($currentUser['avatar_url'])): ?>
          <img src="<?= esc($currentUser['avatar_url']) ?>" alt="Avatar"
               class="h-7 w-7 rounded-full object-cover ring-1 ring-slate-700 flex-shrink-0">
        <?php else: ?>
          <div class="h-7 w-7 rounded-full bg-primary/30 flex items-center justify-center
                      text-xs font-bold text-primary-300 flex-shrink-0">
            <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
          </div>
        <?php endif; ?>
        <div class="flex-1 min-w-0">
          <p class="text-xs font-medium text-slate-300 truncate"><?= esc($currentUser['name'] ?? '') ?></p>
          <p class="text-2xs text-slate-500 truncate"><?= esc($rolesLabels[$userRole] ?? $userRole) ?></p>
        </div>
        <svg class="w-3.5 h-3.5 text-slate-600 group-hover:text-slate-400 transition-colors flex-shrink-0"
             fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
      </a>
    </div>
  </aside>

  <!-- ── Main area ─────────────────────────────────────────────────── -->
  <div class="lg:pl-[248px] flex flex-col min-h-screen">

    <!-- Topbar -->
    <header class="sticky top-0 z-10 h-14 bg-white border-b border-slate-100 flex items-center px-4 sm:px-6 gap-4">

      <!-- Hamburger (mobile) -->
      <button @click="sidebarOpen = true"
              class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600
                     transition-colors flex-shrink-0"
              aria-label="Abrir menu">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>

      <!-- Page title (dynamic via section or static) -->
      <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-slate-800 truncate">
          <?= esc($pageTitle ?? 'Dashboard') ?>
        </p>
      </div>

      <!-- Right actions -->
      <div class="flex items-center gap-1">

        <!-- Notifications -->
        <button class="relative p-2 rounded-lg text-slate-400 hover:bg-slate-100
                       hover:text-slate-600 transition-colors"
                aria-label="Notificações">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
          </svg>
          <span id="notif-badge" class="hidden absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
        </button>

        <!-- Avatar dropdown -->
        <div class="relative" x-data="{ open: false }">
          <button @click="open = !open" @keydown.escape="open = false"
                  class="flex items-center gap-2 pl-1 pr-2 py-1.5 rounded-lg
                         hover:bg-slate-100 transition-colors">
            <?php if (!empty($currentUser['avatar_url'])): ?>
              <img src="<?= esc($currentUser['avatar_url']) ?>" alt="Avatar"
                   class="h-7 w-7 rounded-full object-cover">
            <?php else: ?>
              <div class="h-7 w-7 rounded-full bg-primary flex items-center justify-center
                          text-xs font-bold text-white">
                <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
              </div>
            <?php endif; ?>
            <span class="text-sm font-medium text-slate-700 hidden sm:block max-w-[120px] truncate">
              <?= esc($currentUser['name'] ?? '') ?>
            </span>
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>

          <!-- Dropdown menu -->
          <div x-show="open" @click.away="open = false" x-cloak
               x-transition:enter="transition ease-out duration-100"
               x-transition:enter-start="opacity-0 scale-95"
               x-transition:enter-end="opacity-100 scale-100"
               x-transition:leave="transition ease-in duration-75"
               x-transition:leave-start="opacity-100 scale-100"
               x-transition:leave-end="opacity-0 scale-95"
               class="absolute right-0 mt-1.5 w-52 bg-white rounded-xl
                      shadow-modal border border-slate-100 py-1 z-50 origin-top-right">
            <div class="px-4 py-2.5 border-b border-slate-100">
              <p class="text-sm font-medium text-slate-900 truncate"><?= esc($currentUser['name'] ?? '') ?></p>
              <p class="text-xs text-slate-400 truncate mt-0.5"><?= esc($currentUser['email'] ?? '') ?></p>
            </div>
            <div class="py-1">
              <a href="<?= base_url('logout') ?>"
                 class="flex items-center gap-2.5 px-4 py-2 text-sm text-red-600
                        hover:bg-red-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Sair
              </a>
            </div>
          </div>
        </div>

      </div>
    </header>

    <!-- Content -->
    <main class="flex-1 p-4 sm:p-6 min-w-0">
      <?= $this->include('components/flash_messages') ?>
      <?= $this->renderSection('content') ?>
    </main>

  </div><!-- /.lg:pl-[248px] -->

  <!-- Alpine.js -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <?= $this->renderSection('scripts') ?>
</body>
</html>
