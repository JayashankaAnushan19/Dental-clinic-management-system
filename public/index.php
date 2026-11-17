<?php
require __DIR__ . '/../src/bootstrap.php';

$services = rows('SELECT * FROM services WHERE is_active = 1 ORDER BY code LIMIT 9');
$dentists = active_dentists();
$offers   = rows('SELECT * FROM offers WHERE is_active = 1 AND (valid_until IS NULL OR valid_until >= CURDATE()) ORDER BY discount_percent DESC LIMIT 3');
$reviews  = rows('SELECT f.*, u.name FROM feedback f JOIN users u ON u.id = f.patient_id WHERE f.is_public = 1 ORDER BY f.created_at DESC LIMIT 3');
$clinics  = rows('SELECT * FROM clinics WHERE is_active = 1 ORDER BY id');
$patientCount = (int) val("SELECT COUNT(*) FROM users WHERE role = 'patient'");
$avgRating = (float) val('SELECT COALESCE(AVG(rating), 0) FROM feedback');
$visits = (int) val("SELECT COUNT(*) FROM appointments WHERE status = 'completed'");
$bookUrl = current_user() ? (has_role('patient') ? url('patient/book.php') : home_for(current_user()['role'])) : url('register.php');

public_start('');
?>
<section class="hero">
  <div class="container hero-grid">
    <div>
      <span class="eyebrow"><?= icon('sparkles', 14) ?> Online booking in under a minute</span>
      <h1>Healthy smiles, <span class="grad">beautifully simple.</span></h1>
      <p class="lead"><?= e(config('tagline')) ?>. Book visits online, see your full treatment history and pay invoices — all in one place.</p>
      <div class="hero-cta">
        <a class="btn btn-primary btn-lg" href="<?= e($bookUrl) ?>"><?= icon('calendar-plus') ?> Book an appointment</a>
        <a class="btn btn-ghost btn-lg" href="#services">Explore services</a>
      </div>
      <div class="hero-stats">
        <div><b><?= number_format(max($patientCount, 1)) ?>+</b><span>Happy patients</span></div>
        <div><b><?= $avgRating ? number_format($avgRating, 1) : '5.0' ?>★</b><span>Average rating</span></div>
        <div><b><?= count($dentists) ?></b><span>Expert dentists</span></div>
        <div><b><?= number_format($visits) ?></b><span>Visits completed</span></div>
      </div>
    </div>
    <div class="hero-visual" aria-hidden="true">
      <div class="mock">
        <div class="card-head"><b>Book a visit</b><span class="badge badge-confirmed">Available today</span></div>
        <div class="option-grid" style="grid-template-columns:1fr 1fr">
          <?php foreach (array_slice($services, 0, 4) as $i => $s): ?>
            <div class="option"><div class="option-body" style="<?= $i === 1 ? 'border-color:var(--primary);background:var(--primary-tint)' : '' ?>">
              <div><div class="option-title"><?= e($s['name']) ?></div><div class="option-meta"><?= (int) $s['duration_minutes'] ?> min</div></div>
            </div></div>
          <?php endforeach; ?>
        </div>
        <div class="slots" style="margin-top:1rem">
          <?php foreach (['9:00 AM', '9:30 AM', '10:30 AM', '11:00 AM', '2:00 PM', '3:30 PM'] as $i => $t): ?>
            <span class="slot <?= $i === 2 ? 'selected' : '' ?>" style="display:grid;place-items:center"><?= $t ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="mock-float a"><?= icon('check') ?> Appointment confirmed</div>
      <div class="mock-float b"><span class="stars"><span class="on">★★★★★</span></span> Loved by patients</div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="grid grid-4">
      <?php foreach ([
        ['calendar', 'Book 24/7', 'Pick a dentist and a free time slot from a live calendar.'],
        ['shield', 'Safe & modern', 'Sterilised equipment and the latest painless techniques.'],
        ['clipboard', 'Your records', 'Treatment history and notes available to you any time.'],
        ['receipt', 'Clear pricing', 'Transparent prices and an invoice after every visit.'],
      ] as [$ic, $t, $d]): ?>
        <div class="feature-card"><div class="stat-icon"><?= icon($ic, 20) ?></div><h3><?= e($t) ?></h3><p><?= e($d) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section alt" id="services">
  <div class="container">
    <div class="section-head"><h2>Treatments &amp; services</h2><p>Everything from routine check-ups to complete smile makeovers.</p></div>
    <div class="grid grid-3">
      <?php foreach ($services as $s): ?>
        <div class="feature-card service-card">
          <div class="stat-icon"><?= icon('tooth', 20) ?></div>
          <h3><?= e($s['name']) ?></h3>
          <p><?= e($s['description']) ?></p>
          <div class="price"><span>from <?= e(money($s['price'])) ?></span><small><?= icon('clock', 14) ?> <?= (int) $s['duration_minutes'] ?> min</small></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" id="dentists">
  <div class="container">
    <div class="section-head"><h2>Meet our dentists</h2><p>Friendly specialists who take the time to listen.</p></div>
    <div class="grid grid-4">
      <?php foreach ($dentists as $d): ?>
        <div class="feature-card dentist-card">
          <?= avatar($d['name'], 'xl') ?>
          <h3>Dr. <?= e($d['name']) ?></h3>
          <p class="muted" style="margin-bottom:.5rem"><?= e($d['specialty']) ?></p>
          <span class="pill"><?= (int) $d['experience_years'] ?> yrs experience</span>
          <?php if ($d['bio']): ?><p style="margin-top:.8rem"><?= e($d['bio']) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($offers): ?>
