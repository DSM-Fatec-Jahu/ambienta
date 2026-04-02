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

<div class="card overflow-hidden" x-data="usersPage()">

  <!-- Toolbar -->
  <div class="border-b border-slate-100 flex items-stretch">

    <!-- Esquerda: busca + filtros -->
    <div class="flex items-center gap-3 flex-1 overflow-x-auto p-4 min-w-0">

      <label class="flex items-center gap-0 form-input p-0 pl-2 text-sm cursor-text"
             style="flex:1 1 280px; min-width:200px; overflow:hidden;">
        <span class="flex items-center justify-center pl-3 pr-2 text-slate-400 flex-shrink-0 self-stretch"
              style="margin-left:15px">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
          </svg>
        </span>
        <input type="text" x-model="filters.q" @input.debounce.350ms="goTo(1)"
               placeholder="Buscar por nome ou e-mail..."
               class="flex-1 h-full text-sm bg-transparent border-0 outline-none shadow-none py-0 px-0 pr-3"
               style="box-shadow:none; border:none; outline:none">
      </label>

      <!-- Filtro por perfil -->
      <select class="form-input text-sm flex-shrink-0" style="min-width:170px; width:auto"
              x-model="filters.role" @change="goTo(1)">
        <option value="">Todos os perfis</option>
        <?php foreach ($rolesList as $key => $label): ?>
          <option value="<?= $key ?>"><?= esc($label) ?></option>
        <?php endforeach; ?>
      </select>

      <!-- Filtro de status -->
      <select class="form-input text-sm flex-shrink-0" style="min-width:140px; width:auto"
              x-model="filters.status" @change="goTo(1)">
        <option value="0">Todos os status</option>
        <option value="1">Ativo</option>
        <option value="2">Inativo</option>
      </select>

    </div>

    <!-- Separador vertical -->
    <div class="w-px bg-slate-100 self-stretch flex-shrink-0"></div>

    <!-- Direita: exports -->
    <div class="flex items-center gap-2 p-4 flex-shrink-0">

      <!-- Dropdown Excel -->
      <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
        <button @click="open = !open" class="btn-secondary text-xs flex items-center gap-1.5">
          <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 17v-6h6v6M9 11V7l3-3 3 3v4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/>
          </svg>
          Excel
          <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div x-show="open" x-cloak @click.outside="open = false"
             class="absolute right-0 mt-1 w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-50 py-1">
          <a :href="exportUrl('exportar-xlsx')" @click="open = false"
             class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
            </svg>
            Exportar filtrado
          </a>
          <a :href="exportUrl('exportar-xlsx', true)" @click="open = false"
             class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Exportar tudo
          </a>
        </div>
      </div>

      <!-- Dropdown PDF -->
      <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
        <button @click="open = !open" class="btn-secondary text-xs flex items-center gap-1.5">
          <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
          </svg>
          PDF
          <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div x-show="open" x-cloak @click.outside="open = false"
             class="absolute right-0 mt-1 w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-50 py-1">
          <a :href="exportUrl('exportar-pdf')" @click="open = false"
             class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
            </svg>
            Exportar filtrado
          </a>
          <a :href="exportUrl('exportar-pdf', true)" @click="open = false"
             class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Exportar tudo
          </a>
        </div>
      </div>

    </div>
  </div>

  <!-- Tabela -->
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

        <!-- Linhas reais -->
        <template x-for="u in items" :key="u.id">
          <tr>
            <td>
              <div class="flex items-center gap-2.5">
                <img x-show="u.avatar_url" :src="u.avatar_url" alt=""
                     class="h-7 w-7 rounded-full object-cover flex-shrink-0">
                <div x-show="!u.avatar_url"
                     class="h-7 w-7 rounded-full bg-primary/10 flex items-center justify-center
                            text-xs font-bold text-primary flex-shrink-0"
                     x-text="u.name.charAt(0).toUpperCase()"></div>
                <span class="font-medium text-slate-900" x-text="u.name"></span>
              </div>
            </td>
            <td class="text-slate-600" x-text="u.email"></td>
            <td>
              <select @change="changeRole(u.id, $event.target.value)"
                      class="text-xs border border-slate-200 rounded-lg px-2 py-1 bg-white
                             text-slate-700 focus:outline-none focus:ring-1 focus:ring-primary">
                <option value="role_requester"     :selected="u.role === 'role_requester'">Professor</option>
                <option value="role_technician"    :selected="u.role === 'role_technician'">Resp. Técnico / Apoio</option>
                <option value="role_coordinator"   :selected="u.role === 'role_coordinator'">Coordenador</option>
                <option value="role_vice_director" :selected="u.role === 'role_vice_director'">Vice-diretor</option>
                <option value="role_director"      :selected="u.role === 'role_director'">Diretor</option>
                <option value="role_admin"         :selected="u.role === 'role_admin'">Administrador</option>
              </select>
            </td>
            <td>
              <span x-show="u.google_id"  class="badge-primary">Google</span>
              <span x-show="!u.google_id" class="text-slate-300 text-xs">Local</span>
            </td>
            <td>
              <span x-show="u.is_active"  class="badge-approved badge-dot">Ativo</span>
              <span x-show="!u.is_active" class="badge-cancelled">Inativo</span>
            </td>
            <td class="text-right">
              <button @click="confirmToggle(u.id, u.name, u.is_active)"
                      :class="u.is_active
                        ? 'btn-ghost btn-sm text-red-500 hover:bg-red-50'
                        : 'btn-ghost btn-sm text-emerald-600 hover:bg-emerald-50'"
                      x-text="u.is_active ? 'Desativar' : 'Ativar'">
              </button>
            </td>
          </tr>
        </template>

        <!-- Skeleton -->
        <template x-if="loading && items.length === 0">
          <template x-for="n in 8" :key="'sk'+n">
            <tr class="animate-pulse">
              <td><div class="h-4 bg-slate-100 rounded w-36"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-44"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-28"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-12"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-12"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-16 ml-auto"></div></td>
            </tr>
          </template>
        </template>

        <!-- Estado vazio -->
        <template x-if="!loading && items.length === 0">
          <tr>
            <td colspan="6">
              <div class="empty-state py-12">
                <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <p class="empty-state-title" x-text="hasFilters() ? 'Nenhum usuário encontrado' : 'Nenhum usuário cadastrado'"></p>
                <p class="empty-state-description" x-text="hasFilters() ? 'Tente ajustar os filtros.' : 'Convide membros da instituição.'"></p>
              </div>
            </td>
          </tr>
        </template>

      </tbody>
    </table>
  </div>

  <!-- Footer / paginação -->
  <div class="card-footer flex flex-wrap items-center justify-between gap-4 px-4 py-3">

    <div class="flex items-center gap-2 text-sm text-slate-500">
      <label for="perPageSelectUsers">Exibir</label>
      <select id="perPageSelectUsers" x-model.number="perPage" @change="goTo(1)"
              class="form-input text-sm" style="width:5rem; padding-right:2rem">
        <option value="10">10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
      <span>por página</span>
      <span class="text-slate-400 hidden sm:inline" x-show="total > 0" x-cloak x-text="rangeText()"></span>
    </div>

    <nav x-show="pages > 1" x-cloak class="flex items-center gap-0.5">
      <button @click="goTo(1)"       :disabled="page===1"     class="pg-btn">⏮</button>
      <button @click="goTo(page-1)"  :disabled="page===1"     class="pg-btn">‹</button>
      <template x-for="(p, i) in visiblePages()" :key="i">
        <button
          x-text="p"
          :disabled="p === page || p === '…'"
          @click="typeof p === 'number' && goTo(p)"
          :class="p === page ? 'pg-btn-active' : p === '…' ? 'pg-ellipsis' : 'pg-btn'">
        </button>
      </template>
      <button @click="goTo(page+1)"  :disabled="page===pages" class="pg-btn">›</button>
      <button @click="goTo(pages)"   :disabled="page===pages" class="pg-btn">⏭</button>
    </nav>

  </div>

  <!-- Forms hidden para ações -->
  <form x-ref="roleForm" method="POST" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="role" value="">
  </form>
  <form x-ref="toggleForm" method="POST" class="hidden">
    <?= csrf_field() ?>
  </form>

