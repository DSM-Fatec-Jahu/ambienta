<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Prédios</h1>
    <p class="page-subtitle">Gerencie os prédios da instituição</p>
  </div>
  <button @click="$dispatch('open-building-modal', { mode: 'create' })" class="btn-primary">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    Novo Prédio
  </button>
</div>

<div class="card overflow-hidden" x-data="buildingsPage()">

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
               placeholder="Buscar por nome, código ou descrição..."
               class="flex-1 h-full text-sm bg-transparent border-0 outline-none shadow-none py-0 px-0 pr-3"
               style="box-shadow:none; border:none; outline:none">
      </label>

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
          <th>Nome</th>
          <th>Código</th>
          <th>Descrição</th>
          <th>Status</th>
          <th class="w-24 text-right">Ações</th>
        </tr>
      </thead>
      <tbody>

        <!-- Linhas reais -->
        <template x-for="b in items" :key="b.id">
          <tr>
            <td class="font-medium text-slate-900" x-text="b.name"></td>
            <td>
              <span x-show="b.code" class="badge-primary" x-text="b.code"></span>
              <span x-show="!b.code" class="text-slate-300">—</span>
            </td>
            <td class="text-slate-500 max-w-xs truncate" x-text="b.description || '—'"></td>
            <td>
              <span x-show="b.is_active"  class="badge-approved badge-dot">Ativo</span>
              <span x-show="!b.is_active" class="badge-cancelled">Inativo</span>
            </td>
            <td class="text-right">
              <div class="flex items-center justify-end gap-1">
                <button
                  @click="openModal({ mode: 'edit', id: b.id, name: b.name, code: b.code || '', description: b.description || '', is_active: b.is_active })"
                  class="btn-ghost p-2 text-yellow-500 hover:bg-yellow-50 hover:text-yellow-600"
                  aria-label="Editar">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                         m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </button>
                <button @click="confirmDelete(b.id, b.name)"
                        class="btn-ghost p-2 text-red-500 hover:bg-red-50 hover:text-red-600"
                        aria-label="Excluir">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6
                         m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </template>

        <!-- Skeleton -->
        <template x-if="loading && items.length === 0">
          <template x-for="n in 8" :key="'sk'+n">
            <tr class="animate-pulse">
              <td><div class="h-4 bg-slate-100 rounded w-40"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-16"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-48"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-12"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-12 ml-auto"></div></td>
            </tr>
          </template>
        </template>

        <!-- Estado vazio -->
        <template x-if="!loading && items.length === 0">
          <tr>
            <td colspan="5">
              <div class="empty-state py-12">
                <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                </svg>
                <p class="empty-state-title" x-text="hasFilters() ? 'Nenhum prédio encontrado' : 'Nenhum prédio cadastrado'"></p>
                <p class="empty-state-description" x-text="hasFilters() ? 'Tente ajustar os filtros.' : 'Adicione o primeiro prédio para organizar os ambientes.'"></p>
                <button x-show="!hasFilters()"
                        @click="$dispatch('open-building-modal', { mode: 'create' })"
                        class="btn-primary mt-4">
                  Cadastrar Prédio
                </button>
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
      <label for="perPageSelectPred">Exibir</label>
      <select id="perPageSelectPred" x-model.number="perPage" @change="goTo(1)"
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
      <button @click="goTo(1)"        :disabled="page===1"     class="pg-btn">⏮</button>
      <button @click="goTo(page-1)"   :disabled="page===1"     class="pg-btn">‹</button>
      <template x-for="(p, i) in visiblePages()" :key="i">
        <button
          x-text="p"
          :disabled="p === page || p === '…'"
          @click="typeof p === 'number' && goTo(p)"
          :class="p === page ? 'pg-btn-active' : p === '…' ? 'pg-ellipsis' : 'pg-btn'">
        </button>
      </template>
      <button @click="goTo(page+1)"   :disabled="page===pages" class="pg-btn">›</button>
      <button @click="goTo(pages)"    :disabled="page===pages" class="pg-btn">⏭</button>
    </nav>

  </div>

  <!-- Form hidden para exclusão -->
  <form x-ref="deleteForm" method="POST" class="hidden">
    <?= csrf_field() ?>
  </form>

  <!-- Modal create/edit -->
  <div x-show="modalOpen" class="modal-overlay" x-cloak
       @open-building-modal.window="openModal($event.detail)"
       x-transition:enter="transition-opacity duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition-opacity duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="modal-panel max-w-lg" @click.stop
         x-transition:enter="transition duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">

      <div class="modal-header">
        <h3 class="text-sm font-semibold text-slate-900" x-text="mode === 'create' ? 'Novo Prédio' : 'Editar Prédio'"></h3>
        <button @click="modalOpen = false" class="btn-ghost btn-sm p-1" aria-label="Fechar">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <form :action="mode === 'create' ? '<?= base_url('admin/predios') ?>' : `<?= base_url('admin/predios/') ?>${editId}/update`"
            method="POST">
        <?= csrf_field() ?>

        <div class="modal-body space-y-4">
          <div>
            <label for="b_name" class="form-label form-label-required">Nome</label>
            <input type="text" id="b_name" name="name" x-model="form.name"
                   class="form-input" placeholder="Ex: Bloco A" maxlength="200" required>
          </div>
          <div>
            <label for="b_code" class="form-label">Código / Sigla</label>
            <input type="text" id="b_code" name="code" x-model="form.code"
                   class="form-input" placeholder="Ex: BLK-A" maxlength="20">
          </div>
          <div>
            <label for="b_desc" class="form-label">Descrição</label>
            <textarea id="b_desc" name="description" x-model="form.description"
                      rows="2" class="form-input resize-none"
                      placeholder="Descrição opcional"></textarea>
          </div>
          <div class="flex items-center gap-3">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="b_active" name="is_active" value="1"
                   x-model="form.is_active" class="rounded border-slate-300 text-primary">
            <label for="b_active" class="text-sm text-slate-700">Prédio ativo</label>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" @click="modalOpen = false" class="btn-secondary">Cancelar</button>
          <button type="submit" class="btn-primary" x-text="mode === 'create' ? 'Cadastrar' : 'Salvar'"></button>
        </div>
      </form>
    </div>
  </div>

