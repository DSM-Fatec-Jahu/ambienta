<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Recursos</h1>
    <p class="page-subtitle">Equipamentos, dispositivos e demais itens gerenciados pela instituição</p>
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

  <?php if (empty($items)): ?>
    <div class="empty-state">
      <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
      </svg>
      <p class="empty-state-title">Nenhum recurso cadastrado</p>
      <p class="empty-state-description">Adicione recursos para que possam ser solicitados nas reservas.</p>
      <button @click="$dispatch('open-resource-modal', { mode: 'create' })" class="btn-primary mt-4">
        Cadastrar Recurso
      </button>
    </div>
  <?php else: ?>
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
          <?php foreach ($items as $r): ?>
          <tr>
            <td class="font-medium text-slate-900"><?= esc($r['name']) ?></td>
            <td class="text-slate-500"><?= esc($r['category'] ?? '—') ?></td>
            <td>
              <?php if ($r['code']): ?>
                <span class="badge-primary"><?= esc($r['code']) ?></span>
              <?php else: ?>
                <span class="text-slate-300">—</span>
              <?php endif; ?>
            </td>
            <td class="text-center font-semibold text-slate-700"><?= esc($r['quantity_total']) ?></td>
            <td>
              <?php if (!empty($r['current_room_name'])): ?>
                <span class="inline-flex items-center gap-1 text-sm text-slate-700">
                  <?= esc($r['current_room_name']) ?>
                  <?php if (!empty($r['current_room_abbr'])): ?>
                    <span class="text-xs text-slate-400">(<?= esc($r['current_room_abbr']) ?>)</span>
                  <?php endif; ?>
                </span>
              <?php else: ?>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                             bg-sky-50 text-sky-700 border border-sky-200">
                  Estoque geral
                </span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($r['is_active']): ?>
                <span class="badge-approved badge-dot">Ativo</span>
              <?php else: ?>
                <span class="badge-cancelled">Inativo</span>
              <?php endif; ?>
            </td>
            <td class="text-right">
              <div class="flex items-center justify-end gap-1">
                <!-- Movement history -->
                <button
                  @click="openHistory(<?= (int) $r['id'] ?>, <?= htmlspecialchars(json_encode($r['name']), ENT_QUOTES) ?>)"
                  class="btn-ghost p-2 text-indigo-500 hover:bg-indigo-50 hover:text-indigo-600"
                  title="Histórico de movimentações">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0"/>
                  </svg>
                </button>
                <!-- Edit -->
                <button
                  @click="$dispatch('open-resource-modal', <?= htmlspecialchars(json_encode([
                    'mode'           => 'edit',
                    'id'             => (int) $r['id'],
                    'name'           => $r['name'],
                    'category'       => $r['category'] ?? '',
                    'code'           => $r['code'] ?? '',
                    'description'    => $r['description'] ?? '',
                    'quantity_total' => (int) $r['quantity_total'],
                    'is_active'      => (bool) $r['is_active'],
                  ]), ENT_QUOTES) ?>)"
                  class="btn-ghost p-2 text-yellow-500 hover:bg-yellow-50 hover:text-yellow-600"
                  title="Editar">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                         m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </button>
                <!-- Delete -->
                <form method="POST"
                      action="<?= base_url('admin/recursos/' . $r['id'] . '/delete') ?>"
                      @submit.prevent="if(confirm('Excluir o recurso «<?= esc(addslashes($r['name'])) ?>»? Esta ação não pode ser desfeita se não houver movimentações.')) $el.submit()">
                  <?= csrf_field() ?>
                  <button type="submit"
                          class="btn-ghost p-2 text-red-500 hover:bg-red-50 hover:text-red-600"
                          title="Excluir">
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
      <?= count($items) ?> recurso<?= count($items) !== 1 ? 's' : '' ?> cadastrado<?= count($items) !== 1 ? 's' : '' ?>
    </div>
  <?php endif; ?>

  <!-- ── Create/Edit Modal ──────────────────────────────────────────── -->
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

          <!-- Nome -->
          <div>
            <label for="r_name" class="form-label form-label-required">Nome</label>
            <input type="text" id="r_name" name="name" x-model="form.name"
                   class="form-input" placeholder="Ex: Projetor Epson EB-X51" maxlength="150" required>
          </div>

          <!-- Categoria -->
          <div>
            <label for="r_category" class="form-label">Categoria</label>
            <input type="text" id="r_category" name="category" x-model="form.category"
                   class="form-input" placeholder="Ex: Audiovisual, Informática..." maxlength="80">
          </div>

          <!-- Patrimônio + Quantidade -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="r_code" class="form-label">Nº de Patrimônio</label>
              <input type="text" id="r_code" name="code" x-model="form.code"
                     @input="onCodeInput()"
                     class="form-input" placeholder="Ex: PRJ-001" maxlength="50">
              <!-- Badge informativo quando patrimônio preenchido -->
              <p x-show="form.code && form.code.trim() !== ''"
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
                     :disabled="form.code && form.code.trim() !== ''"
                     :class="form.code && form.code.trim() !== '' ? 'form-input opacity-50 cursor-not-allowed bg-slate-100' : 'form-input'"
                     min="1" max="9999" required>
            </div>
          </div>

          <!-- Descrição -->
          <div>
            <label for="r_desc" class="form-label">Descrição</label>
            <textarea id="r_desc" name="description" x-model="form.description"
                      rows="2" class="form-input resize-none"
                      placeholder="Modelo, características técnicas..."></textarea>
          </div>

          <!-- Status -->
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

  <!-- ── History Modal ─────────────────────────────────────────────── -->
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

  <!-- ── Import Modal (XLSX) ──────────────────────────────────────── -->
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

        <!-- Result feedback -->
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

</div><!-- /.card x-data="resourcePage()" -->

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function resourcePage() {
  return {
    // create/edit modal
    modalOpen: false,
    mode: 'create',
    editId: null,
    form: { name: '', category: '', code: '', description: '', quantity_total: 1, is_active: true },

    // history modal
    historyOpen: false,
    historyName: '',
    historyLoading: false,
    historyRows: [],

    // import modal
    importOpen: false,
    importFile: null,
    importing: false,
    importResult: null,

    // ── Modal open ──────────────────────────────────────────────────────────

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

    // ── RN-R01: lock quantity when patrimônio is filled ─────────────────────

    onCodeInput() {
      if (this.form.code && this.form.code.trim() !== '') {
        this.form.quantity_total = 1;
      }
    },

    // ── History ─────────────────────────────────────────────────────────────

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

    // ── Import ───────────────────────────────────────────────────────────────

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
  };
}
</script>
<?= $this->endSection() ?>
