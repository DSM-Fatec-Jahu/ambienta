<?php
$emailSubject = 'Reserva aprovada';
$date = date('d/m/Y', strtotime($booking['date']));
$dow  = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][date('w', strtotime($booking['date']))];
$reviewerName = $reviewer['name'] ?? 'Equipe';
$emailBody = <<<HTML
<span class="badge badge-approved">✅ Aprovada</span>
<h2>Sua reserva foi aprovada!</h2>
<p>Olá, <strong>{$user['name']}</strong>!</p>
<p>Ótima notícia! Sua reserva foi <strong>aprovada</strong> por {$reviewerName}.</p>

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
  <div class="info-row">
    <span class="info-label">Participantes</span>
    <span class="info-value">{$booking['attendees_count']} pessoa(s)</span>
  </div>
</div>

HTML;

if (!empty($booking['review_notes'])) {
    $notes = htmlspecialchars($booking['review_notes']);
    $emailBody .= <<<HTML
<div class="note-box">
  <strong>Observação do aprovador:</strong><br>
  {$notes}
</div>
HTML;
}

$emailBody .= <<<HTML
<p>Lembre-se de comparecer no horário agendado. Caso precise cancelar, acesse o sistema com antecedência:</p>
<a href="{$appUrl}/reservas/{$booking['id']}" class="btn">Ver reserva</a>
HTML;

echo view('emails/_layout', compact('emailSubject', 'emailBody', 'appName', 'appUrl'));
