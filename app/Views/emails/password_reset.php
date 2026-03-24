<?php
$emailSubject = 'Redefinição de senha';
$emailBody = <<<HTML
<h2>Redefinir senha</h2>
<p>Olá, <strong>{$user['name']}</strong>!</p>
<p>Recebemos uma solicitação para redefinir a senha da sua conta. Clique no botão abaixo para criar uma nova senha:</p>
<a href="{$resetUrl}" class="btn">Redefinir minha senha</a>
<p style="margin-top:16px; font-size:12px; color:#64748b;">
  Este link é válido por <strong>1 hora</strong>. Após esse prazo, será necessário solicitar um novo link.<br><br>
  Se você não solicitou a redefinição de senha, pode ignorar este e-mail com segurança — sua senha permanece inalterada.
</p>
HTML;

echo view('emails/_layout', compact('emailSubject', 'emailBody', 'appName', 'appUrl'));
