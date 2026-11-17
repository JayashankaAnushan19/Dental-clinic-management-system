<?php
require __DIR__ . '/../../src/bootstrap.php';
$u = require_role('dentist');
handle_appointment_status_post();

$status = get('status');
$range = get('range', 'upcoming');
$where = ['a.dentist_id = ?'];
$params = [$u['id']];
if (isset(STATUS_LABELS[$status]) && !in_array($status, ['paid', 'unpaid', 'void'], true)) { $where[] = 'a.status = ?'; $params[] = $status; }
$where[] = match ($range) {
    'past' => 'a.appointment_date < CURDATE()',
    'all'  => '1=1',
    default => 'a.appointment_date >= CURDATE()',
};
$order = $range === 'upcoming' ? 'a.appointment_date, a.start_time' : 'a.appointment_date DESC, a.start_time DESC';
$list = rows(appointment_select() . ' WHERE ' . implode(' AND ', $where) . " ORDER BY $order LIMIT 300", $params);

layout_start('Appointments', 'dentist/appointments.php');
echo page_header('All appointments');
?>
<div class="card">
  <form class="toolbar" method="get">
    <select name="range" data-autosubmit aria-label="Range">
      <option value="upcoming"<?= selected($range, 'upcoming') ?>>Upcoming</option>
      <option value="past"<?= selected($range, 'past') ?>>Past</option>
      <option value="all"<?= selected($range, 'all') ?>>All</option>
    </select>
    <select name="status" data-autosubmit aria-label="Status">
      <option value="">Any status</option>
      <?php foreach (['pending', 'confirmed', 'completed', 'cancelled', 'no_show'] as $s): ?><option value="<?= $s ?>"<?= selected($status, $s) ?>><?= STATUS_LABELS[$s] ?></option><?php endforeach; ?>
    </select>
    <span class="muted small"><?= count($list) ?> result(s)</span>
  </form>
  <?= $list ? appointment_table($list, 'dentist') : empty_state('calendar', 'No appointments match') ?>
</div>
<?php layout_end();
