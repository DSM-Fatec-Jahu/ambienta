<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Ambientes</h1>
    <p class="page-subtitle">Salas, laboratórios e espaços disponíveis para reserva</p>
  </div>
  <button @click="$dispatch('open-room-modal', { mode: 'create' })" class="btn-primary">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
    </svg>
    Novo Ambiente
  </button>
</div>

<div class="card overflow-hidden" x-data="roomsPage()">

  <!-- ── Toolbar ──────────────────────────────────────────────────────── -->

  <div class="border-b border-slate-100 flex items-stretch">

    <!-- Lado esquerdo: busca + filtros (pode rolar em telas pequenas) -->
    <div class="flex items-center gap-3 flex-1 overflow-x-auto p-4 min-w-0">

      <!-- Busca: abordagem flex (ícone + input como irmãos) evita conflito de padding -->
      <label class="flex items-center gap-0 form-input p-0 pl-2 text-sm cursor-text"
        style="flex:1 1 320px; min-width:220px; overflow:hidden;">
        <span class="flex items-center justify-center pl-3 pr-2 text-slate-400 flex-shrink-0 self-stretch ml-4" style="margin-left: 15px;">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
          </svg>
        </span>
        <input type="text" placeholder="Buscar por nome, código ou prédio…" x-model="filters.q"
          @input.debounce.350ms="goTo(1)"
          class="flex-1 h-full text-sm bg-transparent border-0 outline-none shadow-none py-0 px-0 pr-3"
          style="box-shadow:none; border:none; outline:none">
      </label>

      <!-- Filtro prédio -->
      <select x-model="filters.building" @change="goTo(1)" class="form-input text-sm flex-shrink-0"
        style="min-width:170px; width: auto;">
        <option value="0">Todos os prédios</option>
        <?php foreach ($buildings as $b): ?>
          <option value="<?= $b['id'] ?>"><?= esc($b['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <!-- Filtro status -->
      <select x-model="filters.status" @change="goTo(1)" class="form-input text-sm flex-shrink-0 px-3 pr-8"
        style="min-width: 170px; width: auto;">
        <option value="0">Todos os status</option>
        <option value="1">Ativos</option>
        <option value="2">Inativos</option>
        <option value="3">Em manutenção</option>
      </select>

    </div>

    <!-- Separador vertical -->
    <div class="w-px bg-slate-100 self-stretch flex-shrink-0"></div>

    <!-- Lado direito: exports SEM overflow → dropdowns não são clipados -->
    <div class="flex items-center gap-2 p-4 flex-shrink-0">

      <!-- Excel dropdown -->
      <div class="relative" x-data="{ open: false }">
        <button @click="open = !open" @click.outside="open = false" style="min-width: 170px; width: auto;"
          class="btn-secondary text-sm flex justify-between items-center gap-1.5 whitespace-nowrap">
          <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 17v-6h6v6m-3-6V5m-7 16h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
          </svg>
          Excel
          <svg class="w-3 h-3 text-slate-400 transition-transform duration-150" :class="open && 'rotate-180'"
            fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100"
          x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
          class="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-slate-100 z-50 py-1">
          <a :href="exportUrl('exportar-xlsx', false)" @click="open=false"
            class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 4h18v2H3zM3 10h18M3 16h18M3 20h18" />
            </svg>
            Exportar filtrado
          </a>
          <a :href="exportUrl('exportar-xlsx', true)" @click="open=false"
            class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 6h16M4 10h16M4 14h16M4 18h16" />
            </svg>
            Exportar tudo
          </a>
        </div>
      </div>

      <!-- PDF dropdown -->
      <div class="relative" x-data="{ open: false }">
        <button @click="open = !open" @click.outside="open = false" style="min-width: 170px; width: auto;"
          class="btn-secondary text-sm flex justify-between items-center gap-1.5 whitespace-nowrap">
          <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
          </svg>
          PDF
          <svg class="w-3 h-3 text-slate-400 transition-transform duration-150" :class="open && 'rotate-180'"
            fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100"
          x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
          class="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-slate-100 z-50 py-1">
          <a :href="exportUrl('exportar-pdf', false)" @click="open=false"
            class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 4h18v2H3zM3 10h18M3 16h18M3 20h18" />
            </svg>
            Exportar filtrado
          </a>
          <a :href="exportUrl('exportar-pdf', true)" @click="open=false"
            class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 6h16M4 10h16M4 14h16M4 18h16" />
            </svg>
            Exportar tudo
          </a>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Tabela ──────────────────────────────────────────────────────── -->
  <div class="overflow-x-auto">
    <table class="table-base">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Código</th>
          <th>Prédio / Andar</th>
          <th class="text-center">Capacidade</th>
          <th class="text-center">Emp. equip.</th>
          <th class="text-center">Avaliação</th>
          <th>Status</th>
          <th class="w-24 text-right">Ações</th>
        </tr>
      </thead>
      <tbody>

        <template x-for="r in items" :key="r.id">
          <tr>
            <td class="font-medium text-slate-900" x-text="r.name"></td>
            <td>
              <template x-if="r.code">
                <span class="badge-primary" x-text="r.code"></span>
              </template>
              <template x-if="!r.code">
                <span class="text-slate-300">—</span>
              </template>
            </td>
            <td class="text-slate-600">
              <span x-text="r.building_name || '—'"></span>
              <template x-if="r.floor">
                <span class="text-slate-400 text-xs ml-1" x-text="'(' + r.floor + ')'"></span>
              </template>
            </td>
            <td class="text-center">
              <span x-text="r.capacity > 0 ? r.capacity + ' pessoas' : '—'"
                :class="r.capacity > 0 ? '' : 'text-slate-300'"></span>
            </td>
            <td class="text-center">
              <template x-if="r.allows_equipment_lending">
                <span class="text-success">Sim</span>
              </template>
              <template x-if="!r.allows_equipment_lending">
                <span class="text-slate-300">Não</span>
              </template>
            </td>
            <td class="text-center">
              <template x-if="r.avg_rating !== null">
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600">
                  <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                    <path
                      d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                  </svg>
                  <span x-text="r.avg_rating"></span>
                  <span class="text-slate-400 font-normal" x-text="'(' + r.total_ratings + ')'"></span>
                </span>
              </template>
              <template x-if="r.avg_rating === null">
                <span class="text-slate-300 text-xs">—</span>
              </template>
            </td>
            <td>
              <template x-if="r.is_active && !r.maintenance_mode">
                <span class="badge-approved badge-dot">Ativo</span>
              </template>
              <template x-if="!r.is_active">
                <span class="badge-cancelled">Inativo</span>
              </template>
              <template x-if="r.maintenance_mode">
                <span class="badge-warning"
                  x-text="'Manutenção' + (r.maintenance_until ? ' até ' + fmtDate(r.maintenance_until) : '')"></span>
              </template>
            </td>
            <td class="text-right">
              <div class="flex items-center justify-end gap-1">
                <button @click="$dispatch('open-room-resources', { roomId: r.id, roomName: r.name })"
                  class="btn-ghost p-2 text-sky-500 hover:bg-sky-50 hover:text-sky-600" title="Recursos do ambiente">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18" />
                  </svg>
                </button>
                <button @click="$dispatch('open-maintenance-modal', {
                          id: r.id, name: r.name,
                          maintenance_mode: r.maintenance_mode,
                          maintenance_until: r.maintenance_until || '',
                          maintenance_reason: r.maintenance_reason || ''
                        })" class="btn-ghost p-2 text-orange-500 hover:bg-orange-50 hover:text-orange-600"
                  title="Modo manutenção">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </button>
                <button @click="$dispatch('open-room-modal', {
                          mode: 'edit',
                          id: r.id, name: r.name, code: r.code || '',
                          building_id: r.building_id || 0,
                          capacity: r.capacity, floor: r.floor || '',
                          description: r.description || '',
                          allows_equipment_lending: r.allows_equipment_lending,
                          is_active: r.is_active
                        })" class="btn-ghost p-2 text-yellow-500 hover:bg-yellow-50 hover:text-yellow-600"
                  title="Editar">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                         m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button @click="confirmDelete(r.id, r.name)"
                  class="btn-ghost p-2 text-red-500 hover:bg-red-50 hover:text-red-600" title="Excluir">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6
                         m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
              <td>
                <div class="h-4 bg-slate-100 rounded w-40"></div>
              </td>
              <td>
                <div class="h-4 bg-slate-100 rounded w-14"></div>
              </td>
              <td>
                <div class="h-4 bg-slate-100 rounded w-32"></div>
              </td>
              <td>
                <div class="h-4 bg-slate-100 rounded w-16 mx-auto"></div>
              </td>
              <td>
                <div class="h-4 bg-slate-100 rounded w-10 mx-auto"></div>
              </td>
              <td>
                <div class="h-4 bg-slate-100 rounded w-10 mx-auto"></div>
              </td>
              <td>
                <div class="h-4 bg-slate-100 rounded w-16"></div>
              </td>
              <td></td>
            </tr>
          </template>
        </template>

        <!-- Vazio -->
        <template x-if="!loading && items.length === 0">
          <tr>
            <td colspan="8">
              <div class="empty-state py-12">
                <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <p class="empty-state-title"
                  x-text="hasFilters() ? 'Nenhum resultado encontrado' : 'Nenhum ambiente cadastrado'"></p>
                <p class="empty-state-description"
                  x-text="hasFilters() ? 'Tente ajustar os filtros ou o termo de busca.' : 'Adicione o primeiro ambiente.'">
                </p>
                <template x-if="!hasFilters()">
                  <button @click="$dispatch('open-room-modal', { mode: 'create' })" class="btn-primary mt-4">
                    Cadastrar Ambiente
                  </button>
                </template>
              </div>
            </td>
          </tr>
        </template>

      </tbody>
    </table>
  </div>

  <!-- ── Footer: per-page + paginação numérica ───────────────────────── -->
  <div class="card-footer flex flex-wrap items-center justify-between gap-4 px-4 py-3">

    <!-- Lado esquerdo: por página + contador -->
    <div class="flex items-center gap-2 text-sm text-slate-500">
      <label for="perPageSelect" class="whitespace-nowrap">Exibir</label>
      <!-- min-width fixo garante espaço para o número + seta nativa -->
      <select id="perPageSelect" x-model.number="perPage" @change="goTo(1)" class="form-input text-sm"
        style="width:5rem; padding-right:2rem">
        <option value="10">10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
      <span class="whitespace-nowrap">por página</span>
      <span class="text-slate-300 hidden sm:inline">|</span>
      <span class="text-slate-400 hidden sm:inline whitespace-nowrap" x-show="total > 0" x-cloak
        x-text="rangeText()"></span>
    </div>

    <!-- Lado direito: controles de página -->
    <nav x-show="pages > 1" x-cloak class="flex items-center gap-0.5" aria-label="Paginação">

      <!-- Primeira -->
      <button @click="goTo(1)" :disabled="page === 1" class="pg-btn" title="Primeira página">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
        </svg>
      </button>

      <!-- Anterior -->
      <button @click="goTo(page - 1)" :disabled="page === 1" class="pg-btn" title="Página anterior">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <!-- Números: usa um único <button> por item para evitar problema de template aninhado no Alpine -->
      <template x-for="(p, i) in visiblePages()" :key="i">
        <button x-text="p" :disabled="p === page || p === '…'" @click="typeof p === 'number' && goTo(p)" :class="p === page
            ? 'pg-btn-active'
            : p === '…'
              ? 'pg-ellipsis'
              : 'pg-btn'">
        </button>
      </template>

      <!-- Próxima -->
      <button @click="goTo(page + 1)" :disabled="page === pages" class="pg-btn" title="Próxima página">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>

      <!-- Última -->
      <button @click="goTo(pages)" :disabled="page === pages" class="pg-btn" title="Última página">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
        </svg>
      </button>

    </nav>
  </div>

  <!-- Formulário oculto para exclusão via POST -->
  <form x-ref="deleteForm" method="POST" style="display:none">
    <?= csrf_field() ?>
  </form>

  <!-- ── Maintenance Modal ──────────────────────────────────────────── -->
  <div x-show="maintModalOpen" class="modal-overlay" x-cloak
    @open-maintenance-modal.window="openMaintModal($event.detail)" x-transition:enter="transition-opacity duration-200"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity duration-150" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0">
    <div class="modal-panel max-w-lg" @click.stop x-transition:enter="transition duration-200"
      x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
      <div class="modal-header">
        <h3 class="text-sm font-semibold text-slate-900">
          Modo Manutenção — <span x-text="maintForm.name"></span>
        </h3>
        <button @click="maintModalOpen = false" class="btn-ghost btn-sm p-1" aria-label="Fechar">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      <form :action="`<?= base_url('admin/ambientes/') ?>${maintForm.id}/manutencao`" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body space-y-4">
          <div class="flex items-center gap-3">
            <input type="hidden" name="maintenance_mode" :value="maintForm.mode ? '1' : '0'">
            <input type="checkbox" id="maint_mode" x-model="maintForm.mode"
              class="rounded border-slate-300 text-orange-500">
            <label for="maint_mode" class="text-sm text-slate-700 font-medium">Ativar modo manutenção</label>
          </div>
          <div x-show="maintForm.mode" x-cloak class="space-y-4">
            <div>
              <label for="maint_until" class="form-label">Data de término (opcional)</label>
              <input type="date" id="maint_until" name="maintenance_until" x-model="maintForm.until" class="form-input">
              <p class="form-hint">Deixe em branco se a duração for indefinida.</p>
            </div>
            <div>
              <label for="maint_reason" class="form-label">Motivo (opcional)</label>
              <textarea id="maint_reason" name="maintenance_reason" rows="2" x-model="maintForm.reason"
                class="form-input resize-none"
                placeholder="Ex: Manutenção elétrica, reparo no ar-condicionado..."></textarea>
            </div>
          </div>
          <div x-show="!maintForm.mode" x-cloak>
            <input type="hidden" name="maintenance_until" value="">
            <input type="hidden" name="maintenance_reason" value="">
            <p class="text-sm text-slate-500">
              O ambiente está <span class="font-medium text-emerald-600">disponível para reservas</span>.
              Ative o modo manutenção para bloquear novas reservas.
            </p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" @click="maintModalOpen = false" class="btn-secondary">Cancelar</button>
          <button type="submit" :class="maintForm.mode ? 'btn-danger' : 'btn-primary'"
            x-text="maintForm.mode ? 'Colocar em manutenção' : 'Retirar de manutenção'"></button>
        </div>
      </form>
    </div>
  </div>

  <!-- ── Room Modal ──────────────────────────────────────────────────── -->
  <div x-show="modalOpen" class="modal-overlay" x-cloak @open-room-modal.window="openModal($event.detail)"
    x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-150"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div class="modal-panel max-w-2xl" @click.stop x-transition:enter="transition duration-200"
      x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
      <div class="modal-header">
        <h3 class="text-sm font-semibold text-slate-900"
          x-text="mode === 'create' ? 'Novo Ambiente' : 'Editar Ambiente'"></h3>
        <button @click="modalOpen = false" class="btn-ghost btn-sm p-1" aria-label="Fechar">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      <form
        :action="mode === 'create' ? '<?= base_url('admin/ambientes') ?>' : `<?= base_url('admin/ambientes/') ?>${editId}/update`"
        method="POST">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label for="r_name" class="form-label form-label-required">Nome do ambiente</label>
              <input type="text" id="r_name" name="name" x-model="form.name" class="form-input"
                placeholder="Ex: Sala de Reuniões 01" maxlength="200" required>
            </div>
            <div>
              <label for="r_code" class="form-label">Código / Sigla</label>
              <input type="text" id="r_code" name="code" x-model="form.code" class="form-input" placeholder="Ex: SR-01"
                maxlength="20">
            </div>
            <div>
              <label for="r_building" class="form-label">Prédio</label>
              <select id="r_building" name="building_id" x-model="form.building_id" class="form-input">
                <option value="">— Selecione —</option>
                <?php foreach ($buildings as $b): ?>
                  <option value="<?= $b['id'] ?>"><?= esc($b['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="r_capacity" class="form-label">Capacidade (pessoas)</label>
              <input type="number" id="r_capacity" name="capacity" x-model="form.capacity" class="form-input" min="0"
                max="9999" placeholder="0">
            </div>
            <div>
              <label for="r_floor" class="form-label">Andar / Localização</label>
              <input type="text" id="r_floor" name="floor" x-model="form.floor" class="form-input"
                placeholder="Ex: Térreo, 1º andar" maxlength="20">
            </div>
            <div class="sm:col-span-2">
              <label for="r_desc" class="form-label">Descrição</label>
              <textarea id="r_desc" name="description" x-model="form.description" rows="2"
                class="form-input resize-none" placeholder="Recursos, observações..."></textarea>
            </div>
            <div class="flex items-center gap-3">
              <input type="hidden" name="allows_equipment_lending" value="0">
              <input type="checkbox" id="r_equip" name="allows_equipment_lending" value="1"
                x-model="form.allows_equipment_lending" class="rounded border-slate-300 text-primary">
              <label for="r_equip" class="text-sm text-slate-700">Permite empréstimo de recursos</label>
            </div>
            <div class="flex items-center gap-3">
              <input type="hidden" name="is_active" value="0">
              <input type="checkbox" id="r_active" name="is_active" value="1" x-model="form.is_active"
                class="rounded border-slate-300 text-primary">
              <label for="r_active" class="text-sm text-slate-700">Ambiente ativo</label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" @click="modalOpen = false" class="btn-secondary">Cancelar</button>
          <button type="submit" class="btn-primary" x-text="mode === 'create' ? 'Cadastrar' : 'Salvar'"></button>
        </div>
      </form>
    </div>
  </div>

</div>

<?= view('admin/resources/partials/room_resources') ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
  /* ── Botões de paginação ────────────────────────────────────── */
  .pg-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    height: 2rem;
    padding: 0 0.4rem;
    font-size: 0.8125rem;
    font-weight: 500;
    border-radius: 0.375rem;
    color: #475569;
    background: transparent;
    border: 1px solid transparent;
    transition: background 120ms, color 120ms, border-color 120ms;
    cursor: pointer;
    user-select: none;
  }

  .pg-btn:hover:not(:disabled) {
    background: #f1f5f9;
    color: #0f172a;
    border-color: #e2e8f0;
  }

  .pg-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
  }

  .pg-btn-active {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    height: 2rem;
    padding: 0 0.4rem;
    font-size: 0.8125rem;
    font-weight: 600;
    border-radius: 0.375rem;
    color: #fff;
    background: var(--color-primary, #3b82f6);
    border: 1px solid transparent;
    cursor: default;
    user-select: none;
  }

  .pg-ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.5rem;
    height: 2rem;
    padding: 0 0.25rem;
    font-size: 0.875rem;
    color: #94a3b8;
    cursor: default;
    user-select: none;
    pointer-events: none;
  }
