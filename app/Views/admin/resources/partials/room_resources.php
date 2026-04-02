<?php
/**
 * Partial: admin/resources/partials/room_resources.php
 * Included by: admin/rooms/index.php
 *
 * Self-contained Alpine.js component for the "Room Resources" modal.
 * Opens via custom event: $dispatch('open-room-resources', { roomId, roomName })
 *
 * Endpoints (AJAX, CSRF via X-CSRF-TOKEN header):
 *   GET  /admin/ambientes/:id/recursos/data           → RoomResourceController::roomData (paginated)
 *   GET  /admin/ambientes/:id/recursos/disponivel     → RoomResourceController::available (lazy search)
 *   GET  /admin/ambientes/:id/recursos/exportar-xlsx  → RoomResourceController::exportXlsx
 *   GET  /admin/ambientes/:id/recursos/exportar-pdf   → RoomResourceController::exportPdf
 *   POST /admin/ambientes/:id/recursos                → RoomResourceController::store
 *   POST /admin/ambientes/:id/recursos/:rid/delete    → RoomResourceController::destroy
 */
?>

<div
  x-data="roomResourcesModal()"
  @open-room-resources.window="open($event.detail)">

  <!-- ── Resources Modal ───────────────────────────────────────────────────── -->
  <div x-show="isOpen" class="modal-overlay" x-cloak
       @keydown.escape.window="isOpen = false"
       x-transition:enter="transition-opacity duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition-opacity duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="modal-panel" @click.stop
         style="max-width:64rem; width:100%; max-height:92vh; display:flex; flex-direction:column;"
         x-transition:enter="transition duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">

      <!-- Header -->
      <div class="modal-header" style="flex-shrink:0">
        <h3 class="text-sm font-semibold text-slate-900">
          Recursos alocados —
          <span class="text-primary" x-text="roomName"></span>
        </h3>
        <button @click="isOpen = false" class="btn-ghost btn-sm p-1" aria-label="Fechar">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- ── Add resource section ─────────────────────────────────────────── -->
      <div class="px-6 py-4 border-b border-slate-100" style="flex-shrink:0">
        <div class="flex items-end gap-2">

          <!-- Searchable lazy combobox -->
          <div class="flex-1">
            <label class="form-label">Recurso (estoque geral)</label>
            <div class="relative" @click.outside="stockDropOpen = false">

              <!-- Trigger button -->
              <button type="button"
                @click="toggleStockDrop()"
                class="form-input w-full text-left flex items-center justify-between text-sm"
                style="cursor:pointer">
                <span :class="selectedResource ? 'text-slate-900' : 'text-slate-400'"
                      x-text="selectedResource
                        ? selectedResource.name + (selectedResource.code ? ' (' + selectedResource.code + ')' : '')
                        : '— Selecione —'">
                </span>
                <svg class="w-4 h-4 text-slate-400 flex-shrink-0 ml-2 transition-transform duration-150"
                     :class="stockDropOpen && 'rotate-180'"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>

              <!-- Dropdown panel — x-show apenas controla visibilidade; flex fica no filho -->
              <div x-show="stockDropOpen" x-cloak
                   class="absolute left-0 right-0 mt-1 bg-white rounded-lg border border-slate-200 shadow-lg overflow-hidden"
                   style="z-index:60; height:240px;">

                <!-- Filho flex: não sofre com o x-show apagando display:flex -->
                <div style="display:flex; flex-direction:column; height:100%;">

                  <!-- Search input -->
                  <div class="p-2 border-b border-slate-100" style="flex-shrink:0">
                    <input type="text" x-ref="stockSearch"
                           x-model="stockQ"
                           @input.debounce.300ms="searchStock(1)"
                           @keydown.escape.stop="stockDropOpen = false"
                           placeholder="Buscar por nome ou código…"
                           class="form-input text-sm w-full">
                  </div>

                  <!-- Results list — ocupa o espaço restante e rola -->
                  <div style="flex:1; overflow-y:auto; min-height:0">
                    <template x-if="stockLoading">
                      <div class="text-center py-5 text-sm text-slate-400">Carregando…</div>
                    </template>
                    <template x-if="!stockLoading && stockItems.length === 0">
                      <div class="text-center py-5 text-sm text-slate-400">Nenhum recurso disponível.</div>
                    </template>
                    <template x-if="!stockLoading && stockItems.length > 0">
                      <ul class="divide-y divide-slate-50">
                        <template x-for="r in stockItems" :key="r.id">
                          <li @click="selectResource(r)"
                              class="px-3 py-2 text-sm cursor-pointer hover:bg-slate-50 flex items-center justify-between gap-2"
                              :class="newResourceId === r.id ? 'bg-blue-50' : ''">
                            <span :class="newResourceId === r.id ? 'text-blue-700 font-medium' : 'text-slate-800'"
                                  x-text="r.name + (r.code ? ' (' + r.code + ')' : '')"></span>
                            <span class="text-xs text-slate-400 whitespace-nowrap flex-shrink-0"
                                  x-text="'Qtd: ' + r.quantity_total"></span>
                          </li>
                        </template>
                      </ul>
                    </template>
                  </div>

                  <!-- Pagination -->
                  <div x-show="stockPages > 1"
                       class="border-t border-slate-100 px-3 py-1.5 flex items-center justify-between"
                       style="flex-shrink:0">
                    <button @click.stop="searchStock(stockPage - 1)" :disabled="stockPage <= 1"
                            class="pg-btn" style="min-width:1.75rem;height:1.75rem;font-size:.8rem">‹</button>
                    <span class="text-xs text-slate-500"
                          x-text="'Página ' + stockPage + ' de ' + stockPages + ' (' + stockTotal + ' recursos)'"></span>
                    <button @click.stop="searchStock(stockPage + 1)" :disabled="stockPage >= stockPages"
                            class="pg-btn" style="min-width:1.75rem;height:1.75rem;font-size:.8rem">›</button>
                  </div>

                </div><!-- /filho flex -->
              </div><!-- /dropdown panel -->
            </div><!-- /relative -->
          </div>

          <div class="w-28">
            <label class="form-label">Qtd. fixa</label>
            <input type="number" x-model.number="newQuantity"
                   class="form-input" min="1" max="9999" placeholder="1">
          </div>
          <button @click="addResource()"
                  :disabled="!newResourceId || saving"
                  class="btn-primary whitespace-nowrap" style="margin-bottom:0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Alocar
          </button>
        </div>

        <!-- Allocation error -->
        <template x-if="errorMsg">
          <div class="mt-3 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700"
               x-text="errorMsg"></div>
        </template>
      </div>

      <!-- ── Toolbar: search + exports ────────────────────────────────────── -->
      <div class="flex items-stretch border-b border-slate-100" style="flex-shrink:0">

        <!-- Esquerda: busca -->
        <div class="flex items-center flex-1 overflow-x-auto p-3 min-w-0 gap-3">
          <label class="flex items-center gap-0 form-input p-0 text-sm cursor-text"
                 style="flex:1 1 260px; min-width:180px; overflow:hidden;">
            <span class="flex items-center justify-center pl-3 pr-2 text-slate-400 flex-shrink-0 self-stretch">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
              </svg>
            </span>
            <input type="text" placeholder="Buscar recurso alocado…"
                   x-model="filterQ" @input.debounce.350ms="goTo(1)"
                   class="flex-1 h-full text-sm bg-transparent border-0 outline-none shadow-none py-0 px-0 pr-3"
                   style="box-shadow:none;border:none;outline:none">
          </label>
        </div>

        <!-- Separador vertical -->
        <div class="w-px bg-slate-100 self-stretch flex-shrink-0"></div>

        <!-- Direita: exports -->
        <div class="flex items-center gap-2 p-3 flex-shrink-0">

          <!-- Excel dropdown -->
          <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" @click.outside="open = false"
                    class="btn-secondary text-sm flex items-center gap-1.5 whitespace-nowrap">
              <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 17v-6h6v6m-3-6V5m-7 16h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/>
              </svg>
              Excel
              <svg class="w-3 h-3 text-slate-400 transition-transform duration-150" :class="open && 'rotate-180'"
                   fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
            <div x-show="open" x-cloak
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-lg border border-slate-100 py-1"
                 style="z-index:70">
              <a :href="exportUrl('exportar-xlsx', false)" @click="open=false"
                 class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                Exportar filtrado
              </a>
              <a :href="exportUrl('exportar-xlsx', true)" @click="open=false"
                 class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                Exportar tudo
              </a>
            </div>
          </div>

          <!-- PDF dropdown -->
          <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" @click.outside="open = false"
                    class="btn-secondary text-sm flex items-center gap-1.5 whitespace-nowrap">
              <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
              </svg>
              PDF
              <svg class="w-3 h-3 text-slate-400 transition-transform duration-150" :class="open && 'rotate-180'"
                   fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
            <div x-show="open" x-cloak
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-lg border border-slate-100 py-1"
                 style="z-index:70">
              <a :href="exportUrl('exportar-pdf', false)" @click="open=false"
                 class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                Exportar filtrado
              </a>
              <a :href="exportUrl('exportar-pdf', true)" @click="open=false"
                 class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                Exportar tudo
              </a>
            </div>
          </div>

        </div>
      </div>

      <!-- ── Table + Pagination (scrollable) ──────────────────────────────── -->
      <div style="flex:1; overflow-y:auto; min-height:0">

        <div class="overflow-x-auto">
          <table class="table-base">
            <thead>
              <tr>
                <th>Recurso</th>
                <th>Patrimônio</th>
                <th class="text-center">Qtd.</th>
                <th>Alocado por</th>
                <th>Em</th>
                <th class="text-right w-16">Ação</th>
              </tr>
            </thead>
            <tbody>

              <!-- Rows -->
              <template x-for="item in items" :key="item.room_resource_id">
                <tr>
                  <td class="font-medium text-slate-900" x-text="item.name"></td>
                  <td>
                    <span x-show="item.code" class="badge-primary text-xs" x-text="item.code"></span>
                    <span x-show="!item.code" class="text-slate-300">—</span>
                  </td>
                  <td class="text-center font-semibold text-slate-700" x-text="item.allocated_quantity"></td>
                  <td class="text-slate-600 text-sm" x-text="item.allocated_by_name ?? '—'"></td>
                  <td class="text-slate-500 text-xs whitespace-nowrap"
                      x-text="item.allocated_at ? formatDate(item.allocated_at) : '—'"></td>
                  <td class="text-right">
                    <button @click="requestRemove(item.resource_id)"
                            :disabled="saving"
                            class="btn-ghost p-1.5 text-red-500 hover:bg-red-50 hover:text-red-600"
                            aria-label="Remover alocação">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                    </button>
                  </td>
                </tr>
              </template>

              <!-- Skeleton -->
              <template x-if="loading && items.length === 0">
                <template x-for="n in 6" :key="'sk'+n">
                  <tr class="animate-pulse">
                    <td><div class="h-4 bg-slate-100 rounded w-36"></div></td>
                    <td><div class="h-4 bg-slate-100 rounded w-16"></div></td>
                    <td><div class="h-4 bg-slate-100 rounded w-8 mx-auto"></div></td>
                    <td><div class="h-4 bg-slate-100 rounded w-28"></div></td>
                    <td><div class="h-4 bg-slate-100 rounded w-20"></div></td>
                    <td></td>
                  </tr>
                </template>
              </template>

              <!-- Empty state -->
              <template x-if="!loading && items.length === 0">
                <tr>
                  <td colspan="6">
                    <div class="empty-state py-10">
                      <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                      </svg>
                      <p class="empty-state-title"
                         x-text="filterQ ? 'Nenhum resultado encontrado' : 'Nenhum recurso alocado'"></p>
                      <p class="empty-state-description"
                         x-text="filterQ ? 'Tente ajustar o termo de busca.' : 'Aloque o primeiro recurso acima.'"></p>
                    </div>
                  </td>
                </tr>
              </template>

            </tbody>
          </table>
        </div>

        <!-- Pagination footer -->
        <div class="card-footer flex flex-wrap items-center justify-between gap-4 px-4 py-3">

          <div class="flex items-center gap-2 text-sm text-slate-500">
            <label for="rrPerPage">Exibir</label>
            <select id="rrPerPage" x-model.number="perPage" @change="goTo(1)"
                    class="form-input text-sm" style="width:5rem;padding-right:2rem">
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
            </select>
            <span>por página</span>
            <span class="text-slate-400 hidden sm:inline" x-show="total > 0" x-cloak
                  x-text="rangeText()"></span>
          </div>

          <nav x-show="pages > 1" x-cloak class="flex items-center gap-0.5">
            <button @click="goTo(1)"       :disabled="page===1"     class="pg-btn">⏮</button>
            <button @click="goTo(page-1)"  :disabled="page===1"     class="pg-btn">‹</button>
            <template x-for="(p,i) in visiblePages()" :key="i">
              <button x-text="p"
                      :disabled="p===page||p==='…'"
                      @click="typeof p==='number'&&goTo(p)"
                      :class="p===page?'pg-btn-active':p==='…'?'pg-ellipsis':'pg-btn'">
              </button>
            </template>
            <button @click="goTo(page+1)"  :disabled="page===pages" class="pg-btn">›</button>
            <button @click="goTo(pages)"   :disabled="page===pages" class="pg-btn">⏭</button>
          </nav>

        </div>

      </div><!-- /scrollable area -->

      <!-- Footer -->
      <div class="modal-footer" style="flex-shrink:0">
        <button @click="isOpen = false" class="btn-secondary">Fechar</button>
      </div>
    </div>
  </div>

  <!-- ── RN-R10: Confirm Deallocation Modal ────────────────────────────────── -->
  <div x-show="confirmOpen" class="modal-overlay" x-cloak
       style="z-index:80"
       x-transition:enter="transition-opacity duration-150"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition-opacity duration-100"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="modal-panel max-w-lg" @click.stop
         x-transition:enter="transition duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">

      <div class="modal-header">
        <h3 class="text-sm font-semibold text-slate-900">Confirmar remoção de alocação</h3>
        <button @click="confirmOpen = false" class="btn-ghost btn-sm p-1" aria-label="Fechar">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="modal-body space-y-4">
        <div class="flex gap-3 p-3 rounded-md bg-amber-50 border border-amber-200">
          <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
          <p class="text-sm text-amber-800">
            Este ambiente possui reservas <strong>aprovadas</strong> no futuro.
            Ao remover a alocação, este recurso <strong>não será mais listado automaticamente</strong> nessas reservas.
            Confirma a remoção mesmo assim?
          </p>
        </div>

        <div>
          <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
            Reservas afetadas (até 10 mais próximas)
          </p>
          <ul class="divide-y divide-slate-100 text-sm">
            <template x-for="bk in futureBookings" :key="bk.id">
              <li class="py-2 flex items-center justify-between gap-2">
                <span class="font-medium text-slate-800 truncate" x-text="bk.title || '#' + bk.id"></span>
                <span class="text-slate-500 whitespace-nowrap text-xs">
                  <span x-text="bk.date_fmt"></span>
                  <span class="mx-1">·</span>
                  <span x-text="bk.start_time_fmt + ' – ' + bk.end_time_fmt"></span>
                </span>
              </li>
            </template>
          </ul>
        </div>
      </div>

      <div class="modal-footer">
        <button @click="confirmOpen = false" class="btn-secondary">Cancelar</button>
        <button @click="confirmRemove()" :disabled="saving" class="btn-danger">
          <template x-if="saving">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
          </template>
          Remover mesmo assim
        </button>
      </div>
    </div>
  </div>

