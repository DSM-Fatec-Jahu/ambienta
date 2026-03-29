<?php
$types = [
    'success' => ['class' => 'alert-success', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0'],
    'error'   => ['class' => 'alert-danger',  'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0'],
    'warning' => ['class' => 'alert-warning', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
    'info'    => ['class' => 'alert-info',    'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0'],
];
foreach ($types as $key => $cfg):
    $msg = session()->getFlashdata($key);
    if (!$msg) continue;
?>
<div class="<?= $cfg['class'] ?> mb-4" role="alert">
  <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $cfg['icon'] ?>"/>
  </svg>
  <span><?= esc($msg) ?></span>
</div>
<?php endforeach; ?>
<?php
// Resource allocation warnings: array of strings set by BookingsController when
// ResourceAllocationService::resolve() fails for one or more requested items (RN-R04).
$resourceWarnings = session()->getFlashdata('resource_warnings');
if (!empty($resourceWarnings) && is_array($resourceWarnings)):
?>
<div class="alert-warning mb-4" role="alert">
  <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
  </svg>
  <div>
    <strong>Atenção:</strong> Alguns recursos solicitados não puderam ser alocados:
    <ul class="list-disc list-inside mt-1 text-sm">
      <?php foreach ($resourceWarnings as $w): ?>
        <li><?= esc($w) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
<?php endif; ?>
