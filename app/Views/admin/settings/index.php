<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Configurações</h1>
    <p class="page-subtitle">Parâmetros gerais da instituição e do sistema de reservas</p>
  </div>
</div>

<form method="POST" action="<?= base_url('admin/configuracoes') ?>">
  <?= csrf_field() ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Main form -->
    <div class="lg:col-span-2 space-y-4">

      <!-- Institution info -->
      <div class="card">
        <div class="card-header">
          <h2 class="text-sm font-semibold text-slate-900">Instituição</h2>
        </div>
        <div class="card-body space-y-4">
          <div>
            <label for="s_name" class="form-label form-label-required">Nome da instituição</label>
            <input type="text" id="s_name" name="institution_name"
                   value="<?= esc($institution['name'] ?? '') ?>"
                   class="form-input" maxlength="200">
          </div>
          <div>
            <label for="s_email" class="form-label">E-mail de contato</label>
            <input type="email" id="s_email" name="contact_email"
                   value="<?= esc($settings['contact_email'] ?? '') ?>"
                   class="form-input" maxlength="320">
          </div>
        </div>
      </div>

      <!-- Auth settings -->
      <div class="card">
        <div class="card-header">
          <h2 class="text-sm font-semibold text-slate-900">Autenticação</h2>
        </div>
        <div class="card-body space-y-4">

          <div class="flex items-start gap-3">
            <input type="hidden" name="local_login_enabled" value="0">
            <input type="checkbox" id="s_local" name="local_login_enabled" value="1"
                   <?= !empty($settings['auth']['local_login_enabled']) ? 'checked' : '' ?>
                   class="mt-0.5 rounded border-slate-300 text-primary">
            <div>
              <label for="s_local" class="text-sm font-medium text-slate-700">Login local habilitado</label>
              <p class="text-xs text-slate-400">Permite login com e-mail e senha cadastrados localmente.</p>
            </div>
          </div>

          <div class="flex items-start gap-3">
            <input type="hidden" name="sso_google_enabled" value="0">
            <input type="checkbox" id="s_sso" name="sso_google_enabled" value="1"
                   <?= !empty($settings['auth']['sso_google_enabled']) ? 'checked' : '' ?>
                   class="mt-0.5 rounded border-slate-300 text-primary">
            <div>
              <label for="s_sso" class="text-sm font-medium text-slate-700">SSO Google habilitado</label>
              <p class="text-xs text-slate-400">Permite login via conta Google. Requer credenciais OAuth configuradas no .env</p>
            </div>
          </div>

          <div>
            <label for="s_domains" class="form-label">Domínios Google autorizados</label>
            <input type="text" id="s_domains" name="sso_allowed_domains"
                   value="<?= esc(implode(', ', $settings['auth']['sso_allowed_domains'] ?? [])) ?>"
                   class="form-input" placeholder="Ex: fatecjahu.edu.br, sp.gov.br">
            <p class="form-hint">Separados por vírgula. Deixe vazio para aceitar qualquer domínio Google.</p>
          </div>

        </div>
      </div>

      <!-- Resource settings — RN-R08 -->
      <div class="card">
        <div class="card-header">
          <h2 class="text-sm font-semibold text-slate-900">Recursos</h2>
        </div>
        <div class="card-body space-y-4">

          <div class="flex items-start gap-3">
            <input type="hidden" name="resource_return_block_requester" value="0">
            <input type="checkbox" id="s_block_requester" name="resource_return_block_requester" value="1"
                   <?= !empty($settings['resources']['resource_return_block_requester']) ? 'checked' : '' ?>
                   class="mt-0.5 rounded border-slate-300 text-primary">
            <div>
              <label for="s_block_requester" class="text-sm font-medium text-slate-700">Bloquear solicitante inadimplente</label>
              <p class="text-xs text-slate-400">Quando ativo, solicitantes com devolução de recurso vencida ficam impedidos de criar novas reservas até regularizar as pendências.</p>
            </div>
          </div>

          <div class="max-w-xs">
            <label for="s_return_deadline" class="form-label">Prazo de devolução (horas)</label>
            <input type="number" id="s_return_deadline" name="resource_return_deadline_hours"
                   value="<?= (int)($settings['resources']['resource_return_deadline_hours'] ?? 1) ?>"
                   class="form-input" min="1" max="720">
            <p class="form-hint">Horas após o encerramento da reserva antes de considerar a devolução como vencida e disparar notificações. Padrão: 1 hora.</p>
          </div>

        </div>
      </div>

      <!-- Booking settings -->
      <div class="card">
        <div class="card-header">
          <h2 class="text-sm font-semibold text-slate-900">Reservas</h2>
        </div>
        <div class="card-body space-y-4">

          <div class="flex items-start gap-3">
            <input type="hidden" name="requires_approval" value="0">
            <input type="checkbox" id="s_approval" name="requires_approval" value="1"
                   <?= !empty($settings['booking']['requires_approval']) ? 'checked' : '' ?>
                   class="mt-0.5 rounded border-slate-300 text-primary">
            <div>
              <label for="s_approval" class="text-sm font-medium text-slate-700">Requer aprovação de reservas</label>
              <p class="text-xs text-slate-400">Quando ativo, toda reserva fica pendente até ser aprovada manualmente.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
              <label for="s_maxdays" class="form-label">Máx. dias com antecedência</label>
              <input type="number" id="s_maxdays" name="max_days_ahead"
                     value="<?= (int)($settings['booking']['max_days_ahead'] ?? 90) ?>"
                     class="form-input" min="1" max="365">
              <p class="form-hint">Dias à frente que uma reserva pode ser feita.</p>
            </div>
            <div>
              <label for="s_mindur" class="form-label">Duração mínima (min)</label>
              <input type="number" id="s_mindur" name="min_duration_min"
                     value="<?= (int)($settings['booking']['min_duration_min'] ?? 30) ?>"
                     class="form-input" min="15" step="15">
            </div>
            <div>
              <label for="s_maxdur" class="form-label">Duração máxima (min)</label>
              <input type="number" id="s_maxdur" name="max_duration_min"
                     value="<?= (int)($settings['booking']['max_duration_min'] ?? 480) ?>"
                     class="form-input" min="15" step="15">
            </div>
            <div>
              <label for="s_maxweek" class="form-label">Máx. reservas por semana</label>
              <input type="number" id="s_maxweek" name="max_bookings_per_week"
                     value="<?= (int)($settings['booking']['max_bookings_per_week'] ?? 0) ?>"
                     class="form-input" min="0" max="999">
              <p class="form-hint">Limite por usuário na semana. 0 = ilimitado.</p>
            </div>
          </div>

          <!-- Check-in settings -->
          <div class="pt-2 border-t border-slate-100">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Check-in de Presença</p>

            <div class="flex items-start gap-3 mb-4">
              <input type="hidden" name="auto_cancel_no_checkin" value="0">
              <input type="checkbox" id="s_autocancel" name="auto_cancel_no_checkin" value="1"
                     <?= !empty($settings['booking']['auto_cancel_no_checkin']) ? 'checked' : '' ?>
                     class="mt-0.5 rounded border-slate-300 text-primary">
              <div>
                <label for="s_autocancel" class="text-sm font-medium text-slate-700">Cancelar automaticamente reservas sem check-in</label>
                <p class="text-xs text-slate-400">Quando ativo, o comando <code>booking:auto-cancel</code> cancela reservas aprovadas encerradas sem check-in.</p>
              </div>
            </div>

            <div class="max-w-xs">
              <label for="s_checkin_window" class="form-label">Janela de check-in (min antes do início)</label>
              <input type="number" id="s_checkin_window" name="checkin_window_min"
                     value="<?= (int)($settings['booking']['checkin_window_min'] ?? 15) ?>"
                     class="form-input" min="5" max="120" step="5">
              <p class="form-hint">Minutos antes do início em que o check-in fica disponível. Padrão: 15 min.</p>
            </div>
          </div>

        </div>
      </div>

    </div>

    <!-- Right sidebar -->
    <div class="space-y-4">
      <div class="card sticky top-20">
        <div class="card-header">
          <h2 class="text-sm font-semibold text-slate-900">Informações do sistema</h2>
        </div>
        <div class="card-body space-y-2 text-xs text-slate-500">
          <div class="flex justify-between">
            <span>Criado em</span>
            <span class="font-medium text-slate-700">
              <?= isset($institution['created_at']) ? date('d/m/Y', strtotime($institution['created_at'])) : '—' ?>
            </span>
          </div>
          <?php if (!empty($institution['slug'])): ?>
          <div class="flex justify-between">
            <span>Slug</span>
            <code class="font-mono text-slate-700"><?= esc($institution['slug']) ?></code>
          </div>
          <?php endif; ?>
        </div>
        <div class="card-footer">
          <div class="alert-warning mb-3" role="alert">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="text-xs">Alterações afetam todos os usuários imediatamente.</span>
          </div>
          <button type="submit" class="btn-primary w-full">Salvar configurações</button>
        </div>
      </div>
    </div>

  </div>

</form>

<?= $this->endSection() ?>
