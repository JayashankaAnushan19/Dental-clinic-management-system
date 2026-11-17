<?php
require __DIR__ . '/../../src/bootstrap.php';
require_role('admin');

if (is_post() && post('_op') === 'paid') {
    q("UPDATE invoices SET status = 'paid', paid_at = NOW(), payment_method = 'Cash' WHERE id = ? AND status = 'unpaid'", [(int) post('id')]);
    flash('success', 'Invoice marked as paid (cash).');
    redirect($_SERVER['REQUEST_URI']);
}

$status = get('status');
$search = get('q');
$where = [];
$params = [];
if (in_array($status, ['paid', 'unpaid', 'void'], true)) { $where[] = 'i.status = ?'; $params[] = $status; }
if ($search !== '') { $where[] = '(i.invoice_no LIKE ? OR u.name LIKE ?)'; array_push($params, "%$search%", "%$search%"); }
$list = rows('SELECT i.*, u.name AS patient_name FROM invoices i JOIN users u ON u.id = i.patient_id'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY i.issued_at DESC LIMIT 300', $params);
$sum = row("SELECT COALESCE(SUM(CASE WHEN status='paid' AND paid_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN total END),0) month_paid,
                   COALESCE(SUM(CASE WHEN status='unpaid' THEN total END),0) due,
                   COUNT(CASE WHEN status='unpaid' THEN 1 END) due_n,
                   COALESCE(SUM(CASE WHEN status='paid' THEN total END),0) all_paid FROM invoices");

layout_start('Invoices', 'admin/invoices.php');
echo page_header('Invoices', 'Invoices are created automatically when a dentist completes a visit.');
?>
<div class="grid grid-3" style="margin-bottom:1rem">
  <?= stat_card('trend', 'Collected this month', money($sum['month_paid']), '', 'success') ?>
  <?= stat_card('alert', 'Outstanding', money($sum['due']), $sum['due_n'] . ' unpaid', 'warning') ?>
  <?= stat_card('wallet', 'Collected all time', money($sum['all_paid']), '', 'info') ?>
</div>
<div class="card">
  <form class="toolbar" method="get">
    <label class="search"><?= icon('search', 16) ?><input type="search" name="q" value="<?= e($search) ?>" placeholder="Invoice no. or patient…"></label>
    <select name="status" data-autosubmit aria-label="Status"><option value="">All</option>
      <?php foreach (['unpaid', 'paid', 'void'] as $s): ?><option value="<?= $s ?>"<?= selected($status, $s) ?>><?= STATUS_LABELS[$s] ?></option><?php endforeach; ?>
    </select>
    <span class="muted small"><?= count($list) ?> invoice(s)</span>
  </form>
  <?= invoices_table($list, true, true) ?>
</div>
<?php layout_end();
