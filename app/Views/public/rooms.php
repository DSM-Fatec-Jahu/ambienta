<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<div class="mb-6">
  <h1 class="text-2xl font-bold text-gray-900">Ambientes</h1>
  <p class="text-sm text-gray-500 mt-0.5">Consulte salas, laboratórios e outros espaços disponíveis</p>
</div>

<?php if (empty($rooms)): ?>
  <div class="card p-12 text-center">
    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
        d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
    </svg>
    <p class="text-gray-400 text-sm">Nenhum ambiente cadastrado ainda.</p>
  </div>
<?php else: ?>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($rooms as $r): ?>
      <a href="<?= base_url('ambientes/' . $r['id']) ?>" class="card hover:shadow-md transition-shadow block">
        <?php if (!empty($r['image_path'])): ?>
          <img src="<?= base_url(esc($r['image_path'])) ?>" alt="<?= esc($r['name']) ?>"
               class="w-full h-36 object-cover rounded-t-xl">
        <?php endif; ?>
        <div class="card-body">
          <div class="flex items-start justify-between gap-2">
            <h2 class="font-semibold text-gray-900 text-sm"><?= esc($r['name']) ?></h2>
            <?php if (!empty($r['code'])): ?>
            <span class="badge bg-primary-light text-primary border border-primary/20 flex-shrink-0">
              <?= esc($r['code']) ?>
            </span>
            <?php endif; ?>
          </div>
          <p class="text-xs text-gray-500 mt-1"><?= esc($r['building_name'] ?? '') ?></p>
          <div class="flex flex-wrap gap-1.5 mt-2 text-xs text-gray-500">
            <span class="flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              <?= esc($r['capacity']) ?> pessoas
            </span>
          </div>
          <div class="mt-3">
            <span class="text-xs text-primary font-medium">Ver detalhes &rarr;</span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?= $this->endSection() ?>
