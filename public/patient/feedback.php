<?php
require __DIR__ . '/../../src/bootstrap.php';
$u = require_role('patient');

$errors = [];
if (is_post()) {
    $rating = (int) post('rating');
    $message = post('message');
    if ($rating < 1 || $rating > 5) $errors['rating'] = 'Please choose a rating.';
    if (mb_strlen($message) < 5) $errors['message'] = 'Tell us a little more (at least 5 characters).';
    if (mb_strlen($message) > 1000) $errors['message'] = 'Please keep it under 1000 characters.';
    $recent = (int) val('SELECT COUNT(*) FROM feedback WHERE patient_id = ? AND created_at > NOW() - INTERVAL 1 DAY', [$u['id']]);
    if ($recent >= 3) $errors['message'] = 'You have already sent feedback today. Thank you!';
    if (!$errors) {
        q('INSERT INTO feedback (patient_id, rating, message) VALUES (?, ?, ?)', [$u['id'], $rating, $message]);
        flash('success', 'Thank you for your feedback!');
        redirect(url('patient/feedback.php'));
    }
}

$mine = rows('SELECT * FROM feedback WHERE patient_id = ? ORDER BY created_at DESC', [$u['id']]);

layout_start('Feedback', 'patient/feedback.php');
echo page_header('Share your experience', 'Your feedback helps us improve.');
?>
<div class="grid grid-main">
  <form method="post" class="card" novalidate>
    <?= csrf_field() ?>
    <div class="field<?= isset($errors['rating']) ? ' has-error' : '' ?>">
      <span class="label">How was your visit?</span>
      <div class="rating-input">
        <?php for ($i = 5; $i >= 1; $i--): ?>
          <input type="radio" id="r<?= $i ?>" name="rating" value="<?= $i ?>"<?= checked((int) post('rating') === $i) ?>><label for="r<?= $i ?>" title="<?= $i ?> stars">★</label>
        <?php endfor; ?>
      </div>
      <?php if (isset($errors['rating'])): ?><p class="error-text"><?= e($errors['rating']) ?></p><?php endif; ?>
    </div>
    <div class="field<?= isset($errors['message']) ? ' has-error' : '' ?>">
      <label class="label" for="message">Your comments</label>
      <textarea id="message" name="message" rows="5" maxlength="1000" placeholder="What did you like? What could be better?"><?= e(post('message')) ?></textarea>
      <?php if (isset($errors['message'])): ?><p class="error-text"><?= e($errors['message']) ?></p><?php endif; ?>
    </div>
    <div class="form-actions"><button class="btn btn-primary" type="submit"><?= icon('message', 16) ?>Send feedback</button></div>
  </form>
  <div class="card">
    <div class="card-head"><h2>Your previous feedback</h2></div>
    <?php if (!$mine): ?><?= empty_state('star', 'No feedback yet') ?><?php else: ?>
      <ul class="list"><?php foreach ($mine as $f): ?>
        <li class="list-item" style="align-items:flex-start"><div class="grow"><?= stars((int) $f['rating']) ?><p class="small" style="margin:.3rem 0"><?= e($f['message']) ?></p><span class="muted small"><?= e(time_ago($f['created_at'])) ?></span></div></li>
      <?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
</div>
<?php layout_end();
