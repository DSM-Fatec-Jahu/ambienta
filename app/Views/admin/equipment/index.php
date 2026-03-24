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
            <th class="w-24 text-right">Ações</th>
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

  <!-- ── Modal ──────────────────────────────────────────────────── -->
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

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function equipPage() {
  return {
    modalOpen: false,
    mode: 'create',
    editId: null,
    form: { name: '', code: '', description: '', quantity_total: 1, is_active: true },

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
    }
  }
}
</script>
<?= $this->endSection() ?>