</div><!-- /x-data -->

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
function buildingsPage() {
  return {
    items: [], total: 0, page: 1, pages: 1, perPage: 10, loading: false,
    filters: { q: '', status: '0' },

    // Modal state
    modalOpen: false,
    mode: 'create',
    editId: null,
    form: { name: '', code: '', description: '', is_active: true },

    init() { this.fetchPage(); },

    async fetchPage() {
      this.loading = true; this.items = [];
      const params = new URLSearchParams({
        page:   this.page,
        limit:  this.perPage,
        q:      this.filters.q,
        status: this.filters.status,
      });
      try {
        const json = await (await fetch(`<?= base_url('admin/predios/data') ?>?${params}`)).json();
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
      if (all) return `<?= base_url('admin/predios/') ?>${action}`;
      const p = new URLSearchParams({ q: this.filters.q, status: this.filters.status });
      return `<?= base_url('admin/predios/') ?>${action}?${p}`;
    },

    confirmDelete(id, name) {
      if (!confirm(`Excluir o prédio «${name}»?`)) return;
      const f = this.$refs.deleteForm;
      f.action = `<?= base_url('admin/predios/') ?>${id}/delete`;
      f.submit();
    },

    openModal(detail) {
      this.mode = detail.mode;
      if (detail.mode === 'edit') {
        this.editId           = detail.id;
        this.form.name        = detail.name;
        this.form.code        = detail.code;
        this.form.description = detail.description;
        this.form.is_active   = detail.is_active;
      } else {
        this.editId = null;
        this.form = { name: '', code: '', description: '', is_active: true };
      }
      this.modalOpen = true;
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
      return this.filters.q !== '' || this.filters.status !== '0';
    },

    rangeText() {
      if (!this.total) return '';
      const from = (this.page - 1) * this.perPage + 1;
      const to   = Math.min(this.page * this.perPage, this.total);
      return `${from}–${to} de ${this.total} prédio${this.total !== 1 ? 's' : ''}`;
    },
  }
}
</script>
<?= $this->endSection() ?>
