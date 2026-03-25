<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Equipamentos</h1>
    <p class="page-subtitle">Projetores, câmeras e demais recursos disponíveis para empréstimo</p>
  </div>
  <button @click="$dispatch('open-equip-modal', { mode: 'create' })" class="btn-primary">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    Novo Equipamento
  </button>
</div>

<div class="card overflow-hidden" x-data="equipPage()">

  <?php if (empty($items)): ?>
    <div class="empty-state">
      <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
      </svg>
      <p class="empty-state-title">Nenhum equipamento cadastrado</p>
      <p class="empty-state-description">Adicione equipamentos para que possam ser solicitados nas reservas.</p>
      <button @click="$dispatch('open-equip-modal', { mode: 'create' })" class="btn-primary mt-4">
        Cadastrar Equipamento
      </button>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="table-base">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Código</th>
            <th class="text-center">Qtd. total</th>
            <th>Descrição</th>
            <th>Status</th>
            <th class="w-36 text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $e): ?>
          <tr>
            <td class="font-medium text-slate-900"><?= esc($e['name']) ?></td>
            <td>
              <?php if ($e['code']): ?>
                <span class="badge-primary"><?= esc($e['code']) ?></span>
              <?php else: ?>
                <span class="text-slate-300">—</span>
              <?php endif; ?>
            </td>
            <td class="text-center font-semibold text-slate-700"><?= esc($e['quantity_total']) ?></td>
            <td class="text-slate-500 max-w-xs truncate"><?= esc($e['description'] ?? '—') ?></td>
            <td>
              <?php if ($e['is_active']): ?>
                <span class="badge-approved badge-dot">Ativo</span>
              <?php else: ?>
                <span class="badge-cancelled">Inativo</span>
              <?php endif; ?>
            </td>
            <td class="text-right">
              <div class="flex items-center justify-end gap-1">
                <!-- Transfer history -->
                <button
                  @click="openHistory(<?= (int) $e['id'] ?>, <?= htmlspecialchars(json_encode($e['name']), ENT_QUOTES) ?>)"
                  class="btn-ghost p-2 text-indigo-500 hover:bg-indigo-50 hover:text-indigo-600"
                  aria-label="Histórico de movimentações">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0"/>
                  </svg>
                </button>
                <!-- Register transfer -->
                <button
                  @click="$dispatch('open-transfer-modal', { id: <?= (int) $e['id'] ?>, name: <?= htmlspecialchars(json_encode($e['name']), ENT_QUOTES) ?> })"
                  class="btn-ghost p-2 text-teal-500 hover:bg-teal-50 hover:text-teal-600"
                  aria-label="Registrar movimentação">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                  </svg>
                </button>
                <!-- Edit -->
                <button
                  @click="$dispatch('open-equip-modal', <?= htmlspecialchars(json_encode([
                    'mode'           => 'edit',
                    'id'             => (int) $e['id'],
                    'name'           => $e['name'],
                    'code'           => $e['code'] ?? '',
                    'description'    => $e['description'] ?? '',
                    'quantity_total' => (int) $e['quantity_total'],
                    'is_active'      => (bool) $e['is_active'],
                  ]), ENT_QUOTES) ?>)"
                  class="btn-ghost p-2 text-yellow-500 hover:bg-yellow-50 hover:text-yellow-600" aria-label="Editar">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                         m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </button>
                <!-- Delete -->
                <form method="POST" action="<?= base_url('admin/equipamentos/' . $e['id'] . '/delete') ?>"
                      @submit.prevent="if(confirm('Excluir o equipamento «<?= esc($e['name']) ?>»?')) $el.submit()">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn-ghost p-2 text-red-500 hover:bg-red-50 hover:text-red-600"
                          aria-label="Excluir">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6
                           m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer text-xs text-slate-400">
      <?= count($items) ?> equipamento<?= count($items) !== 1 ? 's' : '' ?> cadastrado<?= count($items) !== 1 ? 's' : '' ?>
    </div>
  <?php endif; ?>

  <!-- ── Create/Edit Modal ──────────────────────────────────────────── -->
  <div x-show="modalOpen" class="modal-overlay" x-cloak
       @open-equip-modal.window="openModal($event.detail)"
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
            x-text="mode === 'create' ? 'Novo Equipamento' : 'Editar Equipamento'"></h3>
        <button @click="modalOpen = false" class="btn-ghost btn-sm p-1" aria-label="Fechar">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <form :action="mode === 'create' ? '<?= base_url('admin/equipamentos') ?>' : `<?= base_url('admin/equipamentos/') ?>${editId}/update`"
            method="POST">
        <?= csrf_field() ?>

        <div class="modal-body space-y-4">
          <div>
            <label for="e_name" class="form-label form-label-required">Nome</label>
            <input type="text" id="e_name" name="name" x-model="form.name"
                   class="form-input" placeholder="Ex: Projetor Epson" maxlength="200" required>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="e_code" class="form-label">Código / Patrimônio</label>
              <input type="text" id="e_code" name="code" x-model="form.code"
                     class="form-input" placeholder="Ex: PRJ-001" maxlength="20">
            </div>
            <div>
              <label for="e_qty" class="form-label form-label-required">Quantidade total</label>
              <input type="number" id="e_qty" name="quantity_total" x-model="form.quantity_total"
                     class="form-input" min="1" max="9999" required>
            </div>
          </div>
          <div>
            <label for="e_desc" class="form-label">Descrição</label>
            <textarea id="e_desc" name="description" x-model="form.description"
                      rows="2" class="form-input resize-none"
                      placeholder="Modelo, características..."></textarea>
          </div>
          <div class="flex items-center gap-3">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="e_active" name="is_active" value="1"
                   x-model="form.is_active" class="rounded border-slate-300 text-primary">
            <label for="e_active" class="text-sm text-slate-700">Equipamento ativo</label>
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

  <!-- ── Transfer Modal ────────────────────────────────────────────── -->
  <div x-show="transferOpen" class="modal-overlay" x-cloak
       @open-transfer-modal.window="openTransfer($event.detail)"
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
        <h3 class="text-sm font-semibold text-slate-900">
          Registrar Movimentação — <span class="text-primary" x-text="transferName"></span>
        </h3>
        <button @click="transferOpen = false" class="btn-ghost btn-sm p-1" aria-label="Fechar">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <form :action="`<?= base_url('admin/equipamentos/') ?>${transferId}/transferir`" method="POST">
        <?= csrf_field() ?>

        <div class="modal-body space-y-4">
          <p class="text-xs text-slate-500">
            Registre o movimento físico deste equipamento entre salas. Informe ao menos a sala de origem
            ou a sala de destino.
          </p>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="t_origin" class="form-label">Sala de origem</label>
              <select id="t_origin" name="origin_room_id" class="form-input">
                <option value="">— Nenhuma (entrada externa) —</option>
                <?php foreach ($rooms as $r): ?>
                  <option value="<?= $r['id'] ?>">
                    <?= esc($r['name']) ?><?= !empty($r['code']) ? ' (' . esc($r['code']) . ')' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="t_dest" class="form-label">Sala de destino</label>
              <select id="t_dest" name="destination_room_id" class="form-input">
                <option value="">— Nenhuma (saída do sistema) —</option>
                <?php foreach ($rooms as $r): ?>
                  <option value="<?= $r['id'] ?>">
                    <?= esc($r['name']) ?><?= !empty($r['code']) ? ' (' . esc($r['code']) . ')' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div>
            <label for="t_qty" class="form-label form-label-required">Quantidade</label>
            <input type="number" id="t_qty" name="quantity" value="1"
                   class="form-input" min="1" max="9999" required>
          </div>

          <div>
            <label for="t_notes" class="form-label">Observação</label>
            <textarea id="t_notes" name="notes" rows="2"
                      class="form-input resize-none"
                      placeholder="Motivo, condição do equipamento, número de patrimônio..."></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" @click="transferOpen = false" class="btn-secondary">Cancelar</button>
          <button type="submit" class="btn-primary">Registrar Movimentação</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ── History Modal ─────────────────────────────────────────────── -->
  <div x-show="historyOpen" class="modal-overlay" x-cloak
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
            Nenhuma movimentação registrada para este equipamento.
          </div>
        </template>

        <template x-if="!historyLoading && historyRows.length > 0">
          <div class="overflow-x-auto">
            <table class="table-base">
              <thead>
                <tr>
                  <th>Data</th>
                  <th>Qtd.</th>
                  <th>Origem</th>
                  <th>Destino</th>
                  <th>Responsável</th>
                  <th>Observação</th>
                </tr>
              </thead>
              <tbody>
                <template x-for="row in historyRows" :key="row.id">
                  <tr>
                    <td class="whitespace-nowrap text-xs" x-text="formatDate(row.transferred_at)"></td>
                    <td class="text-center font-semibold" x-text="row.quantity"></td>
                    <td class="text-sm">
                      <span x-show="row.origin_room_name" x-text="row.origin_room_name + (row.origin_room_code ? ' (' + row.origin_room_code + ')' : '')"></span>
                      <span x-show="!row.origin_room_name" class="text-slate-400 italic">Externo</span>
                    </td>
                    <td class="text-sm">
                      <span x-show="row.destination_room_name" x-text="row.destination_room_name + (row.destination_room_code ? ' (' + row.destination_room_code + ')' : '')"></span>
                      <span x-show="!row.destination_room_name" class="text-slate-400 italic">Saída</span>
                    </td>
                    <td class="text-sm" x-text="row.handler_name || '—'"></td>
                    <td class="text-xs text-slate-500 max-w-xs truncate" x-text="row.notes || '—'"></td>
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

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function equipPage() {
  return {
    // create/edit modal
    modalOpen: false,
    mode: 'create',
    editId: null,
    form: { name: '', code: '', description: '', quantity_total: 1, is_active: true },

    // transfer modal
    transferOpen: false,
    transferId: null,
    transferName: '',

    // history modal
    historyOpen: false,
    historyName: '',
    historyLoading: false,
    historyRows: [],

    openModal(detail) {
      this.mode = detail.mode;
      if (detail.mode === 'edit') {
        this.editId = detail.id;
        this.form = {
          name:           detail.name,
          code:           detail.code,
          description:    detail.description,
          quantity_total: detail.quantity_total,
          is_active:      detail.is_active,
        };
      } else {
        this.editId = null;
        this.form = { name: '', code: '', description: '', quantity_total: 1, is_active: true };
      }
      this.modalOpen = true;
    },

    openTransfer(detail) {
      this.transferId   = detail.id;
      this.transferName = detail.name;
      this.transferOpen = true;
    },

    async openHistory(id, name) {
      this.historyName    = name;
      this.historyOpen    = true;
      this.historyLoading = true;
      this.historyRows    = [];

      try {
        const res  = await fetch(`<?= base_url('admin/equipamentos/') ?>${id}/historico`);
        const data = await res.json();
        this.historyRows = data.history || [];
      } catch (e) {
        this.historyRows = [];
      } finally {
        this.historyLoading = false;
      }
    },

    formatDate(dt) {
      if (!dt) return '—';
      const d = new Date(dt.replace(' ', 'T'));
      return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    },
  }
}
</script>
<?= $this->endSection() ?>
