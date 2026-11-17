<?php
require __DIR__ . '/../../src/bootstrap.php';
$u = require_role('admin');
handle_appointment_status_post();

$status = get('status');
$dentist = get_int('dentist');
$from = get('from');
$to = get('to');
$search = get('q');
$range = get('range', ($from || $to) ? 'custom' : 'upcoming');

$where = [];
$params = [];
if (in_array($status, ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'], true)) { $where[] = 'a.status = ?'; $params[] = $status; }
if ($dentist) { $where[] = 'a.dentist_id = ?'; $params[] = $dentist; }
if ($search !== '') { $where[] = '(p.name LIKE ? OR p.phone LIKE ? OR p.email LIKE ?)'; array_push($params, "%$search%", "%$search%", "%$search%"); }
switch ($range) {
    case 'today': $where[] = 'a.appointment_date = CURDATE()'; break;
    case 'week':  $where[] = 'a.appointment_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 6 DAY'; break;
    case 'past':  $where[] = 'a.appointment_date < CURDATE()'; break;
    case 'custom':
        if (valid_date($from)) { $where[] = 'a.appointment_date >= ?'; $params[] = $from; }
        if (valid_date($to))   { $where[] = 'a.appointment_date <= ?'; $params[] = $to; }
        break;
    case 'all': break;
    default: $range = 'upcoming'; $where[] = 'a.appointment_date >= CURDATE()';
}
$order = in_array($range, ['past', 'all'], true) ? 'a.appointment_date DESC, a.start_time DESC' : 'a.appointment_date, a.start_time';
$list = rows(appointment_select() . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY $order LIMIT 300", $params);
$dentists = active_dentists();
$pendingCount = (int) val("SELECT COUNT(*) FROM appointments WHERE status = 'pending' AND appointment_date >= CURDATE()");

layout_start('Appointments', 'admin/appointments.php');
echo page_header('Appointments', $pendingCount ? "$pendingCount booking(s) waiting for confirmation" : 'All bookings are confirmed',
    '<a class="btn btn-primary" href="' . e(url('admin/book.php')) . '">' . icon('calendar-plus', 16) . 'New appointment</a>');
?>
<div class="card">
  <form class="toolbar" method="get">
    <label class="search"><?= icon('search', 16) ?><input type="search" name="q" value="<?= e($search) ?>" placeholder="Patient name, phone…"></label>
    <select name="range" data-autosubmit aria-label="Date range">
      <?php foreach (['upcoming' => 'Upcoming', 'today' => 'Today', 'week' => 'Next 7 days', 'past' => 'Past', 'all' => 'All dates', 'custom' => 'Custom range'] as $k => $l): ?>
        <option value="<?= $k ?>"<?= selected($range, $k) ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($range === 'custom'): ?>
      <input type="date" name="from" value="<?= e($from) ?>" aria-label="From"><input type="date" name="to" value="<?= e($to) ?>" aria-label="To">
    <?php endif; ?>
    <select name="status" data-autosubmit aria-label="Status">
      <option value="">Any status</option>
      <?php foreach (['pending', 'confirmed', 'completed', 'cancelled', 'no_show'] as $s): ?><option value="<?= $s ?>"<?= selected($status, $s) ?>><?= STATUS_LABELS[$s] ?></option><?php endforeach; ?>
    </select>
    <select name="dentist" data-autosubmit aria-label="Dentist">
      <option value="">All dentists</option>
      <?php foreach ($dentists as $d): ?><option value="<?= (int) $d['id'] ?>"<?= selected($dentist, $d['id']) ?>>Dr. <?= e($d['name']) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-ghost" type="submit">Filter</button>
    <span class="muted small"><?= count($list) ?> result(s)</span>
  </form>
  <?= $list ? appointment_table($list, 'admin') : empty_state('calendar', 'No appointments match these filters') ?>
</div>
<?php layout_end();
