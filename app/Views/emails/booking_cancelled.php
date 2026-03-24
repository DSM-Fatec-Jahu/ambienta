<?php
$emailSubject = 'Reserva cancelada';
$date = date('d/m/Y', strtotime($booking['date']));
$dow  = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][date('w', strtotime($booking['date']))];
$reasonEsc = htmlspecialchars($reason ?? 'Cancelada pelo solicitante');
$emailBody = <<<HTML
<span class="badge badge-cancelled">🚫 Cancelada</span>
<h2>Reserva cancelada</h2>
<p>Olá, <strong>{$user['name']}</strong>!</p>
<p>A reserva abaixo foi <strong>cancelada</strong>.</p>

<div class="info-box">
  <div class="info-row">
    <span class="info-label">Título</span>
    <span class="info-value">{$booking['title']}</span>
  </div>
  <div class="info-row">
    <span class="info-label">Ambiente</span>
    <span class="info-value">{$room['name']}</span>
  </div>
  <div class="info-row">
    <span class="info-label">Data</span>
    <span class="info-value">{$dow}, {$date}</span>
  </div>
  <div class="info-row">
    <span class="info-label">Horário</span>
    <span class="info-value">{$booking['start_time']} – {$booking['end_time']}</span>
  </div>
</div>

<div class="note-box">
  <strong>Motivo:</strong> {$reasonEsc}
</div>

<p>Se precisar reservar outro horário, acesse o sistema:</p>
<a href="{$appUrl}/reservas/nova" class="btn">Fazer nova reserva</a>
HTML;

echo view('emails/_layout', compact('emailSubject', 'emailBody', 'appName', 'appUrl'));
