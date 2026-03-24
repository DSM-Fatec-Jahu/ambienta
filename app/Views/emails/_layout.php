<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($emailSubject ?? 'Notificação') ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
           background: #F8FAFC; color: #0F172A; font-size: 14px; line-height: 1.6; }
    .wrapper { max-width: 600px; margin: 32px auto; }
    .header  { background: #0F172A; padding: 24px 32px; border-radius: 12px 12px 0 0; }
    .header h1 { color: #fff; font-size: 18px; font-weight: 700; }
    .header p  { color: #94A3B8; font-size: 12px; margin-top: 2px; }
    .body    { background: #fff; padding: 32px; border-left: 1px solid #E2E8F0;
               border-right: 1px solid #E2E8F0; }
    .badge   { display: inline-block; padding: 4px 12px; border-radius: 9999px;
               font-size: 12px; font-weight: 600; margin-bottom: 20px; }
    .badge-pending   { background: #FEF3C7; color: #92400E; }
    .badge-approved  { background: #D1FAE5; color: #065F46; }
    .badge-rejected  { background: #FEE2E2; color: #991B1B; }
    .badge-cancelled { background: #F1F5F9; color: #475569; }
    h2 { font-size: 20px; font-weight: 700; color: #0F172A; margin-bottom: 8px; }
    p  { color: #475569; margin-bottom: 12px; }
    .info-box { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px;
                padding: 16px 20px; margin: 20px 0; }
    .info-row { display: flex; gap: 8px; padding: 6px 0;
                border-bottom: 1px solid #F1F5F9; font-size: 13px; }
    .info-row:last-child { border-bottom: none; }
    .info-label { color: #64748B; font-weight: 600; min-width: 120px; flex-shrink: 0; }
    .info-value { color: #0F172A; }
    .btn  { display: inline-block; background: #1D6FA4; color: #fff !important;
            padding: 10px 24px; border-radius: 8px; text-decoration: none;
            font-weight: 600; font-size: 14px; margin-top: 8px; }
    .btn-danger { background: #C0392B; }
    .note-box { background: #FEF3C7; border-left: 3px solid #D4A017;
                padding: 12px 16px; border-radius: 0 8px 8px 0; margin: 16px 0;
                font-size: 13px; color: #78350F; }
    .note-box.danger { background: #FEE2E2; border-color: #C0392B; color: #7F1D1D; }
    .footer { background: #F1F5F9; padding: 20px 32px; border-radius: 0 0 12px 12px;
              text-align: center; font-size: 11px; color: #94A3B8;
              border: 1px solid #E2E8F0; border-top: none; }
    .footer a { color: #1D6FA4; text-decoration: none; }
  </style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1><?= esc($appName ?? 'Ambienta') ?></h1>
    <p>Sistema de Gerenciamento de Reservas</p>
  </div>
  <div class="body">
    <?= $emailBody ?>
  </div>
  <div class="footer">
    <p>Este é um e-mail automático. Não responda a esta mensagem.</p>
    <p style="margin-top:4px;">
      <a href="<?= esc($appUrl ?? '') ?>"><?= esc($appName ?? 'Ambienta') ?></a>
    </p>
  </div>
</div>
</body>
</html>
