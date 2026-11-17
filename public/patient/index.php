<?php
require __DIR__ . '/../../src/bootstrap.php';
$u = require_role('patient');
handle_appointment_status_post();

$next = row(appointment_select() . " WHERE a.patient_id = ? AND a.status IN ('pending','confirmed') AND TIMESTAMP(a.appointment_date, a.start_time) >= NOW()
             ORDER BY a.appointment_date, a.start_time LIMIT 1", [$u['id']]);
$upcoming = rows(appointment_select() . " WHERE a.patient_id = ? AND a.status IN ('pending','confirmed') AND a.appointment_date >= CURDATE()
             ORDER BY a.appointment_date, a.start_time LIMIT 5", [$u['id']]);
$visits = (int) val("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'completed'", [$u['id']]);
$lastVisit = val("SELECT MAX(appointment_date) FROM appointments WHERE patient_id = ? AND status = 'completed'", [$u['id']]);
$unpaid = row("SELECT COUNT(*) n, COALESCE(SUM(total),0) amt FROM invoices WHERE patient_id = ? AND status = 'unpaid'", [$u['id']]);
$offers = rows('SELECT * FROM offers WHERE is_active = 1 AND (valid_until IS NULL OR valid_until >= CURDATE()) ORDER BY discount_percent DESC LIMIT 3');
$profile = row('SELECT date_of_birth, address FROM patients WHERE user_id = ?', [$u['id']]);
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

layout_start('Home', 'patient/');
?>
<div class="hero-card">
  <div class="hero-row">
    <div><h1><?= e($greet) ?>, <?= e(strtok($u['name'], ' ')) ?> 👋</h1><p>Here's what's happening with your dental care.</p></div>
    <a class="btn btn-light" href="<?= e(url('patient/book.php')) ?>"><?= icon('calendar-plus', 18) ?>Book appointment</a>
  </div>
  <?php if ($next): ?>
    <div class="next-appt">
      <div class="date-box"><small><?= e(date('M', strtotime($next['appointment_date']))) ?></small><b><?= e(date('j', strtotime($next['appointment_date']))) ?></b><small><?= e(date('D', strtotime($next['appointment_date']))) ?></small></div>
      <div style="flex:1;min-width:200px">
        <div style="font-weight:700;font-size:1.05rem">Next: <?= e($next['service_name']) ?></div>
        <div style="opacity:.9"><?= icon('clock', 14) ?> <?= e(fmt_time($next['start_time'])) ?> · Dr. <?= e($next['dentist_name']) ?> · <?= e($next['clinic_name']) ?></div>
      </div>
      <?= badge($next['status']) ?>
    </div>
  <?php else: ?>
    <div class="next-appt"><div><?= icon('calendar', 18) ?> You have no upcoming appointments. Regular check-ups every 6 months keep your smile healthy!</div></div>
  <?php endif; ?>
</div>

<?php if (!$profile || !$profile['date_of_birth']): ?>
  <div class="alert alert-info" style="margin-top:1rem"><?= icon('info', 16) ?><span>Complete your <a href="<?= e(url('profile.php')) ?>">medical profile</a> (date of birth, allergies) so your dentist can treat you safely.</span></div>
<?php endif; ?>

<div class="grid grid-3" style="margin-top:1rem">
  <?= stat_card('check', 'Completed visits', (string) $visits, $lastVisit ? 'Last: ' . fmt_date($lastVisit, 'j M Y') : 'No visits yet', 'success') ?>
  <?= stat_card('calendar', 'Upcoming', (string) count($upcoming), 'appointments booked', 'info') ?>
  <?= stat_card('wallet', 'Balance due', money($unpaid['amt']), $unpaid['n'] . ' unpaid invoice(s)', $unpaid['n'] ? 'warning' : 'primary') ?>
</div>

<div class="grid grid-main" style="margin-top:1rem">
  <div class="card">
    <div class="card-head"><h2>Upcoming appointments</h2><a class="btn btn-sm btn-ghost" href="<?= e(url('patient/appointments.php')) ?>">View all <?= icon('chevron-right', 14) ?></a></div>
    <?= $upcoming ? appointment_table($upcoming, 'patient') : empty_state('calendar', 'Nothing booked', 'Book your next check-up in a few clicks.', '<a class="btn btn-primary" href="' . e(url('patient/book.php')) . '">' . icon('plus', 16) . 'Book now</a>') ?>
  </div>
  <div class="stack">
    <?php foreach ($offers as $o): ?>
      <div class="offer-card"><div class="offer-off" style="font-size:2rem"><?= (int) $o['discount_percent'] ?>% off</div><h3 style="margin-top:.5rem"><?= e($o['title']) ?></h3><p style="margin:0"><?= e($o['description']) ?></p></div>
    <?php endforeach; ?>
  </div>
</div>
<?php layout_end();
