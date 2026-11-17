<?php
require __DIR__ . '/../../src/bootstrap.php';
require_role('admin');
handle_appointment_status_post();

$id = get_int('id');
if (is_post() && post('_op') === 'paid') {
    q("UPDATE invoices SET status = 'paid', paid_at = NOW(), payment_method = 'Cash' WHERE id = ? AND status = 'unpaid'", [(int) post('id')]);
    flash('success', 'Invoice marked as paid.');
    redirect(url('admin/patient.php?id=' . $id));
}
if (is_post() && post('_op') === 'reset_password') {
    $temp = 'Smile' . random_int(1000, 9999) . '!';
    q("UPDATE users SET password_hash = ? WHERE id = ? AND role = 'patient'", [password_hash($temp, PASSWORD_DEFAULT), $id]);
    flash('success', "New temporary password: $temp");
    redirect(url('admin/patient.php?id=' . $id));
}

$patient = find_patient($id);
if (!$patient) { flash('error', 'Patient not found.'); redirect(url('admin/patients.php')); }

$stats = row("SELECT
    (SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'completed') visits,
    (SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'no_show') no_shows,
    (SELECT COALESCE(SUM(total),0) FROM invoices WHERE patient_id = ? AND status = 'paid') paid,
    (SELECT COALESCE(SUM(total),0) FROM invoices WHERE patient_id = ? AND status = 'unpaid') due", [$id, $id, $id, $id]);

layout_start('Patient record', 'admin/patients.php');
echo page_header($patient['name'], 'Patient since ' . fmt_date($patient['created_at'], 'F Y'),
    '<a class="btn btn-ghost" href="' . e(url('admin/patients.php')) . '">' . icon('chevron-left', 16) . 'Back</a>'
  . action_button(url('admin/patient.php?id=' . $id), 'Reset password', ['_op' => 'reset_password'], 'btn btn-ghost', 'Generate a new temporary password for this patient?')
  . '<a class="btn btn-primary" href="' . e(url('admin/book.php?patient=' . $id)) . '">' . icon('calendar-plus', 16) . 'Book appointment</a>');
?>
<div class="grid grid-4" style="margin-bottom:1rem">
  <?= stat_card('check', 'Visits', (string) $stats['visits'], '', 'success') ?>
  <?= stat_card('x', 'No-shows', (string) $stats['no_shows'], '', 'warning') ?>
  <?= stat_card('wallet', 'Total paid', money($stats['paid']), '', 'info') ?>
  <?= stat_card('receipt', 'Balance due', money($stats['due']), '', (float) $stats['due'] > 0 ? 'warning' : 'primary') ?>
</div>
<div class="stack">
  <?= patient_summary($patient, url('admin/patients.php?action=edit&id=' . $id)) ?>
  <?= patient_history($id, 'admin') ?>
</div>
<?php layout_end();
