<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Minhas Reservas</h1>
    <p class="page-subtitle">Histórico e status das suas solicitações</p>
  </div>
  <div class="flex items-center gap-2">
    <a href="<?= base_url('reservas/calendario.ics') ?>"
       class="btn-secondary text-xs"
       title="Exportar reservas aprovadas para Google Calendar, Outlook, etc.">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
      </svg>
      iCal
    </a>
    <a href="<?= base_url('reservas/nova') ?>" class="btn-primary">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Nova Reserva
    </a>
  </div>
</div>

<?php if (!empty($overdueReturnCount) && $overdueReturnCount > 0): ?>
<div class="mb-4 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
  <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
  </svg>
  <span>
    Você tem <strong><?= $overdueReturnCount ?> recurso(s)</strong> com devolução pendente e prazo vencido.
    Regularize para poder criar novas reservas.
  </span>
</div>
<?php endif; ?>

<div class="card overflow-hidden" x-data="reservasPage()">

  <!-- Toolbar -->
  <div class="border-b border-slate-100 flex items-stretch">

    <!-- Esquerda: busca + filtros -->
    <div class="flex items-center gap-3 flex-1 overflow-x-auto p-4 min-w-0">

      <!-- Busca textual -->
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
               placeholder="Buscar pelo título..."
               class="flex-1 h-full text-sm bg-transparent border-0 outline-none shadow-none py-0 px-0 pr-3"
               style="box-shadow:none; border:none; outline:none">
      </label>

      <!-- Filtro de status -->
      <select class="form-input text-sm flex-shrink-0" style="min-width:160px; width:auto"
              x-model="filters.status" @change="goTo(1)">
        <option value="">Todos os status</option>
        <option value="pending">Pendente</option>
        <option value="approved">Aprovada</option>
        <option value="rejected">Recusada</option>
        <option value="cancelled">Cancelada</option>
        <option value="absent">Ausente</option>
      </select>

      <!-- Filtro de ambiente -->
      <?php if (!empty($rooms)): ?>
      <select class="form-input text-sm flex-shrink-0" style="min-width:170px; width:auto"
              x-model="filters.roomId" @change="goTo(1)">
        <option value="0">Todos os ambientes</option>
        <?php foreach ($rooms as $r): ?>
          <option value="<?= $r['id'] ?>"><?= esc($r['name']) ?><?= !empty($r['code']) ? ' (' . esc($r['code']) . ')' : '' ?></option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>

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
          <th>Título</th>
          <th>Ambiente</th>
          <th>Data</th>
          <th>Horário</th>
          <th>Status</th>
          <th class="text-right">Ações</th>
        </tr>
      </thead>
      <tbody>

        <!-- Linhas reais -->
        <template x-for="r in items" :key="r.id">
          <tr>
            <td>
              <div class="font-medium text-slate-900" x-text="r.title"></div>
              <div x-show="r.review_notes && (r.status === 'rejected' || r.status === 'approved')"
                   class="text-xs text-slate-400 mt-0.5 truncate max-w-xs" x-text="r.review_notes"></div>
            </td>
            <td>
              <div class="text-slate-700" x-text="r.room_name || '—'"></div>
              <div x-show="r.building_name" class="text-xs text-slate-400" x-text="r.building_name"></div>
            </td>
            <td class="whitespace-nowrap">
              <span x-text="fmtDate(r.date)"></span>
              <div class="text-xs text-slate-400" x-text="fmtWeekday(r.date)"></div>
            </td>
            <td class="whitespace-nowrap text-sm"
                x-text="r.start_time.substring(0,5) + ' – ' + r.end_time.substring(0,5)"></td>
            <td>
              <span :class="statusBadge(r.status)" x-text="statusLabel(r.status)"></span>
            </td>
            <td class="text-right whitespace-nowrap">
              <div class="flex items-center justify-end gap-1">
                <a :href="`<?= base_url('reservas/') ?>${r.id}`"
                   class="btn-ghost p-2 text-blue-500 hover:bg-blue-50 hover:text-blue-600"
                   aria-label="Ver detalhes">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
                         -1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </a>
                <template x-if="r.status === 'pending' || r.status === 'approved'">
                  <button @click="confirmCancel(r.id, r.title)"
                          class="btn-ghost px-2 py-1 text-xs text-red-500 hover:bg-red-50">
                    Cancelar
                  </button>
                </template>
              </div>
            </td>
          </tr>
        </template>

        <!-- Skeleton -->
        <template x-if="loading && items.length === 0">
          <template x-for="n in 8" :key="'sk'+n">
            <tr class="animate-pulse">
              <td><div class="h-4 bg-slate-100 rounded w-40"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-32"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-20"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-24"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-16"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-12 ml-auto"></div></td>
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
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                       M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="empty-state-title" x-text="hasFilters() ? 'Nenhuma reserva encontrada' : 'Você ainda não fez reservas'"></p>
                <p class="empty-state-description" x-text="hasFilters() ? 'Tente ajustar os filtros.' : 'Clique em Nova Reserva para começar.'"></p>
                <a x-show="!hasFilters()" href="<?= base_url('reservas/nova') ?>" class="btn-primary mt-4">Fazer reserva</a>
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
      <label for="perPageSelectRes">Exibir</label>
      <select id="perPageSelectRes" x-model.number="perPage" @change="goTo(1)"
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

  <!-- Form hidden para cancelamento -->
  <form x-ref="cancelForm" method="POST" class="hidden">
    <?= csrf_field() ?>
  </form>

