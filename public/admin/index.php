<?php
require __DIR__ . '/../../src/bootstrap.php';
$u = require_role('admin');
handle_appointment_status_post();

$today = rows(appointment_select() . ' WHERE a.appointment_date = CURDATE() ORDER BY a.start_time');
$todayActive = count(array_filter($today, fn($a) => $a['status'] !== 'cancelled'));
$pending = (int) val("SELECT COUNT(*) FROM appointments WHERE status = 'pending' AND appointment_date >= CURDATE()");
$patients = (int) val("SELECT COUNT(*) FROM users WHERE role = 'patient'");
$newPatients = (int) val("SELECT COUNT(*) FROM users WHERE role = 'patient' AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$revMonth = (float) val("SELECT COALESCE(SUM(total), 0) FROM invoices WHERE status = 'paid' AND paid_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$revLast = (float) val("SELECT COALESCE(SUM(total), 0) FROM invoices WHERE status = 'paid' AND paid_at >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01') AND paid_at < DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$unpaid = row("SELECT COUNT(*) n, COALESCE(SUM(total), 0) amt FROM invoices WHERE status = 'unpaid'");

// Appointments per day, last 14 days
$perDay = [];
for ($i = 13; $i >= 0; $i--) $perDay[date('Y-m-d', strtotime("-$i days"))] = 0;
foreach (rows("SELECT appointment_date d, COUNT(*) n FROM appointments WHERE status <> 'cancelled' AND appointment_date >= CURDATE() - INTERVAL 13 DAY AND appointment_date <= CURDATE() GROUP BY appointment_date") as $r) {
    $perDay[$r['d']] = (int) $r['n'];
}
$apptChart = [];
foreach ($perDay as $d => $n) $apptChart[date('j M', strtotime($d))] = $n;

// Revenue, last 6 months
$rev = [];
for ($i = 5; $i >= 0; $i--) $rev[date('Y-m', strtotime(date('Y-m-01') . " -$i months"))] = 0;
foreach (rows("SELECT DATE_FORMAT(paid_at, '%Y-%m') m, SUM(total) t FROM invoices WHERE status = 'paid' AND paid_at >= DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH, '%Y-%m-01') GROUP BY m") as $r) {
    if (isset($rev[$r['m']])) $rev[$r['m']] = (float) $r['t'];
}
$revChart = [];
foreach ($rev as $m => $t) $revChart[date('M', strtotime("$m-01"))] = $t;

$topServices = rows("SELECT s.name, COUNT(*) n FROM appointments a JOIN services s ON s.id = a.service_id
                     WHERE a.status <> 'cancelled' AND a.appointment_date >= CURDATE() - INTERVAL 90 DAY GROUP BY s.id, s.name ORDER BY n DESC LIMIT 5");
$topMax = max(1, ...array_map(fn($s) => (int) $s['n'], $topServices ?: [['n' => 1]]));
$dentistLoad = rows("SELECT u.name, COUNT(a.id) n FROM users u JOIN dentists d ON d.user_id = u.id
                     LEFT JOIN appointments a ON a.dentist_id = u.id AND a.status IN ('pending','confirmed') AND a.appointment_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 6 DAY
                     WHERE u.is_active = 1 GROUP BY u.id, u.name ORDER BY n DESC");
$loadMax = max(1, ...array_map(fn($s) => (int) $s['n'], $dentistLoad ?: [['n' => 1]]));
$feedback = rows('SELECT f.*, u.name FROM feedback f JOIN users u ON u.id = f.patient_id ORDER BY f.created_at DESC LIMIT 4');
$avgRating = (float) val('SELECT COALESCE(AVG(rating), 0) FROM feedback');
$growth = $revLast > 0 ? round(($revMonth - $revLast) / $revLast * 100) : null;

layout_start('Dashboard', 'admin/');
echo page_header('Dashboard', fmt_date(date('Y-m-d'), 'l, j F Y'),
    '<a class="btn btn-ghost" href="' . e(url('admin/patients.php?action=new')) . '">' . icon('user', 16) . 'New patient</a>'
  . '<a class="btn btn-primary" href="' . e(url('admin/book.php')) . '">' . icon('calendar-plus', 16) . 'New appointment</a>');
?>
<div class="grid grid-4">
  <?= stat_card('calendar', "Today's appointments", (string) $todayActive, count(array_filter($today, fn($a) => $a['status'] === 'completed')) . ' completed', 'primary') ?>
  <?= stat_card('alert', 'Awaiting confirmation', (string) $pending, 'upcoming pending bookings', 'warning') ?>
  <?= stat_card('users', 'Patients', number_format($patients), '+' . $newPatients . ' this month', 'info') ?>
  <?= stat_card('trend', 'Revenue this month', money($revMonth), $growth === null ? 'paid invoices' : ($growth >= 0 ? '▲ ' : '▼ ') . abs($growth) . '% vs last month', 'success') ?>
</div>

<div class="grid grid-main" style="margin-top:1rem">
  <div class="card">
    <div class="card-head"><h2>Appointments · last 14 days</h2><span class="pill"><?= array_sum($apptChart) ?> total</span></div>
    <?= bar_chart($apptChart) ?>
  </div>
  <div class="card">
    <div class="card-head"><h2>Revenue · 6 months</h2></div>
    <?= bar_chart($revChart, 'money') ?>
  </div>
</div>

<div class="grid grid-main" style="margin-top:1rem">
  <div class="card">
    <div class="card-head"><h2>Today's schedule</h2><a class="btn btn-sm btn-ghost" href="<?= e(url('admin/appointments.php')) ?>">All appointments <?= icon('chevron-right', 14) ?></a></div>
    <?= $today ? appointment_table($today, 'admin') : empty_state('calendar', 'No appointments today') ?>
  </div>
  <div class="stack">
    <div class="card">
      <div class="card-head"><h3>Outstanding</h3><a class="btn btn-sm btn-ghost" href="<?= e(url('admin/invoices.php?status=unpaid')) ?>">View</a></div>
      <div class="stat-value"><?= e(money($unpaid['amt'])) ?></div><p class="muted small" style="margin:0"><?= (int) $unpaid['n'] ?> unpaid invoice(s)</p>
    </div>
    <div class="card">
      <div class="card-head"><h3>Popular treatments</h3><span class="muted small">90 days</span></div>
      <ul class="legend-list">
        <?php foreach ($topServices as $s): ?>
          <li><span><?= e($s['name']) ?></span><b><?= (int) $s['n'] ?></b><div class="meter"><span style="width:<?= round($s['n'] / $topMax * 100) ?>%"></span></div></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="card">
      <div class="card-head"><h3>Dentist workload</h3><span class="muted small">next 7 days</span></div>
      <ul class="legend-list">
        <?php foreach ($dentistLoad as $d): ?>
          <li><span>Dr. <?= e($d['name']) ?></span><b><?= (int) $d['n'] ?></b><div class="meter"><span style="width:<?= round($d['n'] / $loadMax * 100) ?>%;background:var(--accent)"></span></div></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="card">
      <div class="card-head"><h3>Latest feedback</h3><span class="pill"><?= number_format($avgRating, 1) ?> ★ avg</span></div>
      <ul class="list">
        <?php foreach ($feedback as $f): ?>
          <li class="list-item" style="align-items:flex-start"><?= avatar($f['name'], 'sm') ?><div class="grow"><div class="title small"><?= e($f['name']) ?> <?= stars((int) $f['rating']) ?></div><div class="muted small truncate" style="max-width:100%"><?= e($f['message']) ?></div></div></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
<?php layout_end();
