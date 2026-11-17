<?php
require __DIR__ . '/../../src/bootstrap.php';
$u = require_role('patient');

$treatments = treatments_for_patient($u['id']);
$visits = rows("SELECT a.appointment_date, a.visit_notes, d.name AS dentist_name, s.name AS service_name
                FROM appointments a JOIN users d ON d.id = a.dentist_id LEFT JOIN services s ON s.id = a.service_id
                WHERE a.patient_id = ? AND a.status = 'completed' AND a.visit_notes IS NOT NULL
                ORDER BY a.appointment_date DESC LIMIT 20", [$u['id']]);

layout_start('Treatment history', 'patient/records.php');
echo page_header('Treatment history', 'Every procedure recorded by your dentists.');
?>
<div class="grid grid-main">
  <div class="card"><div class="card-head"><h2>Treatments</h2><span class="pill"><?= count($treatments) ?> total</span></div><?= treatments_table($treatments) ?></div>
  <div class="card">
    <div class="card-head"><h2>Dentist notes</h2></div>
    <?php if (!$visits): ?><?= empty_state('file', 'No notes yet') ?><?php else: ?>
    <ul class="list">
      <?php foreach ($visits as $v): ?>
        <li class="list-item" style="align-items:flex-start">
          <span class="time-chip"><?= e(date('j M', strtotime($v['appointment_date']))) ?><small><?= e(date('Y', strtotime($v['appointment_date']))) ?></small></span>
          <div class="grow"><div class="title"><?= e($v['service_name']) ?></div><div class="muted small">Dr. <?= e($v['dentist_name']) ?></div><p class="pre-line small" style="margin:.3rem 0 0"><?= e($v['visit_notes']) ?></p></div>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
</div>
<?php layout_end();
