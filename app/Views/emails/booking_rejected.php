<?php
$emailSubject = 'Reserva recusada';
$date = date('d/m/Y', strtotime($booking['date']));
$dow  = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][date('w', strtotime($booking['date']))];
$notesEsc = htmlspecialchars($notes ?? '');
$emailBody = <<<HTML
<span class="badge badge-rejected">❌ Recusada</span>
<h2>Sua reserva foi recusada</h2>
<p>Olá, <strong>{$user['name']}</strong>!</p>
<p>Infelizmente sua solicitação de reserva foi <strong>recusada</strong>. Veja o motivo abaixo.</p>

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

<div class="note-box danger">
  <strong>Motivo da recusa:</strong><br>
  {$notesEsc}
</div>

<p>Você pode fazer uma nova solicitação com datas ou horários alternativos:</p>
<a href="{$appUrl}/reservas/nova" class="btn btn-danger">Fazer nova reserva</a>
HTML;

echo view('emails/_layout', compact('emailSubject', 'emailBody', 'appName', 'appUrl'));
