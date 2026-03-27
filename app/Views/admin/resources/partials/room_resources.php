<?php
/**
 * Partial: admin/resources/partials/room_resources.php
 * Included by: admin/rooms/index.php
 *
 * Self-contained Alpine.js component for the "Room Resources" modal.
 * Opens via custom event: $dispatch('open-room-resources', { roomId, roomName })
 *
 * Endpoints (AJAX, CSRF via X-CSRF-TOKEN header):
 *   GET  /admin/ambientes/:id/recursos             → RoomResourceController::index
 *   POST /admin/ambientes/:id/recursos             → RoomResourceController::store
 *   POST /admin/ambientes/:id/recursos/:rid/delete → RoomResourceController::destroy
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

    <div class="modal-panel max-w-2xl" @click.stop
         x-transition:enter="transition duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">

      <div class="modal-header">
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

      <div class="modal-body space-y-4">

        <!-- Add resource form -->
        <div class="flex items-end gap-2">
          <div class="flex-1">
            <label class="form-label">Recurso (estoque geral)</label>
            <select x-model="newResourceId" class="form-input">
              <option value="">— Selecione —</option>
              <template x-for="r in availableStock" :key="r.id">
                <option :value="r.id"
                        x-text="r.name + (r.code ? ' (' + r.code + ')' : '') + ' — Qtd: ' + r.quantity_total">
                </option>
              </template>
            </select>
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

        <!-- Loading -->
        <template x-if="loading">
          <div class="text-sm text-slate-400 text-center py-6">Carregando...</div>
        </template>

        <!-- Empty state -->
        <template x-if="!loading && items.length === 0">
          <div class="text-sm text-slate-400 text-center py-6">
            Nenhum recurso alocado permanentemente neste ambiente.
          </div>
        </template>

        <!-- Resources list -->
        <template x-if="!loading && items.length > 0">
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
              </tbody>
            </table>
          </div>
        </template>

        <!-- Error message -->
        <template x-if="errorMsg">
          <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700"
               x-text="errorMsg"></div>
        </template>

      </div>

      <div class="modal-footer">
        <button @click="isOpen = false" class="btn-secondary">Fechar</button>
      </div>
    </div>
  </div>

  <!-- ── RN-R10: Confirm Deallocation Modal ────────────────────────────────── -->
  <div x-show="confirmOpen" class="modal-overlay" x-cloak
       style="z-index: 60;"
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
    items:           [],
    availableStock:  [],
    loading:         false,
    saving:          false,
    newResourceId:   '',
    newQuantity:     1,
    pendingRemoveId: null,
    futureBookings:  [],
    errorMsg:        '',

    async open(detail) {
      this.roomId        = detail.roomId;
      this.roomName      = detail.roomName;
      this.newResourceId = '';
      this.newQuantity   = 1;
      this.errorMsg      = '';
      this.isOpen        = true;
      await this.load();
    },

    async load() {
      this.loading  = true;
      this.errorMsg = '';
      try {
        const res  = await fetch(`<?= base_url('admin/ambientes/') ?>${this.roomId}/recursos`);
        const data = await res.json();
        this.items          = data.items           || [];
        this.availableStock = data.available_stock || [];
      } catch {
        this.errorMsg = 'Erro ao carregar recursos.';
      } finally {
        this.loading = false;
      }
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
          this.newResourceId = '';
          this.newQuantity   = 1;
          await this.load();
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
          await this.load();
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