</div>

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
function reservasPage() {
  return {
    items: [], total: 0, page: 1, pages: 1, perPage: 10, loading: false,
    filters: { q: '', status: '', roomId: '0' },

    init() { this.fetchPage(); },

    async fetchPage() {
      this.loading = true; this.items = [];
      const params = new URLSearchParams({
        page:    this.page,
        limit:   this.perPage,
        q:       this.filters.q,
        status:  this.filters.status,
        room_id: this.filters.roomId,
      });
      try {
        const json = await (await fetch(`<?= base_url('reservas/data') ?>?${params}`)).json();
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
      if (all) return `<?= base_url('reservas/') ?>${action}`;
      const p = new URLSearchParams({
        q:       this.filters.q,
        status:  this.filters.status,
        room_id: this.filters.roomId,
      });
      return `<?= base_url('reservas/') ?>${action}?${p}`;
    },

    confirmCancel(id, title) {
      if (!confirm(`Cancelar a reserva «${title}»?`)) return;
      const f = this.$refs.cancelForm;
      f.action = `<?= base_url('reservas/') ?>${id}/cancelar`;
      f.submit();
    },

    statusLabel(s) {
      const map = { pending: 'Pendente', approved: 'Aprovada', rejected: 'Recusada', cancelled: 'Cancelada', absent: 'Ausente' };
      return map[s] || s;
    },

    statusBadge(s) {
      const map = { pending: 'badge-warning', approved: 'badge-approved', rejected: 'badge-cancelled', cancelled: 'badge-cancelled', absent: 'badge-warning' };
      return map[s] || 'badge-primary';
    },

    fmtDate(iso) {
      if (!iso) return '';
      const [y, m, d] = iso.split('-'); return `${d}/${m}/${y}`;
    },

    fmtWeekday(iso) {
      if (!iso) return '';
      const days = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
      return days[new Date(iso + 'T12:00:00').getDay()];
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
      return this.filters.q !== '' || this.filters.status !== '' || this.filters.roomId !== '0';
    },

    rangeText() {
      if (!this.total) return '';
      const from = (this.page - 1) * this.perPage + 1;
      const to   = Math.min(this.page * this.perPage, this.total);
      return `${from}–${to} de ${this.total} reserva${this.total !== 1 ? 's' : ''}`;
    },
  }
}
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
