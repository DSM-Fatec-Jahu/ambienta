<?php
$emailSubject = 'Você foi convidado para acessar o sistema';
$roleName = [
    'role_requester'     => 'Solicitante',
    'role_technician'    => 'Resp. Técnico / Apoio',
    'role_coordinator'   => 'Coordenador',
    'role_vice_director' => 'Vice-diretor',
    'role_director'      => 'Diretor',
    'role_admin'         => 'Administrador',
][$invite['role']] ?? $invite['role'];

$expiresAt = date('d/m/Y \à\s H:i', strtotime($invite['expires_at']));
$instName  = htmlspecialchars($institution['name'] ?? $appName);

$emailBody = <<<HTML
<h2>Você foi convidado!</h2>
<p>Olá!</p>
<p><strong>{$inviterName}</strong> convidou você para acessar o sistema de reservas de ambientes da instituição <strong>{$instName}</strong>.</p>

<div class="info-box">
  <div class="info-row">
    <span class="info-label">E-mail</span>
    <span class="info-value">{$invite['email']}</span>
  </div>
  <div class="info-row">
    <span class="info-label">Perfil atribuído</span>
    <span class="info-value">{$roleName}</span>
  </div>
  <div class="info-row">
    <span class="info-label">Convite válido até</span>
    <span class="info-value">{$expiresAt}</span>
  </div>
</div>

<p>Para ativar sua conta, clique no botão abaixo e crie uma senha:</p>
<a href="{$acceptUrl}" class="btn">Aceitar convite</a>

<p style="margin-top:16px; font-size:12px; color:#64748b;">
  Se você não esperava este convite, pode ignorar este e-mail com segurança.<br>
  O link expira em 72 horas.
</p>
HTML;

echo view('emails/_layout', compact('emailSubject', 'emailBody', 'appName', 'appUrl'));
