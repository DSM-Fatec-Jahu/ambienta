<?= $this->extend('layouts/public') ?>
<?= $this->section('content') ?>

<div class="mb-6">
  <h1 class="text-2xl font-bold text-gray-900">Recursos</h1>
  <p class="text-sm text-gray-500 mt-0.5">Consulte os recursos disponíveis por ambiente</p>
</div>

<?php if (empty($resources)): ?>
  <div class="card p-12 text-center">
    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
    </svg>
    <p class="text-gray-400 text-sm">Nenhum recurso cadastrado ainda.</p>
  </div>
<?php else: ?>
  <div class="card overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patrimônio</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Localização</th>
          <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <?php foreach ($resources as $resource): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900"><?= esc($resource['name']) ?></div>
              <?php if (!empty($resource['description'])): ?>
                <div class="text-xs text-gray-500"><?= esc($resource['description']) ?></div>
              <?php endif; ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
              <?= esc($resource['category'] ?? '—') ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
              <?= !empty($resource['code']) ? esc($resource['code']) : '—' ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <?php if (!empty($resource['room_name'])): ?>
                <span class="text-gray-800"><?= esc($resource['room_name']) ?></span>
                <?php if (!empty($resource['room_abbreviation'])): ?>
                  <span class="text-gray-400 text-xs ml-1">(<?= esc($resource['room_abbreviation']) ?>)</span>
                <?php endif; ?>
              <?php else: ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-sky-100 text-sky-700">
                  Estoque geral
                </span>
              <?php endif; ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-right">
              <?= (int) $resource['quantity_total'] ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?= $this->endSection() ?>
