<?php
require __DIR__ . '/../../src/bootstrap.php';
$u = require_role('dentist');
handle_appointment_status_post();

$date = get('date');
if (!valid_date($date)) $date = date('Y-m-d');
$isToday = $date === date('Y-m-d');

$day = rows(appointment_select() . ' WHERE a.dentist_id = ? AND a.appointment_date = ? ORDER BY a.start_time', [$u['id'], $date]);
$active = array_filter($day, fn($a) => in_array($a['status'], ['pending', 'confirmed'], true));
$done = array_filter($day, fn($a) => $a['status'] === 'completed');
$now = date('H:i:s');
$current = null;
foreach ($active as $a) { if (!$isToday || $a['end_time'] >= $now) { $current = $a; break; } }

$weekCount = (int) val("SELECT COUNT(*) FROM appointments WHERE dentist_id = ? AND status IN ('pending','confirmed') AND appointment_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 6 DAY", [$u['id']]);
$monthPatients = (int) val("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE dentist_id = ? AND status = 'completed' AND appointment_date >= CURDATE() - INTERVAL 30 DAY", [$u['id']]);
$pendingCount = (int) val("SELECT COUNT(*) FROM appointments WHERE dentist_id = ? AND status = 'pending' AND appointment_date >= CURDATE()", [$u['id']]);

$prev = date('Y-m-d', strtotime("$date -1 day"));
$next = date('Y-m-d', strtotime("$date +1 day"));

layout_start('My schedule', 'dentist/');
?>
<div class="hero-card">
  <div class="hero-row">
    <div><h1>Hello, Dr. <?= e(strtok($u['name'], ' ')) ?></h1><p><?= $isToday ? 'You have ' . count($active) . ' patient(s) left today.' : 'Schedule for ' . e(fmt_date($date)) ?></p></div>
  </div>
  <?php if ($current): ?>
  <div class="next-appt">
    <div class="date-box"><small><?= $isToday ? 'Next' : e(date('D', strtotime($date))) ?></small><b style="font-size:1.1rem"><?= e(fmt_time($current['start_time'])) ?></b></div>
    <div style="flex:1;min-width:200px"><div style="font-weight:700;font-size:1.05rem"><?= e($current['patient_name']) ?></div><div style="opacity:.9"><?= e($current['service_name']) ?><?= $current['patient_notes'] ? ' · “' . e($current['patient_notes']) . '”' : '' ?></div></div>
    <a class="btn btn-light" href="<?= e(url('dentist/visit.php?id=' . $current['id'])) ?>"><?= icon('stethoscope', 16) ?>Start visit</a>
  </div>
  <?php endif; ?>
</div>

<div class="grid grid-4" style="margin-top:1rem">
  <?= stat_card('calendar', $isToday ? 'Today' : 'This day', (string) count($day), count($done) . ' completed', 'primary') ?>
  <?= stat_card('clock', 'Next 7 days', (string) $weekCount, 'upcoming visits', 'info') ?>
  <?= stat_card('alert', 'Awaiting confirmation', (string) $pendingCount, 'pending bookings', 'warning') ?>
  <?= stat_card('users', 'Patients (30 days)', (string) $monthPatients, 'treated', 'success') ?>
</div>

<div class="card" style="margin-top:1rem">
  <div class="card-head">
    <h2><?= $isToday ? "Today's schedule" : e(fmt_date($date)) ?></h2>
    <div class="page-actions">
      <a class="btn btn-sm btn-ghost" href="?date=<?= e($prev) ?>" aria-label="Previous day"><?= icon('chevron-left', 16) ?></a>
      <?php if (!$isToday): ?><a class="btn btn-sm btn-ghost" href="?">Today</a><?php endif; ?>
      <a class="btn btn-sm btn-ghost" href="?date=<?= e($next) ?>" aria-label="Next day"><?= icon('chevron-right', 16) ?></a>
    </div>
  </div>
  <?= $day ? appointment_table($day, 'dentist') : empty_state('calendar', 'No appointments', 'Enjoy the free time!') ?>
</div>
<?php layout_end();
