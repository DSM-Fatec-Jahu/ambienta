<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Ambientes</h1>
    <p class="page-subtitle">Salas, laboratórios e espaços disponíveis para reserva</p>
  </div>
  <button @click="$dispatch('open-room-modal', { mode: 'create' })" class="btn-primary">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    Novo Ambiente
  </button>
</div>

<div class="card overflow-hidden" x-data="roomsPage()">

  <?php if (empty($items)): ?>
    <div class="empty-state">
      <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
      </svg>
      <p class="empty-state-title">Nenhum ambiente cadastrado</p>
      <p class="empty-state-description">Adicione o primeiro ambiente para que os usuários possam fazer reservas.</p>
      <button @click="$dispatch('open-room-modal', { mode: 'create' })" class="btn-primary mt-4">
        Cadastrar Ambiente
      </button>
    </div>
  <?php else: ?>
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
          <?php foreach ($items as $r): ?>
          <tr>
            <td class="font-medium text-slate-900"><?= esc($r['name']) ?></td>
            <td>
              <?php if ($r['code']): ?>
                <span class="badge-primary"><?= esc($r['code']) ?></span>
              <?php else: ?>
                <span class="text-slate-300">—</span>
              <?php endif; ?>
            </td>
            <td class="text-slate-600">
              <?= esc($r['building_name'] ?? '—') ?>
              <?php if ($r['floor']): ?>
                <span class="text-slate-400 text-xs ml-1">(<?= esc($r['floor']) ?>)</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?= $r['capacity'] > 0 ? esc($r['capacity']) . ' pessoas' : '<span class="text-slate-300">—</span>' ?>
            </td>
            <td class="text-center">
              <?php if ($r['allows_equipment_lending']): ?>
                <span class="text-success">Sim</span>
              <?php else: ?>
                <span class="text-slate-300">Não</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php
              $rData = $ratingsMap[(int) $r['id']] ?? null;
              if ($rData && $rData['total_ratings'] > 0):
              ?>
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600">
                  <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                  </svg>
                  <?= number_format($rData['avg_rating'], 1) ?>
                  <span class="text-slate-400 font-normal">(<?= $rData['total_ratings'] ?>)</span>
                </span>
              <?php else: ?>
                <span class="text-slate-300 text-xs">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($r['is_active']): ?>
                <span class="badge-approved badge-dot">Ativo</span>
              <?php else: ?>
                <span class="badge-cancelled">Inativo</span>
              <?php endif; ?>
              <?php if ($r['maintenance_mode']): ?>
                <span class="badge-warning ml-1">
                  Manutenção<?= $r['maintenance_until'] ? ' até ' . date('d/m', strtotime($r['maintenance_until'])) : '' ?>
                </span>
              <?php endif; ?>
            </td>
            <td class="text-right">
              <div class="flex items-center justify-end gap-1">
                <button
                  @click="$dispatch('open-maintenance-modal', <?= htmlspecialchars(json_encode([
                    'id'                 => (int) $r['id'],
                    'name'               => $r['name'],
                    'maintenance_mode'   => (bool) $r['maintenance_mode'],
                    'maintenance_until'  => $r['maintenance_until'] ?? '',
                    'maintenance_reason' => $r['maintenance_reason'] ?? '',
                  ]), ENT_QUOTES) ?>)"
                  class="btn-ghost p-2 text-orange-500 hover:bg-orange-50 hover:text-orange-600" aria-label="Manutenção"
                  title="Modo manutenção">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
                </button>
                <button
                  @click="$dispatch('open-room-modal', <?= htmlspecialchars(json_encode([
                    'mode'                     => 'edit',
                    'id'                       => (int) $r['id'],
                    'name'                     => $r['name'],
                    'code'                     => $r['code'] ?? '',
                    'building_id'              => (int) ($r['building_id'] ?? 0),
                    'capacity'                 => (int) $r['capacity'],
                    'floor'                    => $r['floor'] ?? '',
                    'description'              => $r['description'] ?? '',
                    'allows_equipment_lending' => (bool) $r['allows_equipment_lending'],
                    'is_active'                => (bool) $r['is_active'],
                  ]), ENT_QUOTES) ?>)"
                  class="btn-ghost p-2 text-yellow-500 hover:bg-yellow-50 hover:text-yellow-600" aria-label="Editar">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                         m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </button>
                <form method="POST" action="<?= base_url('admin/ambientes/' . $r['id'] . '/delete') ?>"
                      @submit.prevent="if(confirm('Excluir o ambiente «<?= esc($r['name']) ?>»?')) $el.submit()">
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
      <?= count($items) ?> ambiente<?= count($items) !== 1 ? 's' : '' ?> cadastrado<?= count($items) !== 1 ? 's' : '' ?>
    </div>
  <?php endif; ?>

  <!-- ── Maintenance Modal ──────────────────────────────────────── -->
  <div x-show="maintModalOpen" class="modal-overlay" x-cloak
       @open-maintenance-modal.window="openMaintModal($event.detail)"
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
          Modo Manutenção — <span x-text="maintForm.name"></span>
        </h3>
        <button @click="maintModalOpen = false" class="btn-ghost btn-sm p-1" aria-label="Fechar">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
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
              <input type="date" id="maint_until" name="maintenance_until"
                     x-model="maintForm.until" class="form-input">
              <p class="form-hint">Deixe em branco se a duração for indefinida.</p>
            </div>
            <div>
              <label for="maint_reason" class="form-label">Motivo (opcional)</label>
              <textarea id="maint_reason" name="maintenance_reason" rows="2"
                        x-model="maintForm.reason"
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
          <button type="submit"
                  :class="maintForm.mode ? 'btn-danger' : 'btn-primary'"
                  x-text="maintForm.mode ? 'Colocar em manutenção' : 'Retirar de manutenção'"></button>
        </div>
      </form>
    </div>
  </div>

  <!-- ── Room Modal ──────────────────────────────────────────────── -->
  <div x-show="modalOpen" class="modal-overlay" x-cloak
       @open-room-modal.window="openModal($event.detail)"
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
        <h3 class="text-sm font-semibold text-slate-900"
            x-text="mode === 'create' ? 'Novo Ambiente' : 'Editar Ambiente'"></h3>
        <button @click="modalOpen = false" class="btn-ghost btn-sm p-1" aria-label="Fechar">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <form :action="mode === 'create' ? '<?= base_url('admin/ambientes') ?>' : `<?= base_url('admin/ambientes/') ?>${editId}/update`"
            method="POST">
        <?= csrf_field() ?>

        <div class="modal-body">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div class="sm:col-span-2">
              <label for="r_name" class="form-label form-label-required">Nome do ambiente</label>
              <input type="text" id="r_name" name="name" x-model="form.name"
                     class="form-input" placeholder="Ex: Sala de Reuniões 01" maxlength="200" required>
            </div>

            <div>
              <label for="r_code" class="form-label">Código / Sigla</label>
              <input type="text" id="r_code" name="code" x-model="form.code"
                     class="form-input" placeholder="Ex: SR-01" maxlength="20">
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
              <input type="number" id="r_capacity" name="capacity" x-model="form.capacity"
                     class="form-input" min="0" max="9999" placeholder="0">
            </div>

            <div>
              <label for="r_floor" class="form-label">Andar / Localização</label>
              <input type="text" id="r_floor" name="floor" x-model="form.floor"
                     class="form-input" placeholder="Ex: Térreo, 1º andar" maxlength="20">
            </div>

            <div class="sm:col-span-2">
              <label for="r_desc" class="form-label">Descrição</label>
              <textarea id="r_desc" name="description" x-model="form.description"
                        rows="2" class="form-input resize-none"
                        placeholder="Recursos, observações..."></textarea>
            </div>

            <div class="flex items-center gap-3">
              <input type="hidden" name="allows_equipment_lending" value="0">
              <input type="checkbox" id="r_equip" name="allows_equipment_lending" value="1"
                     x-model="form.allows_equipment_lending"
                     class="rounded border-slate-300 text-primary">
              <label for="r_equip" class="text-sm text-slate-700">Permite empréstimo de equipamentos</label>
            </div>

            <div class="flex items-center gap-3">
              <input type="hidden" name="is_active" value="0">
              <input type="checkbox" id="r_active" name="is_active" value="1"
                     x-model="form.is_active" class="rounded border-slate-300 text-primary">
              <label for="r_active" class="text-sm text-slate-700">Ambiente ativo</label>
            </div>

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
function roomsPage() {
  return {
    modalOpen: false,
    mode: 'create',
    editId: null,
    form: {
      name: '', code: '', building_id: '', capacity: '', floor: '',
      description: '', allows_equipment_lending: false, is_active: true
    },

    maintModalOpen: false,
    maintForm: {
      id: null, name: '', mode: false, until: '', reason: ''
    },

    openModal(detail) {
      this.mode = detail.mode;
      if (detail.mode === 'edit') {
        this.editId = detail.id;
        this.form = {
          name:                     detail.name,
          code:                     detail.code,
          building_id:              detail.building_id || '',
          capacity:                 detail.capacity || '',
          floor:                    detail.floor,
          description:              detail.description,
          allows_equipment_lending: detail.allows_equipment_lending,
          is_active:                detail.is_active,
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
        id:     detail.id,
        name:   detail.name,
        mode:   detail.maintenance_mode,
        until:  detail.maintenance_until || '',
        reason: detail.maintenance_reason || '',
      };
      this.maintModalOpen = true;
    }
  }
}
</script>
<?= $this->endSection() ?>
