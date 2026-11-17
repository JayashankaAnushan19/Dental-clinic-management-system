<?php
require __DIR__ . '/../src/bootstrap.php';
$u = require_role();

$inv = row('SELECT i.*, u.name AS patient_name, u.email AS patient_email, u.phone AS patient_phone, p.address AS patient_address,
                   a.appointment_date, a.start_time, a.dentist_id, d.name AS dentist_name,
                   c.name AS clinic_name, c.address AS clinic_address, c.phone AS clinic_phone, c.email AS clinic_email
            FROM invoices i
            JOIN users u ON u.id = i.patient_id
            LEFT JOIN patients p ON p.user_id = u.id
            LEFT JOIN appointments a ON a.id = i.appointment_id
            LEFT JOIN users d ON d.id = a.dentist_id
            LEFT JOIN clinics c ON c.id = a.clinic_id
            WHERE i.id = ?', [get_int('id')]);

$allowed = $inv && match ($u['role']) {
    'admin'   => true,
    'patient' => (int) $inv['patient_id'] === (int) $u['id'],
    'dentist' => (int) $inv['dentist_id'] === (int) $u['id'],
    default   => false,
};
if (!$allowed) {
    http_response_code(404);
    layout_start('Invoice');
    echo empty_state('receipt', 'Invoice not found');
    layout_end();
    exit;
}

if (is_post() && has_role('admin')) {
    $op = post('_op');
    if ($op === 'paid' && $inv['status'] === 'unpaid') {
        $method = in_array(post('method'), ['Cash', 'Card', 'Bank transfer', 'Insurance'], true) ? post('method') : 'Cash';
        q("UPDATE invoices SET status = 'paid', paid_at = NOW(), payment_method = ? WHERE id = ?", [$method, $inv['id']]);
        flash('success', 'Invoice marked as paid.');
    } elseif ($op === 'void' && $inv['status'] === 'unpaid') {
        q("UPDATE invoices SET status = 'void' WHERE id = ?", [$inv['id']]);
        flash('success', 'Invoice voided.');
    }
    redirect(url('invoice.php?id=' . $inv['id']));
}

$items = rows('SELECT t.*, s.code FROM treatments t LEFT JOIN services s ON s.id = t.service_id WHERE t.invoice_id = ? ORDER BY t.id', [$inv['id']]);
$back = match ($u['role']) { 'admin' => url('admin/invoices.php'), 'patient' => url('patient/invoices.php'), default => url('dentist/') };

layout_start('Invoice ' . $inv['invoice_no'], $u['role'] === 'admin' ? 'admin/invoices.php' : ($u['role'] === 'patient' ? 'patient/invoices.php' : ''));
?>
<div class="page-header no-print">
  <a class="btn btn-ghost" href="<?= e($back) ?>"><?= icon('chevron-left', 16) ?>Back</a>
  <div class="page-actions">
    <?php if (has_role('admin') && $inv['status'] === 'unpaid'): ?>
      <form method="post" class="inline toolbar" style="margin:0">
        <?= csrf_field() ?><input type="hidden" name="_op" value="paid">
        <select name="method" aria-label="Payment method"><option>Cash</option><option>Card</option><option>Bank transfer</option><option>Insurance</option></select>
        <button class="btn btn-primary" type="submit"><?= icon('check', 16) ?>Mark paid</button>
      </form>
      <?= action_button(url('invoice.php?id=' . $inv['id']), 'Void', ['_op' => 'void'], 'btn btn-ghost danger', 'Void this invoice?') ?>
    <?php endif; ?>
    <button class="btn btn-ghost" type="button" onclick="window.print()"><?= icon('printer', 16) ?>Print</button>
  </div>
</div>

<div class="card invoice">
  <div class="invoice-head">
    <div>
      <div class="brand" style="padding:0;margin-bottom:.75rem"><span class="brand-mark"><?= icon('tooth', 20) ?></span><span><?= e(config('app_name')) ?></span></div>
      <p class="muted small" style="margin:0"><?= e($inv['clinic_name'] ?? '') ?><br><?= e($inv['clinic_address'] ?? '') ?><br><?= e(trim(($inv['clinic_phone'] ?? '') . '  ' . ($inv['clinic_email'] ?? ''))) ?></p>
    </div>
    <div style="text-align:right">
      <div class="muted small">INVOICE</div>
      <div class="invoice-no"><?= e($inv['invoice_no']) ?></div>
      <div style="margin:.4rem 0"><?= badge($inv['status']) ?></div>
      <div class="small muted">Issued <?= e(fmt_date($inv['issued_at'], 'j M Y')) ?></div>
      <?php if ($inv['paid_at']): ?><div class="small muted">Paid <?= e(fmt_date($inv['paid_at'], 'j M Y')) ?> · <?= e($inv['payment_method']) ?></div><?php endif; ?>
    </div>
  </div>

  <div class="invoice-parties">
    <div><h4>Billed to</h4><p><b><?= e($inv['patient_name']) ?></b><br><?= e($inv['patient_email']) ?><br><?= e($inv['patient_phone']) ?><br><?= e($inv['patient_address']) ?></p></div>
    <div><h4>Visit</h4><p><?= $inv['appointment_date'] ? e(fmt_date($inv['appointment_date'])) . ' at ' . e(fmt_time($inv['start_time'])) : '—' ?><br><?= $inv['dentist_name'] ? 'Dr. ' . e($inv['dentist_name']) : '' ?></p></div>
  </div>

  <div class="table-wrap" style="margin:0 -1.25rem">
    <table class="table">
      <thead><tr><th>Treatment</th><th>Tooth</th><th class="right">Amount</th></tr></thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr><td data-label="Treatment"><b><?= e($it['description']) ?></b> <?php if ($it['code']): ?><span class="muted small"><?= e($it['code']) ?></span><?php endif; ?>
          <?php if ($it['notes']): ?><div class="muted small"><?= e($it['notes']) ?></div><?php endif; ?></td>
          <td data-label="Tooth"><?= e($it['tooth'] ?: '—') ?></td>
          <td data-label="Amount" class="right"><?= e(money($it['price'])) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$items): ?><tr><td colspan="3" class="muted">No line items.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="totals">
    <div><span class="muted">Subtotal</span><span><?= e(money($inv['subtotal'])) ?></span></div>
    <?php if ((float) $inv['discount'] > 0): ?><div><span class="muted">Discount</span><span>− <?= e(money($inv['discount'])) ?></span></div><?php endif; ?>
    <div class="grand"><span>Total</span><span><?= e(money($inv['total'])) ?></span></div>
  </div>
  <p class="muted small" style="margin-top:2rem;text-align:center">Thank you for choosing <?= e(config('app_name')) ?>.</p>
</div>
<?php layout_end();
