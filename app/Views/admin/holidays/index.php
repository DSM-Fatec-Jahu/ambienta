<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Feriados</h1>
    <p class="page-subtitle">Datas em que reservas ficam bloqueadas automaticamente</p>
  </div>
  <div class="flex items-center gap-2" x-data="holidaysImport()">
    <button @click="importApi()" :disabled="loading"
            class="btn-secondary flex items-center gap-2"
            title="Importar feriados nacionais do ano atual via BrasilAPI">
      <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
      </svg>
      <span x-text="loading ? 'Importando...' : 'Importar Nacionais ' + year"></span>
    </button>
    <button @click="$dispatch('open-holiday-modal', { mode: 'create' })" class="btn-primary">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Novo Feriado
    </button>
  </div>
</div>

<div class="card overflow-hidden" x-data="holidaysPage()">

  <?php if (empty($items)): ?>
    <div class="empty-state">
      <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      <p class="empty-state-title">Nenhum feriado cadastrado</p>
      <p class="empty-state-description">Adicione feriados para bloquear reservas automaticamente nessas datas.</p>
      <button @click="$dispatch('open-holiday-modal', { mode: 'create' })" class="btn-primary mt-4">
        Cadastrar Feriado
      </button>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="table-base">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Data</th>
            <th class="text-center">Recorrente</th>
            <th class="w-24 text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $h):
            $dateFormatted = date('d/m/Y', strtotime($h['date']));
            $dayOfWeek     = date('l', strtotime($h['date']));
            $daysPtBr      = ['Sunday'=>'Dom','Monday'=>'Seg','Tuesday'=>'Ter','Wednesday'=>'Qua',
                              'Thursday'=>'Qui','Friday'=>'Sex','Saturday'=>'Sáb'];
            $upcoming = $h['date'] >= date('Y-m-d');
          ?>
          <tr>
            <td class="font-medium text-slate-900"><?= esc($h['name']) ?></td>
            <td>
              <span class="text-slate-700"><?= $dateFormatted ?></span>
              <span class="text-xs text-slate-400 ml-1"><?= $daysPtBr[$dayOfWeek] ?? '' ?></span>
              <?php if ($h['is_recurring']): ?>
                <span class="text-xs text-slate-400 ml-1">(todo ano)</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php if ($h['is_recurring']): ?>
                <span class="badge-approved badge-dot">Sim</span>
              <?php else: ?>
                <span class="text-slate-400 text-xs">Não</span>
              <?php endif; ?>
            </td>
            <td class="text-right">
              <div class="flex items-center justify-end gap-1">
                <button
                  @click="$dispatch('open-holiday-modal', {
                    mode: 'edit',
                    id: <?= $h['id'] ?>,
                    name: <?= json_encode($h['name']) ?>,
                    date: <?= json_encode($h['date']) ?>,
                    is_recurring: <?= $h['is_recurring'] ? 'true' : 'false' ?>
                  })"
                  class="btn-ghost p-2 text-yellow-500 hover:bg-yellow-50 hover:text-yellow-600" aria-label="Editar">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                         m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </button>
                <form method="POST" action="<?= base_url('admin/feriados/' . $h['id'] . '/delete') ?>"
                      @submit.prevent="if(confirm('Remover o feriado «<?= esc($h['name']) ?>»?')) $el.submit()">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn-ghost p-2 text-red-500 hover:bg-red-50 hover:text-red-600"
                          aria-label="Remover">
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
      <?= count($items) ?> feriado<?= count($items) !== 1 ? 's' : '' ?> cadastrado<?= count($items) !== 1 ? 's' : '' ?>
    </div>
  <?php endif; ?>

  <!-- Modal -->
  <div x-show="modalOpen" class="modal-overlay" x-cloak
       @open-holiday-modal.window="openModal($event.detail)"
       x-transition:enter="transition-opacity duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition-opacity duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="modal-panel max-w-md" @click.stop
         x-transition:enter="transition duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">

      <div class="modal-header">
        <h3 class="text-sm font-semibold text-slate-900"
            x-text="mode === 'create' ? 'Novo Feriado' : 'Editar Feriado'"></h3>
        <button @click="modalOpen = false" class="btn-ghost btn-sm p-1" aria-label="Fechar">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <form :action="mode === 'create' ? '<?= base_url('admin/feriados') ?>' : `<?= base_url('admin/feriados/') ?>${editId}/update`"
            method="POST">
        <?= csrf_field() ?>

        <div class="modal-body space-y-4">
          <div>
            <label for="h_name" class="form-label form-label-required">Nome do feriado</label>
            <input type="text" id="h_name" name="name" x-model="form.name"
                   class="form-input" placeholder="Ex: Natal, Tiradentes..." maxlength="200" required>
          </div>
          <div>
            <label for="h_date" class="form-label form-label-required">Data</label>
            <input type="date" id="h_date" name="date" x-model="form.date"
                   class="form-input" required>
            <p class="form-hint">Para feriados recorrentes, o ano da data é ignorado.</p>
          </div>
          <div class="flex items-center gap-3">
            <input type="hidden" name="is_recurring" value="0">
            <input type="checkbox" id="h_recurring" name="is_recurring" value="1"
                   x-model="form.is_recurring" class="rounded border-slate-300 text-primary">
            <label for="h_recurring" class="text-sm text-slate-700">
              Feriado recorrente (se repete todo ano)
            </label>
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
function holidaysImport() {
  return {
    year: new Date().getFullYear(),
    loading: false,

    async importApi() {
      this.loading = true;
      try {
        const res  = await fetch(`<?= base_url('admin/feriados/importar-api/') ?>${this.year}`, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
        });
        const data = await res.json();
        if (res.ok) {
          alert(data.message);
          if (data.imported > 0) location.reload();
        } else {
          alert('Erro: ' + (data.error ?? 'Falha na importação.'));
        }
      } catch (e) {
        alert('Erro de conexão ao importar feriados.');
      } finally {
        this.loading = false;
      }
    }
  }
}

function holidaysPage() {
  return {
    modalOpen: false,
    mode: 'create',
    editId: null,
    form: { name: '', date: '', is_recurring: false },

    openModal(detail) {
      this.mode = detail.mode;
      if (detail.mode === 'edit') {
        this.editId           = detail.id;
        this.form.name        = detail.name;
        this.form.date        = detail.date;
        this.form.is_recurring = detail.is_recurring;
      } else {
        this.editId = null;
        this.form = { name: '', date: '', is_recurring: false };
      }
      this.modalOpen = true;
    }
  }
}
</script>
<?= $this->endSection() ?>
