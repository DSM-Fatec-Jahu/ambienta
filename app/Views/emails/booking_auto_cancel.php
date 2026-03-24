<?php
$emailSubject = 'Reserva cancelada por ausência';
$date = date('d/m/Y', strtotime($booking['date']));
$dow  = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][date('w', strtotime($booking['date']))];
$emailBody = <<<HTML
<span class="badge badge-cancelled">⏱️ Cancelada por ausência</span>
<h2>Reserva cancelada automaticamente</h2>
<p>Olá, <strong>{$user['name']}</strong>!</p>
<p>Sua reserva abaixo foi <strong>cancelada automaticamente</strong> pois nenhum check-in foi registrado até o término do horário agendado.</p>

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
  <strong>Motivo:</strong> O check-in deve ser registrado no sistema até o término do horário da reserva. Como nenhum check-in foi detectado, a reserva foi cancelada automaticamente.
</div>

<p>Se precisar reservar novamente, acesse o sistema:</p>
<a href="{$appUrl}/reservas/nova" class="btn">Fazer nova reserva</a>
HTML;

echo view('emails/_layout', compact('emailSubject', 'emailBody', 'appName', 'appUrl'));
