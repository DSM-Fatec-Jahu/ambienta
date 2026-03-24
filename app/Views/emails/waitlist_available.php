<?php
$emailSubject = 'Vaga disponível — ' . ($room['name'] ?? 'Ambiente');
$date  = date('d/m/Y', strtotime($booking['date']));
$dow   = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][date('w', strtotime($booking['date']))];
$start = substr($booking['start_time'], 0, 5);
$end   = substr($booking['end_time'],   0, 5);

$emailBody = <<<HTML
<span class="badge badge-approved">🔔 Lista de espera</span>
<h2>Uma vaga ficou disponível!</h2>
<p>Olá, <strong>{$user['name']}</strong>!</p>
<p>Boa notícia! O horário que você aguardava no ambiente <strong>{$room['name']}</strong> ficou disponível.</p>

<div class="info-box">
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
    <span class="info-value">{$start} – {$end}</span>
  </div>
</div>

<div class="note-box">
  <strong>Atenção:</strong> Esta notificação é enviada para o próximo da fila, mas o horário ainda está sujeito à
  disponibilidade no momento da reserva. Corra!
</div>

<p>Clique abaixo para fazer sua reserva:</p>
<a href="{$bookUrl}" class="btn">Reservar agora</a>
HTML;

echo view('emails/_layout', compact('emailSubject', 'emailBody', 'appName', 'appUrl'));