<section class="section alt" id="offers">
  <div class="container">
    <div class="section-head"><h2>Current offers</h2><p>Limited-time savings on popular treatments.</p></div>
    <div class="grid grid-3">
      <?php foreach ($offers as $o): ?>
        <div class="offer-card">
          <div class="offer-off"><?= (int) $o['discount_percent'] ?>% off</div>
          <h3 style="margin-top:.75rem"><?= e($o['title']) ?></h3>
          <p><?= e($o['description']) ?></p>
          <?php if ($o['valid_until']): ?><small>Valid until <?= e(fmt_date($o['valid_until'], 'j M Y')) ?></small><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($reviews): ?>
<section class="section">
  <div class="container">
    <div class="section-head"><h2>What patients say</h2></div>
    <div class="grid grid-3">
      <?php foreach ($reviews as $r): ?>
        <div class="review-card">
          <?= stars((int) $r['rating']) ?>
          <p>“<?= e($r['message']) ?>”</p>
          <div class="person"><?= avatar($r['name'], 'sm') ?><span><?= e($r['name']) ?><small class="muted"><?= e(time_ago($r['created_at'])) ?></small></span></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section alt" id="contact">
  <div class="container">
    <div class="section-head"><h2>Visit us</h2><p>Two convenient locations.</p></div>
    <div class="grid grid-<?= max(1, min(3, count($clinics))) ?>">
      <?php foreach ($clinics as $c): ?>
        <div class="feature-card">
          <div class="contact-card"><div class="stat-icon"><?= icon('building', 20) ?></div>
            <div style="flex:1">
              <h3><?= e($c['name']) ?></h3>
              <p class="muted" style="margin:0"><?= icon('map-pin', 14) ?> <?= e($c['address']) ?></p>
              <?php if ($c['phone']): ?><p class="muted" style="margin:0"><?= icon('phone', 14) ?> <?= e($c['phone']) ?></p><?php endif; ?>
              <ul class="hours">
                <li><span><?= e(weekdays_label($c['working_days'])) ?></span><b><?= e(fmt_time($c['open_time'])) ?> – <?= e(fmt_time($c['close_time'])) ?></b></li>
              </ul>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-band">
      <div><h2>Ready for your best smile?</h2><p>Create a free account and book your first visit today.</p></div>
      <a class="btn btn-lg" href="<?= e($bookUrl) ?>"><?= icon('calendar-plus') ?> Book now</a>
    </div>
  </div>
</section>
<?php public_end();