</style>
<script>
  function roomsPage() {
    return {
      items: [],
      total: 0,
      page: 1,
      pages: 1,
      perPage: 10,
      loading: false,

      filters: { q: '', building: '0', status: '0' },

      modalOpen: false, mode: 'create', editId: null,
      form: {
        name: '', code: '', building_id: '', capacity: '', floor: '',
        description: '', allows_equipment_lending: false, is_active: true
      },
      maintModalOpen: false,
      maintForm: { id: null, name: '', mode: false, until: '', reason: '' },

      init() { this.fetchPage(); },

      async fetchPage() {
        this.loading = true;
        this.items = [];

        const params = new URLSearchParams({
          page: this.page,
          limit: this.perPage,
          q: this.filters.q,
          building: this.filters.building,
          status: this.filters.status,
        });

        try {
          const res = await fetch(`<?= base_url('admin/ambientes/data') ?>?${params}`);
          const json = await res.json();
          this.items = json.data;
          this.total = json.total;
          this.pages = json.pages;
          // corrige se página atual excede total
          if (this.page > this.pages && this.pages > 0) {
            this.page = this.pages;
            return this.fetchPage();
          }
        } catch (e) {
          console.error('Erro ao carregar ambientes:', e);
        } finally {
          this.loading = false;
        }
      },

      goTo(n) {
        const p = Math.max(1, Math.min(n, this.pages || 1));
        this.page = p;
        this.fetchPage();
      },

      exportUrl(action, all = false) {
        if (all) return `<?= base_url('admin/ambientes/') ?>${action}`;
        const p = new URLSearchParams({
          q: this.filters.q, building: this.filters.building, status: this.filters.status
        });
        return `<?= base_url('admin/ambientes/') ?>${action}?${p}`;
      },

      confirmDelete(id, name) {
        if (!confirm(`Excluir o ambiente «${name}»?`)) return;
        const f = this.$refs.deleteForm;
        f.action = `<?= base_url('admin/ambientes/') ?>${id}/delete`;
        f.submit();
      },

      /* Retorna array de números e '…' para o nav de páginas */
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

      hasFilters() {
        return this.filters.q !== '' || this.filters.building !== '0' || this.filters.status !== '0';
      },

      rangeText() {
        if (!this.total) return '';
        const from = (this.page - 1) * this.perPage + 1;
        const to = Math.min(this.page * this.perPage, this.total);
        return `${from}–${to} de ${this.total} ambiente${this.total !== 1 ? 's' : ''}`;
      },

      fmtDate(iso) {
        if (!iso) return '';
        const [, m, d] = iso.split('-');
        return `${d}/${m}`;
      },

      openModal(detail) {
        this.mode = detail.mode;
        if (detail.mode === 'edit') {
          this.editId = detail.id;
          this.form = {
            name: detail.name, code: detail.code,
            building_id: detail.building_id || '', capacity: detail.capacity || '',
            floor: detail.floor, description: detail.description,
            allows_equipment_lending: detail.allows_equipment_lending,
            is_active: detail.is_active,
          };
        } else {
          this.editId = null;
          this.form = {
            name: '', code: '', building_id: '', capacity: '', floor: '',
            description: '', allows_equipment_lending: false, is_active: true
          };
        }
        this.modalOpen = true;
      },

      openMaintModal(detail) {
        this.maintForm = {
          id: detail.id, name: detail.name,
          mode: detail.maintenance_mode,
          until: detail.maintenance_until || '',
          reason: detail.maintenance_reason || '',
        };
        this.maintModalOpen = true;
      },
    }
  }
</script>
<?= $this->endSection() ?>