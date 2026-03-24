<?php
$emailSubject = "Como foi \"{$booking['title']}\"? Avalie o ambiente";
$date = date('d/m/Y', strtotime($booking['date']));
$dow  = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][date('w', strtotime($booking['date']))];
$emailBody = <<<HTML
<span class="badge badge-approved">⭐ Avaliação</span>
<h2>Como foi a sua reserva?</h2>
<p>Olá, <strong>{$user['name']}</strong>!</p>
<p>Sua reserva de ontem foi concluída. Gostaríamos de saber como foi a sua experiência com o ambiente.</p>

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

<p>Sua avaliação nos ajuda a melhorar continuamente os espaços disponíveis.</p>

<a href="{$appUrl}/reservas/{$booking['id']}" class="btn">Avaliar agora</a>
HTML;

echo view('emails/_layout', compact('emailSubject', 'emailBody', 'appName', 'appUrl'));
