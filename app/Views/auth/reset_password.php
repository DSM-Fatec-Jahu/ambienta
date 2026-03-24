<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<h2 class="text-xl font-bold text-gray-900 mb-1">Nova senha</h2>
<p class="text-sm text-gray-500 mb-5">Defina uma nova senha para sua conta</p>

<?php if (!empty($errors)): ?>
  <div class="alert-danger mb-4" role="alert">
    <ul class="text-sm list-none m-0 p-0">
      <?php foreach ($errors as $error): ?>
        <li><?= esc($error) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="POST" action="<?= base_url('redefinir-senha/' . esc($token)) ?>" novalidate>
  <?= csrf_field() ?>

  <div class="mb-4">
    <label for="password" class="form-label">Nova senha</label>
    <input type="password" id="password" name="password" required
           class="form-input" placeholder="Mínimo 8 caracteres">
  </div>

  <div class="mb-5">
    <label for="password_confirm" class="form-label">Confirmar senha</label>
    <input type="password" id="password_confirm" name="password_confirm" required
           class="form-input" placeholder="Repita a senha">
  </div>

  <button type="submit" class="btn-primary w-full">
    Redefinir senha
  </button>
</form>

<?= $this->endSection() ?>
