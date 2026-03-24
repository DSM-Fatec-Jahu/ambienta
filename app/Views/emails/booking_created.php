<?php
$emailSubject = 'Reserva recebida — aguardando aprovação';
$date = date('d/m/Y', strtotime($booking['date']));
$dow  = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][date('w', strtotime($booking['date']))];
$emailBody = <<<HTML
<span class="badge badge-pending">⏳ Aguardando aprovação</span>
<h2>Reserva recebida com sucesso!</h2>
<p>Olá, <strong>{$user['name']}</strong>!</p>
<p>Sua solicitação de reserva foi registrada e está <strong>aguardando aprovação</strong> da equipe responsável.</p>

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

<p>Você receberá outro e-mail quando a reserva for aprovada ou recusada.</p>
<p>Para acompanhar o status da sua reserva, acesse o sistema:</p>
<a href="{$appUrl}/reservas/{$booking['id']}" class="btn">Ver minha reserva</a>
HTML;
echo view('emails/_layout', compact('emailSubject', 'emailBody', 'appName', 'appUrl'));
