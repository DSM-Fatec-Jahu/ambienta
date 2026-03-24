<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<h2 class="text-xl font-bold text-slate-900 mb-1">Entrar</h2>
<p class="text-sm text-slate-500 mb-5">Acesse sua conta para gerenciar reservas</p>

<?php if (!empty($errors)): ?>
  <div class="alert-danger mb-4" role="alert">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0"/>
    </svg>
    <ul class="text-sm list-none m-0 p-0">
      <?php foreach ($errors as $error): ?>
        <li><?= esc($error) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<!-- Google SSO Button -->
<?php if (!empty($ssoEnabled)): ?>
  <a href="<?= base_url('auth/google') ?>"
     class="flex items-center justify-center gap-3 w-full border border-slate-300 rounded-lg px-4 py-2.5
            bg-white hover:bg-slate-50 text-sm font-medium text-slate-700 transition-colors mb-4 shadow-sm">
    <svg class="w-5 h-5" viewBox="0 0 24 24" aria-hidden="true">
      <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
      <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
      <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
      <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
    </svg>
    Entrar com Google
  </a>

  <?php if (!empty($localLoginEnabled)): ?>
    <div class="relative my-4">
      <div class="absolute inset-0 flex items-center">
        <div class="w-full border-t border-slate-200"></div>
      </div>
      <div class="relative flex justify-center">
        <span class="px-3 bg-white text-xs text-slate-400">ou</span>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

<!-- Local Login Form -->
<?php if (!empty($localLoginEnabled)): ?>
<form method="POST" action="<?= base_url('login') ?>" novalidate>
  <?= csrf_field() ?>

  <div class="mb-4">
    <label for="email" class="form-label">E-mail</label>
    <input type="email" id="email" name="email" autocomplete="email" required
           value="<?= esc(old('email')) ?>"
           class="form-input <?= isset($fieldErrors['email']) ? 'border-red-400 focus:border-red-400 focus:ring-red-400' : '' ?>"
           placeholder="seu@email.com">
    <?php if (!empty($fieldErrors['email'])): ?>
      <p class="form-error"><?= esc($fieldErrors['email']) ?></p>
    <?php endif; ?>
  </div>

  <div class="mb-1">
    <label for="password" class="form-label">Senha</label>
    <div class="relative">
      <input type="password" id="password" name="password" autocomplete="current-password" required
             class="form-input pr-10 <?= isset($fieldErrors['password']) ? 'border-red-400 focus:border-red-400 focus:ring-red-400' : '' ?>"
             placeholder="••••••••">
      <button type="button" onclick="togglePassword()"
              class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600"
              aria-label="Mostrar/ocultar senha">
        <svg id="eye-icon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
      </button>
    </div>
    <?php if (!empty($fieldErrors['password'])): ?>
      <p class="form-error"><?= esc($fieldErrors['password']) ?></p>
    <?php endif; ?>
  </div>

  <div class="flex justify-end mb-5">
    <a href="<?= base_url('esqueci-senha') ?>" class="text-xs text-primary hover:text-primary-dark transition-colors">
      Esqueci minha senha
    </a>
  </div>

  <button type="submit" class="btn-primary w-full btn-lg">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
    </svg>
    Entrar
  </button>
</form>
<?php endif; ?>

<script>
function togglePassword() {
  const input = document.getElementById('password');
  input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

<?= $this->endSection() ?>