</div><!-- /x-data -->


<!-- Convites pendentes (server-side) -->
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

<?= $this->section('scripts') ?>
<style>
.pg-btn {
  display:inline-flex; align-items:center; justify-content:center;
  min-width:2rem; height:2rem; padding:0 0.4rem;
  font-size:.8125rem; font-weight:500; border-radius:.375rem;
  color:#475569; background:transparent; border:1px solid transparent;
  transition:background 120ms,color 120ms; cursor:pointer; user-select:none;
}
.pg-btn:hover:not(:disabled) { background:#f1f5f9; color:#0f172a; border-color:#e2e8f0; }
.pg-btn:disabled              { opacity:.3; cursor:not-allowed; }
.pg-btn-active {
  display:inline-flex; align-items:center; justify-content:center;
  min-width:2rem; height:2rem; padding:0 0.4rem;
  font-size:.8125rem; font-weight:600; border-radius:.375rem;
  color:#fff; background:var(--color-primary,#3b82f6);
  border:1px solid transparent; cursor:default; user-select:none;
}
.pg-ellipsis {
  display:inline-flex; align-items:center; justify-content:center;
  min-width:1.5rem; height:2rem; font-size:.875rem;
  color:#94a3b8; cursor:default; pointer-events:none;
}
</style>
<script>
function usersPage() {
  return {
    items: [], total: 0, page: 1, pages: 1, perPage: 10, loading: false,
    filters: { q: '', role: '', status: '0' },

    init() { this.fetchPage(); },

    async fetchPage() {
      this.loading = true; this.items = [];
      const params = new URLSearchParams({
        page:   this.page,
        limit:  this.perPage,
        q:      this.filters.q,
        role:   this.filters.role,
        status: this.filters.status,
      });
      try {
        const json = await (await fetch(`<?= base_url('admin/usuarios/data') ?>?${params}`)).json();
        this.items = json.data; this.total = json.total; this.pages = json.pages;
        if (this.page > this.pages && this.pages > 0) {
          this.page = this.pages; return this.fetchPage();
        }
      } catch(e) { console.error(e); } finally { this.loading = false; }
    },

    goTo(n) {
      this.page = Math.max(1, Math.min(n, this.pages || 1));
      this.fetchPage();
    },

    exportUrl(action, all = false) {
      if (all) return `<?= base_url('admin/usuarios/') ?>${action}`;
      const p = new URLSearchParams({ q: this.filters.q, role: this.filters.role, status: this.filters.status });
      return `<?= base_url('admin/usuarios/') ?>${action}?${p}`;
    },

    changeRole(id, role) {
      const f = this.$refs.roleForm;
      f.action = `<?= base_url('admin/usuarios/') ?>${id}/role`;
      f.querySelector('[name=role]').value = role;
      f.submit();
    },

    confirmToggle(id, name, isActive) {
      const action = isActive ? 'Desativar' : 'Ativar';
      if (!confirm(`${action} o usuário «${name}»?`)) return;
      const f = this.$refs.toggleForm;
      f.action = `<?= base_url('admin/usuarios/') ?>${id}/toggle-active`;
      f.submit();
    },

    visiblePages() {
      const P = this.pages, p = this.page;
      if (P <= 7) return Array.from({ length: P }, (_, i) => i + 1);
      const arr = [1];
      if (p > 3) arr.push('…');
      const s = Math.max(2, p - 1), e = Math.min(P - 1, p + 1);
      for (let i = s; i <= e; i++) arr.push(i);
      if (p < P - 2) arr.push('…');
      arr.push(P); return arr;
    },

    hasFilters() {
      return this.filters.q !== '' || this.filters.role !== '' || this.filters.status !== '0';
    },

    rangeText() {
      if (!this.total) return '';
      const from = (this.page - 1) * this.perPage + 1;
      const to   = Math.min(this.page * this.perPage, this.total);
      return `${from}–${to} de ${this.total} usuário${this.total !== 1 ? 's' : ''}`;
    },
  }
}
</script>
<?= $this->endSection() ?>
