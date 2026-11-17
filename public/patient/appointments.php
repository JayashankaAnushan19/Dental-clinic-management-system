<?php
require __DIR__ . '/../../src/bootstrap.php';
$u = require_role('patient');
handle_appointment_status_post();

$upcoming = rows(appointment_select() . " WHERE a.patient_id = ? AND a.status IN ('pending','confirmed') AND a.appointment_date >= CURDATE()
                 ORDER BY a.appointment_date, a.start_time", [$u['id']]);
$past = rows(appointment_select() . " WHERE a.patient_id = ? AND NOT (a.status IN ('pending','confirmed') AND a.appointment_date >= CURDATE())
             ORDER BY a.appointment_date DESC, a.start_time DESC LIMIT 100", [$u['id']]);

layout_start('My appointments', 'patient/appointments.php');
echo page_header('My appointments', '', '<a class="btn btn-primary" href="' . e(url('patient/book.php')) . '">' . icon('plus', 16) . 'New booking</a>');
?>
<div class="card">
  <div class="tabs" data-tabs>
    <button type="button" class="tab active" data-tab="up">Upcoming <span class="count"><?= count($upcoming) ?></span></button>
    <button type="button" class="tab" data-tab="past">Past &amp; cancelled <span class="count"><?= count($past) ?></span></button>
  </div>
  <div id="up" class="tab-panel"><?= $upcoming ? appointment_table($upcoming, 'patient') : empty_state('calendar', 'No upcoming appointments', '', '<a class="btn btn-primary" href="' . e(url('patient/book.php')) . '">Book now</a>') ?></div>
  <div id="past" class="tab-panel" hidden><?= $past ? appointment_table($past, 'patient') : empty_state('clock', 'No history yet') ?></div>
</div>
<?php layout_end();
