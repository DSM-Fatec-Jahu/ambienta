<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Recursos</h1>
    <p class="page-subtitle">Recursos, dispositivos e demais itens gerenciados pela instituição</p>
  </div>
  <div class="flex items-center gap-2">
    <button @click="$dispatch('open-import-modal')" class="btn-secondary flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
      </svg>
      Importar XLSX
    </button>
    <button @click="$dispatch('open-resource-modal', { mode: 'create' })" class="btn-primary">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Novo Recurso
    </button>
  </div>
</div>

<div class="card overflow-hidden" x-data="resourcePage()">

  <!-- ── Toolbar ─────────────────────────────────────────────────────────── -->
  <div class="border-b border-slate-100 flex items-stretch">

    <!-- Esquerda: busca + filtros -->
    <div class="flex items-center gap-3 flex-1 overflow-x-auto p-4 min-w-0">

      <!-- Busca -->
      <label class="flex items-center gap-0 form-input p-0 pl-2 text-sm cursor-text"
             style="flex:1 1 320px; min-width:220px; overflow:hidden;">
        <span class="flex items-center justify-center pl-3 pr-2 text-slate-400 flex-shrink-0 self-stretch"
              style="margin-left:15px">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 21l-4.35-4.35M17 11A6 6 0 111 11a6 6 0 0116 0z"/>
          </svg>
        </span>
        <input type="text" x-model="filters.q" @input.debounce.350ms="goTo(1)"
               placeholder="Buscar por nome ou patrimônio…"
               class="flex-1 h-full text-sm bg-transparent border-0 outline-none shadow-none py-0 px-0 pr-3"
               style="box-shadow:none; border:none; outline:none">
      </label>

      <!-- Categoria -->
      <select class="form-input text-sm flex-shrink-0" style="min-width:160px; width:auto"
              x-model="filters.categoria" @change="goTo(1)">
        <option value="">Todas as categorias</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= esc($cat['category']) ?>"><?= esc($cat['category']) ?></option>
        <?php endforeach; ?>
      </select>

      <!-- Localização -->
      <select class="form-input text-sm flex-shrink-0" style="min-width:170px; width:auto"
              x-model="filters.local" @change="goTo(1)">
        <option value="0">Todas as localizações</option>
        <option value="-1">Estoque geral</option>
        <?php foreach ($rooms as $room): ?>
          <option value="<?= (int) $room['id'] ?>"><?= esc($room['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <!-- Status -->
      <select class="form-input text-sm flex-shrink-0" style="min-width:130px; width:auto"
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
      <div class="relative">
        <button @click="showXlsxMenu = !showXlsxMenu; showPdfMenu = false"
                class="btn-secondary flex items-center gap-1.5 text-sm">
          <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 17v-2m3 2v-4m3 4v-6M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
          </svg>
          Excel
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div x-show="showXlsxMenu" @click.outside="showXlsxMenu = false"
             class="absolute right-0 z-50 mt-1 w-48 bg-white rounded-lg shadow-lg border border-slate-100 py-1"
             x-cloak>
          <a :href="exportUrl('exportar-xlsx')"
             class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
             @click="showXlsxMenu = false">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
            </svg>
            Exportar filtrado
          </a>
          <a :href="exportUrl('exportar-xlsx', true)"
             class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
             @click="showXlsxMenu = false">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Exportar tudo
          </a>
        </div>
      </div>

      <!-- Dropdown PDF -->
      <div class="relative">
        <button @click="showPdfMenu = !showPdfMenu; showXlsxMenu = false"
                class="btn-secondary flex items-center gap-1.5 text-sm">
          <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
          </svg>
          PDF
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div x-show="showPdfMenu" @click.outside="showPdfMenu = false"
             class="absolute right-0 z-50 mt-1 w-48 bg-white rounded-lg shadow-lg border border-slate-100 py-1"
             x-cloak>
          <a :href="exportUrl('exportar-pdf')"
             class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
             @click="showPdfMenu = false">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
            </svg>
            Exportar filtrado
          </a>
          <a :href="exportUrl('exportar-pdf', true)"
             class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
             @click="showPdfMenu = false">
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

  <!-- ── Tabela ───────────────────────────────────────────────────────────── -->
  <div class="overflow-x-auto">
    <table class="table-base">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Categoria</th>
          <th>Patrimônio</th>
          <th class="text-center">Quantidade</th>
          <th>Localização atual</th>
          <th>Status</th>
          <th class="w-36 text-right">Ações</th>
        </tr>
      </thead>
      <tbody>

        <!-- Linhas reais -->
        <template x-for="r in items" :key="r.id">
          <tr>
            <td class="font-medium text-slate-900" x-text="r.name"></td>
            <td class="text-slate-500" x-text="r.category || '—'"></td>
            <td>
              <span x-show="r.code" class="badge-primary" x-text="r.code"></span>
              <span x-show="!r.code" class="text-slate-300">—</span>
            </td>
            <td class="text-center font-semibold text-slate-700" x-text="r.quantity_total"></td>
            <td>
              <span x-show="r.current_room_name"
                    class="inline-flex items-center gap-1 text-sm text-slate-700"
                    x-text="r.current_room_name + (r.current_room_abbr ? ' (' + r.current_room_abbr + ')' : '')"></span>
              <span x-show="!r.current_room_name"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-sky-50 text-sky-700 border border-sky-200">
                Estoque geral
              </span>
            </td>
            <td>
              <span :class="r.is_active ? 'badge-approved badge-dot' : 'badge-cancelled'"
                    x-text="r.is_active ? 'Ativo' : 'Inativo'"></span>
            </td>
            <td class="text-right">
              <div class="flex items-center justify-end gap-1">
                <!-- Histórico -->
                <button @click="openHistory(r.id, r.name)"
                        class="btn-ghost p-2 text-indigo-500 hover:bg-indigo-50 hover:text-indigo-600"
                        title="Histórico de movimentações">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0"/>
                  </svg>
                </button>
                <!-- Editar -->
                <button @click="$dispatch('open-resource-modal', {
                          mode: 'edit', id: r.id, name: r.name,
                          category: r.category || '', code: r.code || '',
                          description: r.description || '',
                          quantity_total: r.quantity_total, is_active: r.is_active })"
                        class="btn-ghost p-2 text-yellow-500 hover:bg-yellow-50 hover:text-yellow-600"
                        title="Editar">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                         m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </button>
                <!-- Excluir -->
                <button @click="confirmDelete(r.id, r.name)"
                        class="btn-ghost p-2 text-red-500 hover:bg-red-50 hover:text-red-600"
                        title="Excluir">
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
              <td><div class="h-4 bg-slate-100 rounded w-24"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-20"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-8 mx-auto"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-32"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-16"></div></td>
              <td><div class="h-4 bg-slate-100 rounded w-20 ml-auto"></div></td>
            </tr>
          </template>
        </template>

        <!-- Estado vazio -->
        <template x-if="!loading && items.length === 0">
          <tr>
            <td colspan="7">
              <div class="empty-state py-12">
                <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <p class="empty-state-title"
                   x-text="hasFilters() ? 'Nenhum resultado encontrado' : 'Nenhum recurso cadastrado'"></p>
                <p class="empty-state-description"
                   x-text="hasFilters() ? 'Tente ajustar os filtros de busca.' : 'Adicione recursos para que possam ser solicitados nas reservas.'"></p>
                <button x-show="!hasFilters()"
                        @click="$dispatch('open-resource-modal', { mode: 'create' })"
                        class="btn-primary mt-4">
                  Cadastrar Recurso
                </button>
              </div>
            </td>
          </tr>
        </template>

      </tbody>
    </table>
  </div>

  <!-- ── Footer / Paginação ───────────────────────────────────────────────── -->
  <div class="card-footer flex flex-wrap items-center justify-between gap-4 px-4 py-3">
    <div class="flex items-center gap-2 text-sm text-slate-500">
      <label for="perPageSelect">Exibir</label>
      <select id="perPageSelect" x-model.number="perPage" @change="goTo(1)"
              class="form-input text-sm" style="width:5rem; padding-right:2rem">
        <option value="10">10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
      <span>por página</span>
      <span class="text-slate-400 hidden sm:inline" x-show="total > 0" x-cloak
            x-text="rangeText()"></span>
    </div>

    <nav x-show="pages > 1" x-cloak class="flex items-center gap-0.5">
      <button @click="goTo(1)"      :disabled="page===1"     class="pg-btn">⏮</button>
      <button @click="goTo(page-1)" :disabled="page===1"     class="pg-btn">‹</button>
      <template x-for="(p, i) in visiblePages()" :key="i">
        <button
          x-text="p"
          :disabled="p === page || p === '…'"
          @click="typeof p === 'number' && goTo(p)"
          :class="p === page ? 'pg-btn-active' : p === '…' ? 'pg-ellipsis' : 'pg-btn'">
        </button>
      </template>
      <button @click="goTo(page+1)" :disabled="page===pages" class="pg-btn">›</button>
      <button @click="goTo(pages)"  :disabled="page===pages" class="pg-btn">⏭</button>
    </nav>
  </div>

  <!-- Formulário oculto para delete -->
  <form x-ref="deleteForm" method="POST" style="display:none">
    <?= csrf_field() ?>
  </form>

  <!-- ── Create/Edit Modal ──────────────────────────────────────────────── -->
  <div x-show="modalOpen" class="modal-overlay" x-cloak
       @open-resource-modal.window="openModal($event.detail)"
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
        <h3 class="text-sm font-semibold text-slate-900"
            x-text="mode === 'create' ? 'Novo Recurso' : 'Editar Recurso'"></h3>
        <button @click="modalOpen = false" class="btn-ghost btn-sm p-1" aria-label="Fechar">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <form :action="mode === 'create'
                     ? '<?= base_url('admin/recursos') ?>'
                     : `<?= base_url('admin/recursos/') ?>${editId}/update`"
            method="POST">
        <?= csrf_field() ?>

        <div class="modal-body space-y-4">

          <div>
            <label for="r_name" class="form-label form-label-required">Nome</label>
            <input type="text" id="r_name" name="name" x-model="form.name"
                   class="form-input" placeholder="Ex: Projetor Epson EB-X51" maxlength="150" required>
          </div>

          <div>
            <label for="r_category" class="form-label">Categoria</label>
            <input type="text" id="r_category" name="category" x-model="form.category"
                   class="form-input" placeholder="Ex: Audiovisual, Informática..." maxlength="80">
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="r_code" class="form-label">Nº de Patrimônio</label>
              <input type="text" id="r_code" name="code" x-model="form.code"
                     @input="onCodeInput()"
                     class="form-input" placeholder="Ex: PRJ-001" maxlength="50">
              <p x-show="form.code !== '' && form.code.trim() !== ''"
                 class="mt-1 text-xs text-amber-600 font-medium flex items-center gap-1">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                Patrimônio: quantidade travada em 1
              </p>
            </div>
            <div>
              <label for="r_qty" class="form-label form-label-required">Quantidade total</label>
              <input type="number" id="r_qty" name="quantity_total"
                     x-model="form.quantity_total"
                     :disabled="form.code !== '' && form.code.trim() !== ''"
                     :class="form.code !== '' && form.code.trim() !== '' ? 'form-input opacity-50 cursor-not-allowed bg-slate-100' : 'form-input'"
                     min="1" max="9999" required>
            </div>
          </div>

          <div>
            <label for="r_desc" class="form-label">Descrição</label>
            <textarea id="r_desc" name="description" x-model="form.description"
                      rows="2" class="form-input resize-none"
                      placeholder="Modelo, características técnicas..."></textarea>
          </div>

          <div class="flex items-center gap-3">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="r_active" name="is_active" value="1"
                   x-model="form.is_active" class="rounded border-slate-300 text-primary">
            <label for="r_active" class="text-sm text-slate-700">Recurso ativo</label>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" @click="modalOpen = false" class="btn-secondary">Cancelar</button>
          <button type="submit" class="btn-primary"
                  x-text="mode === 'create' ? 'Cadastrar' : 'Salvar'"></button>
        </div>
      </form>
    </div>
  </div>

  <!-- ── History Modal ─────────────────────────────────────────────────── -->
  <div x-show="historyOpen" class="modal-overlay" x-cloak
       x-transition:enter="transition-opacity duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition-opacity duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="modal-panel max-w-3xl" @click.stop
         x-transition:enter="transition duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">

      <div class="modal-header">
        <h3 class="text-sm font-semibold text-slate-900">
          Histórico de Movimentações — <span class="text-primary" x-text="historyName"></span>
        </h3>
        <button @click="historyOpen = false" class="btn-ghost btn-sm p-1" aria-label="Fechar">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="modal-body p-0">
        <template x-if="historyLoading">
          <div class="p-6 text-center text-slate-400 text-sm">Carregando...</div>
        </template>

        <template x-if="!historyLoading && historyRows.length === 0">
          <div class="p-6 text-center text-slate-400 text-sm">
            Nenhuma movimentação registrada para este recurso.
          </div>
        </template>

        <template x-if="!historyLoading && historyRows.length > 0">
          <div class="overflow-x-auto">
            <table class="table-base">
              <thead>
                <tr>
                  <th>Data</th>
                  <th>Tipo</th>
                  <th class="text-center">Qtd.</th>
                  <th>Origem</th>
                  <th>Destino</th>
                  <th>Responsável</th>
                  <th>Observação</th>
                </tr>
              </thead>
              <tbody>
                <template x-for="row in historyRows" :key="row.id">
                  <tr>
                    <td class="whitespace-nowrap text-xs" x-text="formatDate(row.moved_at || row.transferred_at)"></td>
                    <td>
                      <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                            :class="movementTypeClass(row.movement_type)"
                            x-text="movementTypeLabel(row.movement_type)"></span>
                    </td>
                    <td class="text-center font-semibold" x-text="row.quantity"></td>
                    <td class="text-sm">
                      <span x-show="row.origin_room_name"
                            x-text="row.origin_room_name + (row.origin_room_abbr ? ' (' + row.origin_room_abbr + ')' : '')"></span>
                      <span x-show="!row.origin_room_name" class="text-slate-400 italic">—</span>
                    </td>
                    <td class="text-sm">
                      <span x-show="row.destination_room_name"
                            x-text="row.destination_room_name + (row.destination_room_abbr ? ' (' + row.destination_room_abbr + ')' : '')"></span>
                      <span x-show="!row.destination_room_name" class="text-slate-400 italic">—</span>
                    </td>
                    <td class="text-sm" x-text="row.handler_name || '—'"></td>
                    <td class="text-xs text-slate-500 max-w-[160px] truncate" x-text="row.notes || '—'"></td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </template>
      </div>

      <div class="modal-footer">
        <button @click="historyOpen = false" class="btn-secondary">Fechar</button>
      </div>
    </div>
  </div>

  <!-- ── Import Modal (XLSX) ──────────────────────────────────────────── -->
  <div x-show="importOpen" class="modal-overlay" x-cloak
       @open-import-modal.window="importOpen = true; importResult = null; importFile = null"
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
        <h3 class="text-sm font-semibold text-slate-900">Importar Recursos</h3>
        <button @click="importOpen = false" class="btn-ghost btn-sm p-1" aria-label="Fechar">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="modal-body space-y-4">
        <p class="text-xs text-slate-500">
          Envie um arquivo <strong>.xlsx</strong> (recomendado) ou <strong>.csv</strong> com as colunas:
          <strong>nome</strong> (obrigatório), <strong>patrimonio</strong>,
          <strong>categoria</strong>, <strong>descricao</strong> e <strong>quantidade</strong>.
          Recursos com patrimônio terão quantidade forçada para 1.
        </p>

        <a href="<?= base_url('admin/recursos/template-xlsx') ?>"
           class="inline-flex items-center gap-1.5 text-xs text-primary hover:underline">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
          </svg>
          Baixar modelo XLSX
        </a>

        <div>
          <label class="form-label form-label-required">Arquivo (.xlsx ou .csv)</label>
          <input type="file" accept=".xlsx,.csv,.txt"
                 @change="importFile = $event.target.files[0]"
                 class="form-input text-sm">
        </div>

        <template x-if="importResult">
          <div>
            <div x-show="importResult.imported > 0"
                 class="rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm p-3 mb-2"
                 x-text="importResult.imported + ' recurso(s) importado(s) com sucesso.'"></div>
            <template x-if="importResult.errors && importResult.errors.length > 0">
              <div class="rounded-md bg-red-50 border border-red-200 text-red-800 text-xs p-3 space-y-1">
                <p class="font-semibold" x-text="'Linhas ignoradas (' + importResult.errors.length + '):'"></p>
                <template x-for="e in importResult.errors" :key="e.row">
                  <p x-text="'Linha ' + e.row + ': ' + e.message"></p>
                </template>
              </div>
            </template>
          </div>
        </template>
      </div>

      <div class="modal-footer">
        <button type="button" @click="importOpen = false" class="btn-secondary">Fechar</button>
        <button @click="uploadFile()" :disabled="!importFile || importing" class="btn-primary">
          <span x-text="importing ? 'Importando...' : 'Importar'"></span>
        </button>
      </div>
    </div>
  </div>

