<?php
$emailSubject = "Lembrete: sua reserva '{$booking['title']}' é amanhã";
$date = date('d/m/Y', strtotime($booking['date']));
$dow  = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][date('w', strtotime($booking['date']))];
$emailBody = <<<HTML
<span class="badge badge-approved">📅 Lembrete</span>
<h2>Lembrete: sua reserva é amanhã!</h2>
<p>Olá, <strong>{$user['name']}</strong>!</p>
<p>Este é um lembrete de que você tem uma reserva agendada para <strong>amanhã</strong>.</p>

<div class="info-box">
  <div class="info-row">
    <span class="info-label">Título</span>
    <span class="info-value">{$booking['title']}</span>
  </div>
  <div class="info-row">
    <span class="info-label">Ambiente</span>
    <span class="info-value">{$room['name']} — {$room['building_name']}</span>
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

<div class="note-box">
  <strong>Atenção:</strong><br>
  Caso precise cancelar, faça isso com antecedência pelo sistema.
</div>

<a href="{$appUrl}/reservas/{$booking['id']}" class="btn">Ver reserva</a>
HTML;

echo view('emails/_layout', compact('emailSubject', 'emailBody', 'appName', 'appUrl'));
