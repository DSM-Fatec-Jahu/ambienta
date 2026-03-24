<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Usuários</h1>
    <p class="page-subtitle">Gerencie perfis e acesso dos membros da instituição</p>
  </div>
</div>

<!-- Search -->
<form method="GET" action="<?= base_url('admin/usuarios') ?>" class="mb-4">
  <div class="input-group max-w-sm">
    <svg class="input-icon-left w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
    </svg>
    <input type="text" name="q" value="<?= esc($search ?? '') ?>"
           class="form-input-icon-left" placeholder="Buscar por nome ou e-mail...">
  </div>
</form>

<div class="card overflow-hidden">

  <?php if (empty($items)): ?>
    <div class="empty-state">
      <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
      </svg>
      <p class="empty-state-title">Nenhum usuário encontrado</p>
      <?php if ($search): ?>
        <p class="empty-state-description">Tente uma busca diferente.</p>
        <a href="<?= base_url('admin/usuarios') ?>" class="btn-secondary mt-4">Limpar busca</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="table-base">
        <thead>
          <tr>
            <th>Usuário</th>
            <th>E-mail</th>
            <th>Perfil</th>
            <th>SSO</th>
            <th>Status</th>
            <th class="text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $u): ?>
          <tr>
            <td>
              <div class="flex items-center gap-2.5">
                <?php if (!empty($u['avatar_url'])): ?>
                  <img src="<?= esc($u['avatar_url']) ?>" alt="" class="h-7 w-7 rounded-full object-cover flex-shrink-0">
                <?php else: ?>
                  <div class="h-7 w-7 rounded-full bg-primary/10 flex items-center justify-center
                              text-xs font-bold text-primary flex-shrink-0">
                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                  </div>
                <?php endif; ?>
                <span class="font-medium text-slate-900"><?= esc($u['name']) ?></span>
              </div>
            </td>
            <td class="text-slate-600"><?= esc($u['email']) ?></td>
            <td>
              <!-- Inline role update form -->
              <form method="POST" action="<?= base_url('admin/usuarios/' . $u['id'] . '/role') ?>">
                <?= csrf_field() ?>
                <select name="role" onchange="this.form.submit()"
                        class="text-xs border border-slate-200 rounded-lg px-2 py-1 bg-white
                               text-slate-700 focus:outline-none focus:ring-1 focus:ring-primary">
                  <?php foreach ($rolesList as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $u['role'] === $key ? 'selected' : '' ?>>
                      <?= esc($label) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td>
              <?php if ($u['google_id']): ?>
                <span class="badge-primary">Google</span>
              <?php else: ?>
                <span class="text-slate-300 text-xs">Local</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($u['is_active']): ?>
                <span class="badge-approved badge-dot">Ativo</span>
              <?php else: ?>
                <span class="badge-cancelled">Inativo</span>
              <?php endif; ?>
            </td>
            <td class="text-right">
              <form method="POST" action="<?= base_url('admin/usuarios/' . $u['id'] . '/toggle-active') ?>"
                    onsubmit="return confirm('<?= $u['is_active'] ? 'Desativar' : 'Ativar' ?> o usuário <?= esc(addslashes($u['name'])) ?>?')">
                <?= csrf_field() ?>
                <button type="submit"
                        class="<?= $u['is_active'] ? 'btn-ghost btn-sm text-red-500 hover:bg-red-50' : 'btn-ghost btn-sm text-success hover:bg-emerald-50' ?>">
                  <?= $u['is_active'] ? 'Desativar' : 'Ativar' ?>
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer text-xs text-slate-400">
      <?= count($items) ?> usuário<?= count($items) !== 1 ? 's' : '' ?>
      <?= $search ? 'encontrado' . (count($items) !== 1 ? 's' : '') . ' para "' . esc($search) . '"' : 'cadastrado' . (count($items) !== 1 ? 's' : '') ?>
    </div>
  <?php endif; ?>

</div>

<?= $this->endSection() ?>
