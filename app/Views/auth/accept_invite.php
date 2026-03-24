<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<h2 class="text-xl font-bold text-slate-900 mb-1">Ativar sua conta</h2>
<p class="text-sm text-slate-500 mb-5">
  Complete o cadastro para acessar o sistema.
</p>

<!-- Errors -->
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

<!-- Email badge -->
<div class="mb-5 p-3 bg-primary-light rounded-lg flex items-center gap-3">
  <svg class="w-5 h-5 text-primary flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
  </svg>
  <div class="min-w-0">
    <p class="text-xs text-slate-500">Convite para</p>
    <p class="text-sm font-medium text-slate-800 truncate"><?= esc($invite['email']) ?></p>
  </div>
</div>

<form method="POST" action="<?= base_url('convite/' . esc($token)) ?>"
      x-data="{ show: false }">
  <?= csrf_field() ?>

  <!-- Name -->
  <div class="mb-4">
    <label for="ai_name" class="form-label form-label-required">Seu nome completo</label>
    <input type="text" id="ai_name" name="name"
           class="form-input" required maxlength="200"
           autocomplete="name" placeholder="João da Silva">
  </div>

  <!-- Password -->
  <div class="mb-4">
    <label for="ai_pass" class="form-label form-label-required">Senha</label>
    <div class="input-group">
      <input :type="show ? 'text' : 'password'" id="ai_pass" name="password"
             class="form-input pr-10" required minlength="8"
             autocomplete="new-password" placeholder="Mínimo 8 caracteres">
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

  <!-- Confirm password -->
  <div class="mb-6">
    <label for="ai_confirm" class="form-label form-label-required">Confirmar senha</label>
    <input :type="show ? 'text' : 'password'" id="ai_confirm" name="password_confirm"
           class="form-input" required minlength="8" autocomplete="new-password">
  </div>

  <button type="submit" class="btn-primary w-full">
    Ativar minha conta
  </button>
</form>

<p class="mt-4 text-center text-xs text-slate-400">
  Já tem conta? <a href="<?= base_url('login') ?>" class="text-primary hover:underline">Entrar</a>
</p>

<?= $this->endSection() ?>
