<?php
require __DIR__ . '/../../src/bootstrap.php';
$u = require_role('dentist', 'admin');

$appt = find_appointment(get_int('id'));
if (!$appt || ($u['role'] === 'dentist' && (int) $appt['dentist_id'] !== (int) $u['id'])) {
    flash('error', 'Appointment not found.');
    redirect(home_for($u['role']));
}
$self = url('dentist/visit.php?id=' . $appt['id']);
$open = in_array($appt['status'], ['pending', 'confirmed'], true);
$errors = [];

if (is_post() && $open) {
    $op = post('_op');
    if ($op === 'add') {
        $service = row('SELECT * FROM services WHERE id = ?', [(int) post('service_id')]);
        $desc = post('description') ?: ($service['name'] ?? '');
        $price = post('price') !== '' ? post('price') : (string) ($service['price'] ?? '');
        if ($desc === '') $errors['description'] = 'Choose a service or enter a description.';
        if (!is_numeric($price) || (float) $price < 0) $errors['price'] = 'Enter a valid price.';
        if (!$errors) {
            q('INSERT INTO treatments (patient_id, dentist_id, appointment_id, service_id, description, tooth, notes, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
              [$appt['patient_id'], $appt['dentist_id'], $appt['id'], $service['id'] ?? null, mb_substr($desc, 0, 255), mb_substr(post('tooth'), 0, 20) ?: null, post('notes') ?: null, round((float) $price, 2)]);
            flash('success', 'Treatment added.');
            redirect($self);
        }
    } elseif ($op === 'remove') {
        q('DELETE FROM treatments WHERE id = ? AND appointment_id = ? AND invoice_id IS NULL', [(int) post('tid'), $appt['id']]);
        flash('success', 'Treatment removed.');
        redirect($self);
    } elseif ($op === 'complete') {
        try {
            $invoiceId = complete_appointment($appt['id'], post('visit_notes'), (float) post('discount'));
            flash('success', 'Visit completed and invoice issued.');
            redirect(url('invoice.php?id=' . $invoiceId));
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
            redirect($self);
        }
    }
}