</div><!-- /.card -->

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
function resourcePage() {
  return {
    // tabela
    items: [], total: 0, page: 1, pages: 1, perPage: 10, loading: false,
    filters: { q: '', categoria: '', local: '0', status: '0' },

    // dropdowns de export
    showXlsxMenu: false,
    showPdfMenu: false,

    // modal criar/editar
    modalOpen: false,
    mode: 'create',
    editId: null,
    form: { name: '', category: '', code: '', description: '', quantity_total: 1, is_active: true },

    // modal histórico
    historyOpen: false,
    historyName: '',
    historyLoading: false,
    historyRows: [],

    // modal importar
    importOpen: false,
    importFile: null,
    importing: false,
    importResult: null,

    init() { this.fetchPage(); },

    async fetchPage() {
      this.loading = true; this.items = [];
      const params = new URLSearchParams({
        page: this.page, limit: this.perPage,
        q: this.filters.q,
        categoria: this.filters.categoria,
        local: this.filters.local,
        status: this.filters.status,
      });
      try {
        const json = await (await fetch(`<?= base_url('admin/recursos/data') ?>?${params}`)).json();
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
      if (all) return `<?= base_url('admin/recursos/') ?>${action}`;
      const p = new URLSearchParams({
        q: this.filters.q,
        categoria: this.filters.categoria,
        local: this.filters.local,
        status: this.filters.status,
      });
      return `<?= base_url('admin/recursos/') ?>${action}?${p}`;
    },

    openModal(detail) {
      this.mode = detail.mode;
      if (detail.mode === 'edit') {
        this.editId = detail.id;
        this.form = {
          name:           detail.name,
          category:       detail.category || '',
          code:           detail.code || '',
          description:    detail.description || '',
          quantity_total: detail.quantity_total,
          is_active:      detail.is_active,
        };
      } else {
        this.editId = null;
        this.form = { name: '', category: '', code: '', description: '', quantity_total: 1, is_active: true };
      }
      this.modalOpen = true;
    },

    onCodeInput() {
      if (this.form.code && this.form.code.trim() !== '') {
        this.form.quantity_total = 1;
      }
    },

    async openHistory(id, name) {
      this.historyName    = name;
      this.historyOpen    = true;
      this.historyLoading = true;
      this.historyRows    = [];
      try {
        const res  = await fetch(`<?= base_url('admin/recursos/') ?>${id}/historico`);
        const data = await res.json();
        this.historyRows = data.history || [];
      } catch (e) {
        this.historyRows = [];
      } finally {
        this.historyLoading = false;
      }
    },

    movementTypeLabel(type) {
      const labels = {
        room_allocation:   'Alocação em ambiente',
        room_deallocation: 'Desalocação',
        booking_checkout:  'Saída via reserva',
        booking_return:    'Devolução registrada',
        return_confirmed:  'Devolução confirmada',
        return_rejected:   'Devolução recusada',
      };
      return labels[type] || type || 'Movimentação';
    },

    movementTypeClass(type) {
      const classes = {
        room_allocation:   'bg-sky-100 text-sky-700',
        room_deallocation: 'bg-slate-100 text-slate-600',
        booking_checkout:  'bg-amber-100 text-amber-700',
        booking_return:    'bg-emerald-100 text-emerald-700',
        return_confirmed:  'bg-green-100 text-green-700',
        return_rejected:   'bg-red-100 text-red-700',
      };
      return classes[type] || 'bg-slate-100 text-slate-600';
    },

    formatDate(dt) {
      if (!dt) return '—';
      const d = new Date(dt.replace(' ', 'T'));
      return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    },

    async uploadFile() {
      if (!this.importFile) return;
      this.importing    = true;
      this.importResult = null;
      const formData = new FormData();
      formData.append('import_file', this.importFile);
      try {
        const res  = await fetch('<?= base_url('admin/recursos/importar') ?>', {
          method:  'POST',
          headers: { 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
          body:    formData,
        });
        const data = await res.json();
        this.importResult = data;
        if (res.ok && data.imported > 0) {
          setTimeout(() => location.reload(), 1800);
        }
      } catch (e) {
        this.importResult = { imported: 0, errors: [{ row: '—', message: 'Erro de conexão.' }] };
      } finally {
        this.importing = false;
      }
    },

    confirmDelete(id, name) {
      if (!confirm(`Excluir «${name}»?`)) return;
      const f = this.$refs.deleteForm;
      f.action = `<?= base_url('admin/recursos/') ?>${id}/delete`;
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
      return this.filters.q !== '' || this.filters.categoria !== '' ||
             this.filters.local !== '0' || this.filters.status !== '0';
    },

    rangeText() {
      if (!this.total) return '';
      const from = (this.page - 1) * this.perPage + 1;
      const to   = Math.min(this.page * this.perPage, this.total);
      return `${from}–${to} de ${this.total} registro${this.total !== 1 ? 's' : ''}`;
    },
  };
}
</script>
<?= $this->endSection() ?>
