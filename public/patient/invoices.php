<?php
require __DIR__ . '/../../src/bootstrap.php';
$u = require_role('patient');

$list = rows('SELECT * FROM invoices WHERE patient_id = ? ORDER BY issued_at DESC', [$u['id']]);
$totals = row("SELECT COALESCE(SUM(CASE WHEN status='paid' THEN total END),0) paid, COALESCE(SUM(CASE WHEN status='unpaid' THEN total END),0) due FROM invoices WHERE patient_id = ?", [$u['id']]);

layout_start('Invoices', 'patient/invoices.php');
echo page_header('Invoices', 'Payments are made at the clinic reception.');
?>
<div class="grid grid-2" style="margin-bottom:1rem">
  <?= stat_card('wallet', 'Balance due', money($totals['due']), '', (float) $totals['due'] > 0 ? 'warning' : 'success') ?>
  <?= stat_card('check', 'Total paid', money($totals['paid']), '', 'success') ?>
</div>
<div class="card"><?= invoices_table($list) ?></div>
<?php layout_end();