$patient = find_patient((int) $appt['patient_id']);
$lines = rows('SELECT t.*, s.code FROM treatments t LEFT JOIN services s ON s.id = t.service_id WHERE t.appointment_id = ? ORDER BY t.id', [$appt['id']]);
$history = rows('SELECT t.*, s.code AS service_code, d.name AS dentist_name FROM treatments t LEFT JOIN services s ON s.id = t.service_id LEFT JOIN users d ON d.id = t.dentist_id
                 WHERE t.patient_id = ? AND (t.appointment_id IS NULL OR t.appointment_id <> ?) ORDER BY t.created_at DESC LIMIT 15', [$appt['patient_id'], $appt['id']]);
$services = rows('SELECT * FROM services WHERE is_active = 1 ORDER BY name');
$total = array_sum(array_map(fn($l) => (float) $l['price'], $lines));
$offers = rows('SELECT title, discount_percent FROM offers WHERE is_active = 1 AND (valid_until IS NULL OR valid_until >= CURDATE()) ORDER BY discount_percent DESC');

layout_start('Visit', $u['role'] === 'dentist' ? 'dentist/' : 'admin/appointments.php');
echo page_header('Visit · ' . $appt['patient_name'],
    fmt_date($appt['appointment_date']) . ' · ' . fmt_time($appt['start_time']) . ' – ' . fmt_time($appt['end_time']) . ' · ' . ($appt['service_name'] ?? ''),
    badge($appt['status']) . ' <a class="btn btn-ghost" href="' . e(home_for($u['role'])) . '">' . icon('chevron-left', 16) . 'Back</a>');
?>
<div class="grid grid-main">
  <div class="stack">
    <?php if ($appt['patient_notes']): ?><div class="alert alert-info"><?= icon('message', 16) ?><span><b>Patient says:</b> <?= e($appt['patient_notes']) ?></span></div><?php endif; ?>

    <div class="card">
      <div class="card-head"><h2>Treatments this visit</h2><b><?= e(money($total)) ?></b></div>
      <?php if ($lines): ?>
        <div class="table-wrap" style="margin-bottom:1rem"><table class="table"><thead><tr><th>Treatment</th><th>Tooth</th><th>Notes</th><th class="right">Price</th><?php if ($open): ?><th></th><?php endif; ?></tr></thead><tbody>
        <?php foreach ($lines as $l): ?>
          <tr><td data-label="Treatment"><b><?= e($l['description']) ?></b> <span class="muted small"><?= e($l['code']) ?></span></td><td data-label="Tooth"><?= e($l['tooth'] ?: '—') ?></td>
          <td data-label="Notes" class="pre-line small"><?= e($l['notes'] ?: '—') ?></td><td data-label="Price" class="right"><?= e(money($l['price'])) ?></td>
          <?php if ($open): ?><td class="right"><?= action_button($self, '', ['_op' => 'remove', 'tid' => $l['id']], 'btn btn-sm btn-ghost danger', 'Remove this treatment?', 'trash') ?></td><?php endif; ?></tr>
        <?php endforeach; ?>
        </tbody></table></div>
      <?php else: ?>
        <p class="muted">No treatments added yet. <?= $appt['service_name'] ? 'If you complete without adding any, the booked service (<b>' . e($appt['service_name']) . '</b>) will be billed.' : '' ?></p>
      <?php endif; ?>

      <?php if ($open): ?>
      <form method="post" novalidate style="border-top:1px solid var(--border);padding-top:1rem">
        <?= csrf_field() ?><input type="hidden" name="_op" value="add">
        <h3>Add treatment</h3>
        <div class="form-grid">
          <div class="field"><label class="label" for="service_id">Service</label>
            <select id="service_id" name="service_id"><option value="">Custom…</option>
              <?php foreach ($services as $s): ?><option value="<?= (int) $s['id'] ?>"<?= selected($s['id'], post('service_id', (string) $appt['service_id'])) ?>><?= e($s['code'] . ' · ' . $s['name'] . ' (' . money($s['price']) . ')') ?></option><?php endforeach; ?>
            </select></div>
          <div class="field"><label class="label" for="tooth">Tooth (FDI no.)</label><input id="tooth" name="tooth" maxlength="20" placeholder="e.g. 36" value="<?= e(post('tooth')) ?>"></div>
          <div class="field<?= isset($errors['description']) ? ' has-error' : '' ?>"><label class="label" for="description">Description</label><input id="description" name="description" maxlength="255" placeholder="Defaults to the service name" value="<?= e(post('description')) ?>">
            <?php if (isset($errors['description'])): ?><p class="error-text"><?= e($errors['description']) ?></p><?php endif; ?></div>
          <div class="field<?= isset($errors['price']) ? ' has-error' : '' ?>"><label class="label" for="price">Price</label><input id="price" type="number" step="0.01" min="0" name="price" placeholder="Defaults to service price" value="<?= e(post('price')) ?>">
            <?php if (isset($errors['price'])): ?><p class="error-text"><?= e($errors['price']) ?></p><?php endif; ?></div>
          <div class="field full"><label class="label" for="tnotes">Clinical notes</label><textarea id="tnotes" name="notes" rows="2"><?= e(post('notes')) ?></textarea></div>
        </div>
        <div class="form-actions"><button class="btn btn-soft" type="submit"><?= icon('plus', 16) ?>Add treatment</button></div>
      </form>
      <?php endif; ?>
    </div>

    <?php if ($open): ?>
    <form method="post" class="card" data-confirm="Complete this visit and issue the invoice?">
      <?= csrf_field() ?><input type="hidden" name="_op" value="complete">
      <div class="card-head"><h2>Finish visit</h2></div>
      <div class="field"><label class="label" for="visit_notes">Visit summary (visible to patient)</label>
        <textarea id="visit_notes" name="visit_notes" rows="3" placeholder="Findings, advice and follow-up…"></textarea></div>
      <div class="field" style="max-width:320px"><label class="label" for="discount">Discount</label>
        <select id="discount" name="discount"><option value="0">No discount</option>
          <?php foreach ($offers as $o): ?><option value="<?= (int) $o['discount_percent'] ?>"><?= e($o['title']) ?> (<?= (int) $o['discount_percent'] ?>%)</option><?php endforeach; ?>
        </select></div>
      <div class="form-actions"><button class="btn btn-primary btn-lg" type="submit"><?= icon('check', 18) ?>Complete &amp; issue invoice</button></div>
    </form>
    <?php elseif ($appt['visit_notes']): ?>
      <div class="card"><h2>Visit summary</h2><p class="pre-line"><?= e($appt['visit_notes']) ?></p>
      <?php if ($appt['invoice_id']): ?><a class="btn btn-ghost" href="<?= e(url('invoice.php?id=' . $appt['invoice_id'])) ?>"><?= icon('receipt', 16) ?>View invoice</a><?php endif; ?></div>
    <?php endif; ?>
  </div>

  <div class="stack">
    <?= $patient ? patient_summary($patient) : '' ?>
    <div class="card"><div class="card-head"><h3>Previous treatments</h3></div>
      <?php if (!$history): ?><p class="muted">First visit — no history.</p><?php else: ?>
      <ul class="list"><?php foreach ($history as $h): ?>
        <li class="list-item"><span class="time-chip"><?= e(date('j M', strtotime($h['created_at']))) ?><small><?= e(date('Y', strtotime($h['created_at']))) ?></small></span>
          <div class="grow"><div class="title"><?= e($h['description']) ?><?= $h['tooth'] ? ' · #' . e($h['tooth']) : '' ?></div><div class="muted small"><?= $h['dentist_name'] ? 'Dr. ' . e($h['dentist_name']) : '' ?></div></div></li>
      <?php endforeach; ?></ul>
      <?php endif; ?>
      <a class="btn btn-sm btn-ghost" style="margin-top:.75rem" href="<?= e(url(($u['role'] === 'admin' ? 'admin' : 'dentist') . '/patient.php?id=' . $appt['patient_id'])) ?>">Full record <?= icon('chevron-right', 14) ?></a>
    </div>
  </div>
</div>
<?php layout_end();
