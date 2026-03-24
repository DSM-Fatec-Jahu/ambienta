<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Notificações</h1>
    <p class="page-subtitle">Histórico das suas notificações do sistema</p>
  </div>
</div>

<?php if (empty($items)): ?>
  <div class="card">
    <div class="card-body py-16 flex flex-col items-center text-center gap-3">
      <svg class="w-12 h-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
      </svg>
      <p class="text-sm font-medium text-slate-500">Nenhuma notificação ainda</p>
      <p class="text-xs text-slate-400">As notificações do sistema aparecerão aqui.</p>
    </div>
  </div>

<?php else: ?>
  <div class="card divide-y divide-slate-100">
    <?php
    $typeIcons = [
        'booking.created'       => ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'color' => 'text-blue-500 bg-blue-50'],
        'booking.approved'      => ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0',                                                                                      'color' => 'text-green-600 bg-green-50'],
        'booking.rejected'      => ['icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0',                                                             'color' => 'text-red-500 bg-red-50'],
        'booking.cancelled'     => ['icon' => 'M6 18L18 6M6 6l12 12',                                                                                                             'color' => 'text-slate-500 bg-slate-100'],
        'booking.reminder'      => ['icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0',                                                                                       'color' => 'text-amber-500 bg-amber-50'],
        'booking.review_request'=> ['icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'color' => 'text-yellow-500 bg-yellow-50'],
        'booking.auto_cancel'   => ['icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'color' => 'text-orange-500 bg-orange-50'],
        'waitlist.available'    => ['icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', 'color' => 'text-primary bg-primary/10'],
    ];
    ?>

    <?php foreach ($items as $item):
      $meta  = $typeIcons[$item['type']] ?? $typeIcons['booking.created'];
      $isNew = empty($item['read_at']);
    ?>
      <div class="flex items-start gap-3 px-4 py-3.5 <?= $isNew ? 'bg-primary/[0.03]' : '' ?>">

        <!-- Icon -->
        <div class="flex-shrink-0 mt-0.5 w-8 h-8 rounded-full <?= $meta['color'] ?> flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $meta['icon'] ?>"/>
          </svg>
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-2">
            <p class="text-sm font-medium text-slate-800 <?= $isNew ? 'font-semibold' : '' ?>">
              <?= esc($item['title']) ?>
              <?php if ($isNew): ?>
                <span class="ml-1.5 inline-block h-2 w-2 rounded-full bg-primary align-middle"></span>
              <?php endif; ?>
            </p>
            <span class="text-2xs text-slate-400 whitespace-nowrap flex-shrink-0">
              <?= $item['created_at'] ? date('d/m H:i', strtotime($item['created_at'])) : '' ?>
            </span>
          </div>
          <?php if (!empty($item['body'])): ?>
            <p class="text-xs text-slate-500 mt-0.5"><?= esc($item['body']) ?></p>
          <?php endif; ?>
          <?php if (!empty($item['url'])): ?>
            <a href="<?= esc($item['url']) ?>" class="text-xs text-primary hover:underline mt-1 inline-block">
              Ver detalhes &rarr;
            </a>
          <?php endif; ?>
        </div>

      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?= $this->endSection() ?>
