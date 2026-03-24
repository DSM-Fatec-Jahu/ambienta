<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<div class="mb-6">
  <h1 class="text-2xl font-bold text-gray-900">Equipamentos</h1>
  <p class="text-sm text-gray-500 mt-0.5">Consulte os equipamentos disponíveis por ambiente</p>
</div>

<?php if (empty($equipment)): ?>
  <div class="card p-12 text-center">
    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
        d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
    </svg>
    <p class="text-gray-400 text-sm">Nenhum equipamento cadastrado ainda.</p>
  </div>
<?php endif; ?>

<?= $this->endSection() ?>
