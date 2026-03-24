<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<div class="mb-6">
  <h1 class="text-2xl font-bold text-gray-900">Prédios</h1>
  <p class="text-sm text-gray-500 mt-0.5">Conheça os prédios e blocos da instituição</p>
</div>

<?php if (empty($buildings)): ?>
  <div class="card p-12 text-center">
    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
    </svg>
    <p class="text-gray-400 text-sm">Nenhum prédio cadastrado ainda.</p>
    <p class="text-gray-400 text-xs mt-1">Os prédios serão exibidos aqui após o cadastro.</p>
  </div>
<?php else: ?>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($buildings as $b): ?>
      <div class="card hover:shadow-md transition-shadow">
        <div class="card-body">
          <div class="flex items-start justify-between gap-2">
            <div>
              <h2 class="font-semibold text-gray-900"><?= esc($b['name']) ?></h2>
              <span class="badge bg-primary-light text-primary border border-primary/20 mt-1">
                <?= esc($b['abbreviation']) ?>
              </span>
            </div>
            <span class="badge <?= $b['is_active'] ? 'badge-approved' : 'badge-cancelled' ?> flex-shrink-0">
              <?= $b['is_active'] ? 'Ativo' : 'Inativo' ?>
            </span>
          </div>
          <?php if (!empty($b['address'])): ?>
            <p class="text-sm text-gray-500 mt-2 flex items-start gap-1.5">
              <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              <?= esc($b['address']) ?>
            </p>
          <?php endif; ?>
          <a href="<?= base_url('ambientes?building_id=' . $b['id']) ?>"
             class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-primary hover:text-primary-dark">
            Ver ambientes
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?= $this->endSection() ?>