</div>

<script>
function roomResourcesModal() {
  return {
    isOpen:          false,
    confirmOpen:     false,
    roomId:          null,
    roomName:        '',

    // Allocated resources table state
    items:   [],
    total:   0,
    page:    1,
    pages:   1,
    perPage: 10,
    loading: false,
    filterQ: '',

    // Allocation form state
    saving:          false,
    newResourceId:   '',
    newQuantity:     1,
    errorMsg:        '',

    // Deallocation confirm state
    pendingRemoveId: null,
    futureBookings:  [],

    // Lazy combobox state
    stockDropOpen:    false,
    stockQ:           '',
    stockItems:       [],
    stockPage:        1,
    stockPages:       1,
    stockTotal:       0,
    stockLoading:     false,
    selectedResource: null,

    async open(detail) {
      this.roomId           = detail.roomId;
      this.roomName         = detail.roomName;
      this.newResourceId    = '';
      this.newQuantity      = 1;
      this.errorMsg         = '';
      this.filterQ          = '';
      this.page             = 1;
      this.stockDropOpen    = false;
      this.stockQ           = '';
      this.stockItems       = [];
      this.stockPage        = 1;
      this.stockPages       = 1;
      this.selectedResource = null;
      this.isOpen           = true;
      await this.fetchPage();
    },

    async fetchPage() {
      this.loading = true;
      this.items   = [];
      const params = new URLSearchParams({
        page:  this.page,
        limit: this.perPage,
        q:     this.filterQ,
      });
      try {
        const res  = await fetch(`<?= base_url('admin/ambientes/') ?>${this.roomId}/recursos/data?${params}`);
        const json = await res.json();
        this.items = json.data  || [];
        this.total = json.total || 0;
        this.pages = json.pages || 1;
        if (this.page > this.pages && this.pages > 0) {
          this.page = this.pages;
          return this.fetchPage();
        }
      } catch (e) {
        console.error('Erro ao carregar recursos alocados:', e);
      } finally {
        this.loading = false;
      }
    },

    goTo(n) {
      this.page = Math.max(1, Math.min(n, this.pages || 1));
      this.fetchPage();
    },

    exportUrl(action, all = false) {
      const base = `<?= base_url('admin/ambientes/') ?>${this.roomId}/recursos/${action}`;
      if (all) return base;
      return base + '?q=' + encodeURIComponent(this.filterQ);
    },

    rangeText() {
      if (!this.total) return '';
      const from = (this.page - 1) * this.perPage + 1;
      const to   = Math.min(this.page * this.perPage, this.total);
      return `${from}–${to} de ${this.total} registro${this.total !== 1 ? 's' : ''}`;
    },

    visiblePages() {
      const P = this.pages, p = this.page;
      if (P <= 7) return Array.from({ length: P }, (_, i) => i + 1);
      const arr = [1];
      if (p > 3) arr.push('…');
      const s = Math.max(2, p - 1), e = Math.min(P - 1, p + 1);
      for (let i = s; i <= e; i++) arr.push(i);
      if (p < P - 2) arr.push('…');
      arr.push(P);
      return arr;
    },

    toggleStockDrop() {
      this.stockDropOpen = !this.stockDropOpen;
      if (this.stockDropOpen) {
        this.$nextTick(() => {
          if (this.$refs.stockSearch) this.$refs.stockSearch.focus();
          if (this.stockItems.length === 0) this.searchStock(1);
        });
      }
    },

    async searchStock(page = 1) {
      this.stockPage    = page;
      this.stockLoading = true;
      try {
        const params = new URLSearchParams({ q: this.stockQ, page: this.stockPage });
        const res    = await fetch(`<?= base_url('admin/ambientes/') ?>${this.roomId}/recursos/disponivel?${params}`);
        const data   = await res.json();
        this.stockItems = data.data  || [];
        this.stockPages = data.pages || 1;
        this.stockTotal = data.total || 0;
      } catch {
        // silent
      } finally {
        this.stockLoading = false;
      }
    },

    selectResource(r) {
      this.newResourceId    = r.id;
      this.selectedResource = r;
      this.stockDropOpen    = false;
    },

    async addResource() {
      if (!this.newResourceId) return;
      this.saving   = true;
      this.errorMsg = '';
      const form = new FormData();
      form.append('resource_id', this.newResourceId);
      form.append('quantity',    this.newQuantity || 1);
      try {
        const res  = await fetch(`<?= base_url('admin/ambientes/') ?>${this.roomId}/recursos`, {
          method:  'POST',
          headers: { 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
          body:    form,
        });
        const data = await res.json();
        if (res.ok) {
          this.newResourceId    = '';
          this.newQuantity      = 1;
          this.selectedResource = null;
          this.stockQ           = '';
          this.stockItems       = [];
          this.stockPage        = 1;
          this.page             = 1;
          await this.fetchPage();
        } else {
          this.errorMsg = data.error ?? 'Erro ao alocar recurso.';
        }
      } catch {
        this.errorMsg = 'Erro de conexão.';
      } finally {
        this.saving = false;
      }
    },

    async requestRemove(resourceId) {
      this.pendingRemoveId = resourceId;
      await this.doRemove(false);
    },

    async confirmRemove() {
      await this.doRemove(true);
    },

    async doRemove(force) {
      this.saving   = true;
      this.errorMsg = '';
      const form = new FormData();
      form.append('force', force ? '1' : '0');
      try {
        const res  = await fetch(
          `<?= base_url('admin/ambientes/') ?>${this.roomId}/recursos/${this.pendingRemoveId}/delete`,
          {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
            body:    form,
          }
        );
        const data = await res.json();

        if (data.needs_confirm) {
          this.futureBookings = data.future_bookings || [];
          this.confirmOpen    = true;
        } else if (res.ok) {
          this.confirmOpen     = false;
          this.pendingRemoveId = null;
          await this.fetchPage();
        } else {
          this.errorMsg = data.error ?? 'Erro ao remover recurso.';
        }
      } catch {
        this.errorMsg = 'Erro de conexão.';
      } finally {
        this.saving = false;
      }
    },

    formatDate(dt) {
      if (!dt) return '—';
      const d = new Date(dt.replace(' ', 'T'));
      return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    },
  };
}
</script>
