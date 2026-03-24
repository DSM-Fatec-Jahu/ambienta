<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<h2 class="text-xl font-bold text-gray-900 mb-1">Recuperar senha</h2>
<p class="text-sm text-gray-500 mb-5">Informe seu e-mail para receber o link de redefinição</p>

<form method="POST" action="<?= base_url('esqueci-senha') ?>" novalidate>
  <?= csrf_field() ?>

  <div class="mb-4">
    <label for="email" class="form-label">E-mail</label>
    <input type="email" id="email" name="email" autocomplete="email" required
           value="<?= esc(old('email')) ?>"
           class="form-input"
           placeholder="seu@email.com">
  </div>

  <button type="submit" class="btn-primary w-full">
    Enviar link de redefinição
  </button>
</form>

<p class="text-center mt-4 text-sm text-gray-500">
  <a href="<?= base_url('login') ?>" class="text-primary hover:text-primary-dark">&larr; Voltar ao login</a>
</p>

<?= $this->endSection() ?>
