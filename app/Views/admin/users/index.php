<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header" x-data="{ inviteOpen: false }">
  <div>
    <h1 class="page-title">Usuários</h1>
    <p class="page-subtitle">Gerencie perfis e acesso dos membros da instituição</p>
  </div>
  <button @click="inviteOpen = true" class="btn-primary">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
    </svg>
    Convidar usuário
  </button>

  <!-- Invite modal -->
  <div x-show="inviteOpen" class="modal-overlay" x-cloak @keydown.escape.window="inviteOpen = false">
    <div class="modal-panel max-w-md" @click.stop>
      <div class="modal-header">
        <h3 class="text-sm font-semibold text-slate-900">Convidar novo usuário</h3>
        <button @click="inviteOpen = false" class="btn-ghost btn-sm p-1" aria-label="Fechar">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <form method="POST" action="<?= base_url('admin/usuarios/convidar') ?>">
        <?= csrf_field() ?>
        <div class="modal-body space-y-4">
          <p class="text-xs text-slate-500">
            Um e-mail com link de ativação será enviado. O convite expira em 72 horas.
          </p>
          <div>
            <label for="inv_email" class="form-label form-label-required">E-mail do convidado</label>
            <input type="email" id="inv_email" name="email"
                   class="form-input" required placeholder="usuario@exemplo.com" autocomplete="off">
          </div>
          <div>
            <label for="inv_role" class="form-label form-label-required">Perfil</label>
            <select id="inv_role" name="role" class="form-input">
              <?php foreach ($rolesList as $key => $label): ?>
                <option value="<?= $key ?>"><?= esc($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" @click="inviteOpen = false" class="btn-secondary">Cancelar</button>
          <button type="submit" class="btn-primary">Enviar convite</button>
        </div>
      </form>
    </div>
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


<!-- Pending invites -->
<?php if (!empty($pendingInvites)): ?>
<div class="mt-6">
  <h2 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
    <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
    </svg>
    Convites pendentes
    <span class="ml-1 text-2xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full font-semibold">
      <?= count($pendingInvites) ?>
    </span>
  </h2>
  <div class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="table-base">
        <thead>
          <tr>
            <th>E-mail convidado</th>
            <th>Perfil</th>
            <th>Convidado por</th>
            <th>Expira em</th>
            <th class="text-right">Ação</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pendingInvites as $inv):
            $expiresIn = ceil((strtotime($inv['expires_at']) - time()) / 3600);
          ?>
          <tr>
            <td class="font-medium text-slate-800"><?= esc($inv['email']) ?></td>
            <td>
              <span class="badge-primary text-xs">
                <?= esc($rolesList[$inv['role']] ?? $inv['role']) ?>
              </span>
            </td>
            <td class="text-slate-600"><?= esc($inv['inviter_name'] ?? '—') ?></td>
            <td class="text-xs text-amber-600">
              <?php if ($expiresIn > 0): ?>
                <?= $expiresIn ?>h restante<?= $expiresIn !== 1 ? 's' : '' ?>
              <?php else: ?>
                Expirando
              <?php endif; ?>
            </td>
            <td class="text-right">
              <form method="POST"
                    action="<?= base_url('admin/usuarios/convites/' . $inv['id'] . '/revogar') ?>"
                    onsubmit="return confirm('Revogar convite para <?= esc(addslashes($inv['email'])) ?>?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn-ghost btn-sm text-red-500 hover:bg-red-50">
                  Revogar
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
