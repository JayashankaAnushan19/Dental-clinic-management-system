<?php
require __DIR__ . '/../../src/bootstrap.php';
$u = require_role('dentist');

$search = get('q');
$params = [$u['id']];
$filter = '';
if ($search !== '') {
    $filter = 'AND (p.name LIKE ? OR p.email LIKE ? OR p.phone LIKE ?)';
    array_push($params, "%$search%", "%$search%", "%$search%");
}
$list = rows("SELECT p.id, p.name, p.email, p.phone, COUNT(*) AS visits,
                     MAX(CASE WHEN a.status = 'completed' THEN a.appointment_date END) AS last_visit,
                     MIN(CASE WHEN a.status IN ('pending','confirmed') AND a.appointment_date >= CURDATE() THEN a.appointment_date END) AS next_visit
              FROM appointments a JOIN users p ON p.id = a.patient_id
              WHERE a.dentist_id = ? $filter
              GROUP BY p.id, p.name, p.email, p.phone ORDER BY p.name", $params);

layout_start('My patients', 'dentist/patients.php');
echo page_header('My patients', 'Everyone who has booked with you.');
?>
<div class="card">
  <form class="toolbar" method="get"><label class="search"><?= icon('search', 16) ?><input type="search" name="q" value="<?= e($search) ?>" placeholder="Search by name, email or phone…"></label></form>
  <?php if (!$list): ?><?= empty_state('users', 'No patients found') ?><?php else: ?>
  <div class="table-wrap"><table class="table"><thead><tr><th>Patient</th><th>Phone</th><th>Appointments</th><th>Last visit</th><th>Next visit</th><th class="right"></th></tr></thead><tbody>
    <?php foreach ($list as $p): ?>
      <tr><td data-label="Patient"><a class="person" href="<?= e(url('dentist/patient.php?id=' . $p['id'])) ?>"><?= avatar($p['name'], 'sm') ?><span><?= e($p['name']) ?><small class="muted"><?= e($p['email']) ?></small></span></a></td>
      <td data-label="Phone"><?= e($p['phone'] ?: '—') ?></td><td data-label="Appointments"><?= (int) $p['visits'] ?></td>
      <td data-label="Last visit"><?= e(fmt_date($p['last_visit'], 'j M Y')) ?></td><td data-label="Next visit"><?= e(fmt_date($p['next_visit'], 'j M Y')) ?></td>
      <td class="right"><a class="btn btn-sm btn-ghost" href="<?= e(url('dentist/patient.php?id=' . $p['id'])) ?>">Open record <?= icon('chevron-right', 14) ?></a></td></tr>
    <?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
</div>
<?php layout_end();
