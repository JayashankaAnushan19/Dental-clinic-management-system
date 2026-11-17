<?php
/**
 * Booking wizard form. Expects:
 *   $bookError (string), $patients (array|null - admin only: list for patient picker)
 */
$services = rows('SELECT * FROM services WHERE is_active = 1 ORDER BY name');
$dentists = active_dentists();
$closedDays = array_column(rows('SELECT closed_on FROM closed_days WHERE closed_on >= CURDATE()'), 'closed_on');
$clinicDays = [];
foreach ($dentists as $d) {
    $c = dentist_clinic((int) $d['id']);
    $clinicDays[$d['id']] = $c['working_days'] ?? '1,2,3,4,5,6';
}
$selService = (int) post('service_id', (string) get_int('service'));
$selDentist = (int) post('dentist_id', (string) get_int('dentist'));
?>
<form method="post" class="grid grid-main" data-booking data-slots-url="<?= e(url('api/slots.php')) ?>"
      data-closed='<?= e(json_encode($closedDays)) ?>' data-max-days="<?= (int) config('booking_days_ahead', 60) ?>" data-time="<?= e(post('time')) ?>" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="date" value="<?= e(post('date')) ?>">
  <input type="hidden" name="time" value="">

  <div class="card">
    <?php if (!empty($bookError)): ?><div class="alert alert-error"><?= icon('alert', 16) ?><span><?= e($bookError) ?></span></div><?php endif; ?>

    <?php if (!empty($patients)): ?>
    <div class="book-section">
      <h3><span class="step-num">0</span> Patient</h3>
      <select name="patient_id" required>
        <option value="">Choose a patient…</option>
        <?php foreach ($patients as $p): ?>
          <option value="<?= (int) $p['id'] ?>"<?= selected($p['id'], post('patient_id', (string) get_int('patient'))) ?>><?= e($p['name']) ?> — <?= e($p['email']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>

    <div class="book-section">
      <h3><span class="step-num">1</span> Choose a treatment</h3>
      <div class="option-grid">
        <?php foreach ($services as $s): ?>
          <label class="option">
            <input type="radio" name="service_id" value="<?= (int) $s['id'] ?>" data-name="<?= e($s['name']) ?>" data-price="<?= e(money($s['price'])) ?>" data-duration="<?= (int) $s['duration_minutes'] ?>"<?= checked($selService === (int) $s['id']) ?>>
            <span class="option-body">
              <span><span class="option-title"><?= e($s['name']) ?></span><br><span class="option-meta"><?= icon('clock', 12) ?> <?= (int) $s['duration_minutes'] ?> min</span></span>
              <span class="option-price"><?= e(money($s['price'])) ?></span>
            </span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="book-section">
      <h3><span class="step-num">2</span> Choose a dentist</h3>
      <div class="option-grid">
        <?php foreach ($dentists as $d): ?>
          <label class="option">
            <input type="radio" name="dentist_id" value="<?= (int) $d['id'] ?>" data-name="Dr. <?= e($d['name']) ?>" data-days="<?= e($clinicDays[$d['id']]) ?>"<?= checked($selDentist === (int) $d['id']) ?>>
            <span class="option-body"><?= avatar($d['name']) ?>
              <span><span class="option-title">Dr. <?= e($d['name']) ?></span><br><span class="option-meta"><?= e($d['specialty']) ?><?= $d['clinic_name'] ? ' · ' . e($d['clinic_name']) : '' ?></span></span>
            </span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="grid grid-2">
      <div class="book-section">
        <h3><span class="step-num">3</span> Pick a date</h3>
        <div class="calendar" data-calendar></div>
      </div>
      <div class="book-section">
        <h3><span class="step-num">4</span> Pick a time</h3>
        <div data-slots><p class="muted">Pick a date to see available times.</p></div>
      </div>
    </div>

    <div class="field">
      <label class="label" for="notes">Anything we should know? (optional)</label>
      <textarea id="notes" name="notes" rows="3" maxlength="500" placeholder="e.g. tooth pain on the lower right, nervous patient…"><?= e(post('notes')) ?></textarea>
    </div>
  </div>

  <aside class="card summary-box">
    <div class="card-head"><h3>Booking summary</h3></div>
    <div class="summary-row"><span>Treatment</span><span data-sum="service">—</span></div>
    <div class="summary-row"><span>Duration</span><span data-sum="duration">—</span></div>
    <div class="summary-row"><span>Dentist</span><span data-sum="dentist">—</span></div>
    <div class="summary-row"><span>Date</span><span data-sum="date">—</span></div>
    <div class="summary-row"><span>Time</span><span data-sum="time">—</span></div>
    <div class="summary-row"><span>Estimated price</span><span data-sum="price">—</span></div>
    <button class="btn btn-primary btn-lg btn-block" style="margin-top:1.25rem" type="submit" data-submit disabled><?= icon('check', 18) ?>Confirm booking</button>
    <p class="help" style="text-align:center">You can cancel online up to 2 hours before.</p>
  </aside>
</form>
