<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Minha Conta</h1>
    <p class="page-subtitle">Gerencie suas informações pessoais e senha de acesso</p>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- Left: avatar + role info -->
  <div class="space-y-4">
    <div class="card">
      <div class="card-body flex flex-col items-center text-center gap-3 py-6"
           x-data="avatarUpload()">

        <!-- Avatar display -->
        <div class="relative group">
          <?php if (!empty($profileUser['avatar_path'])): ?>
            <img :src="preview || '<?= esc(base_url('uploads/avatars/' . $profileUser['avatar_path'])) ?>'"
                 alt="Avatar"
                 class="h-20 w-20 rounded-full object-cover ring-4 ring-white shadow-md">
          <?php elseif (!empty($profileUser['avatar_url'])): ?>
            <img :src="preview || '<?= esc($profileUser['avatar_url']) ?>'"
                 alt="Avatar"
                 class="h-20 w-20 rounded-full object-cover ring-4 ring-white shadow-md">
          <?php else: ?>
            <template x-if="!preview">
              <div class="h-20 w-20 rounded-full bg-primary flex items-center justify-center
                          text-2xl font-bold text-white ring-4 ring-white shadow-md">
                <?= strtoupper(substr($profileUser['name'] ?? 'U', 0, 1)) ?>
              </div>
            </template>
            <template x-if="preview">
              <img :src="preview" alt="Preview"
                   class="h-20 w-20 rounded-full object-cover ring-4 ring-white shadow-md">
            </template>
          <?php endif; ?>

          <!-- Overlay edit button -->
          <label for="avatar_file"
                 class="absolute inset-0 rounded-full flex items-center justify-center
                        bg-black/40 text-white opacity-0 group-hover:opacity-100
                        transition-opacity cursor-pointer"
                 title="Trocar foto">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </label>
        </div>

        <!-- Upload form (hidden until preview selected) -->
        <form method="POST" action="<?= base_url('perfil/avatar') ?>"
              enctype="multipart/form-data" x-ref="uploadForm" id="avatarForm">
          <?= csrf_field() ?>
          <input type="file" id="avatar_file" name="avatar" accept="image/*"
                 class="sr-only" @change="onFileChange($event)">
        </form>

        <!-- Save / Cancel preview buttons -->
        <div x-show="preview" x-cloak class="flex gap-2">
          <button type="submit" form="avatarForm" class="btn-primary btn-sm">
            Salvar foto
          </button>
          <button type="button" @click="preview = null" class="btn-secondary btn-sm">
            Cancelar
          </button>
        </div>

        <!-- Remove avatar link (only if has a local avatar) -->
        <?php if (!empty($profileUser['avatar_path'])): ?>
          <form method="POST" action="<?= base_url('perfil/avatar/remover') ?>" x-show="!preview"
                onsubmit="return confirm('Remover foto de perfil?')">
            <?= csrf_field() ?>
            <button type="submit" class="text-xs text-slate-400 hover:text-red-500 transition-colors">
              Remover foto
            </button>
          </form>
        <?php endif; ?>

        <div>
          <p class="font-semibold text-slate-900"><?= esc($profileUser['name']) ?></p>
          <p class="text-xs text-slate-500 mt-0.5"><?= esc($rolesLabels[$profileUser['role']] ?? $profileUser['role']) ?></p>
        </div>

        <?php if ($profileUser['google_id']): ?>
          <span class="badge-primary flex items-center gap-1.5">
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12.545 10.239v3.821h5.445c-.712 2.315-2.647 3.972-5.445 3.972a6.033 6.033 0 110-12.064c1.498 0 2.866.549 3.921 1.453l2.814-2.814A9.969 9.969 0 0012.545 2C7.021 2 2.543 6.477 2.543 12s4.478 10 10.002 10c8.396 0 10.249-7.85 9.426-11.748l-9.426-.013z"/>
            </svg>
            Conta Google vinculada
          </span>
        <?php endif; ?>
      </div>
      <div class="card-footer space-y-2 text-xs text-slate-500">
        <div class="flex justify-between">
          <span>E-mail</span>
          <span class="font-medium text-slate-700 truncate ml-2"><?= esc($profileUser['email']) ?></span>
        </div>
        <div class="flex justify-between">
          <span>Último acesso</span>
          <span class="font-medium text-slate-700">
            <?= $profileUser['last_login_at'] ? date('d/m/Y H:i', strtotime($profileUser['last_login_at'])) : '—' ?>
          </span>
        </div>
        <div class="flex justify-between">
          <span>Membro desde</span>
          <span class="font-medium text-slate-700">
            <?= date('d/m/Y', strtotime($profileUser['created_at'])) ?>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Right: forms -->
  <div class="lg:col-span-2 space-y-4">

    <!-- Update info -->
    <div class="card">
      <div class="card-header">
        <h2 class="text-sm font-semibold text-slate-900">Informações pessoais</h2>
      </div>
      <form method="POST" action="<?= base_url('perfil/info') ?>">
        <?= csrf_field() ?>
        <div class="card-body space-y-4">
          <div>
            <label for="p_name" class="form-label form-label-required">Nome completo</label>
            <input type="text" id="p_name" name="name"
                   value="<?= esc($profileUser['name']) ?>"
                   class="form-input" maxlength="200" required>
          </div>
          <div>
            <label for="p_email" class="form-label">E-mail</label>
            <input type="email" id="p_email" value="<?= esc($profileUser['email']) ?>"
                   class="form-input bg-slate-50" readonly aria-readonly="true">
            <p class="form-hint">O e-mail não pode ser alterado por aqui.</p>
          </div>
          <div>
            <label for="p_phone" class="form-label">Telefone / Celular</label>
            <input type="text" id="p_phone" name="cellphone"
                   value="<?= esc($profileUser['cellphone'] ?? '') ?>"
                   class="form-input" maxlength="30" placeholder="(00) 00000-0000">
          </div>
        </div>
        <div class="card-footer flex justify-end">
          <button type="submit" class="btn-primary">Salvar alterações</button>
        </div>
      </form>
    </div>

    <!-- Change password -->
    <?php if (!empty($profileUser['password_hash'])): ?>
    <div class="card">
      <div class="card-header">
        <h2 class="text-sm font-semibold text-slate-900">Alterar senha</h2>
      </div>
      <form method="POST" action="<?= base_url('perfil/senha') ?>" x-data="{ show: false }">
        <?= csrf_field() ?>
        <div class="card-body space-y-4">
          <div>
            <label for="p_curr" class="form-label form-label-required">Senha atual</label>
            <div class="input-group">
              <input :type="show ? 'text' : 'password'" id="p_curr" name="current_password"
                     class="form-input pr-10" required autocomplete="current-password">
              <button type="button" @click="show = !show"
                      class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600"
                      tabindex="-1" aria-label="Mostrar senha">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path x-show="!show" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
                       -1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  <path x-show="show" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7
                       a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243
                       M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29
                       M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7
                       a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                </svg>
              </button>
            </div>
          </div>
          <div>
            <label for="p_new" class="form-label form-label-required">Nova senha</label>
            <input :type="show ? 'text' : 'password'" id="p_new" name="new_password"
                   class="form-input" required minlength="8" autocomplete="new-password"
                   placeholder="Mínimo 8 caracteres">
          </div>
          <div>
            <label for="p_confirm" class="form-label form-label-required">Confirmar nova senha</label>
            <input :type="show ? 'text' : 'password'" id="p_confirm" name="confirm_password"
                   class="form-input" required minlength="8" autocomplete="new-password">
          </div>
        </div>
        <div class="card-footer flex justify-end">
          <button type="submit" class="btn-primary">Alterar senha</button>
        </div>
      </form>
    </div>
    <?php else: ?>
    <div class="card">
      <div class="card-body">
        <div class="alert-info" role="alert">
          <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span>Sua conta usa autenticação Google. Gerencie sua senha diretamente pelo Google.</span>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>

</div>

<?= $this->section('scripts') ?>
<script>
function avatarUpload() {
  return {
    preview: null,
    onFileChange(event) {
      const file = event.target.files[0];
      if (!file) return;
      if (file.size > 2 * 1024 * 1024) {
        alert('O arquivo excede o limite de 2 MB.');
        event.target.value = '';
        return;
      }
      const reader = new FileReader();
      reader.onload = (e) => { this.preview = e.target.result; };
      reader.readAsDataURL(file);
    },
  };
}
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
