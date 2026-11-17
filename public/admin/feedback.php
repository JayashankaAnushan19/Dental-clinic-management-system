<?php
require __DIR__ . '/../../src/bootstrap.php';
require_role('admin');

if (is_post()) {
    $id = (int) post('id');
    if (post('_op') === 'toggle') {
        q('UPDATE feedback SET is_public = 1 - is_public WHERE id = ?', [$id]);
        flash('success', 'Visibility updated.');
    } elseif (post('_op') === 'delete') {
        q('DELETE FROM feedback WHERE id = ?', [$id]);
        flash('success', 'Feedback deleted.');
    }
    redirect(url('admin/feedback.php'));
}

$list = rows('SELECT f.*, u.name, u.email FROM feedback f JOIN users u ON u.id = f.patient_id ORDER BY f.created_at DESC LIMIT 300');
$dist = array_fill(1, 5, 0);
foreach ($list as $f) $dist[(int) $f['rating']]++;
$total = max(1, count($list));
$avg = $list ? array_sum(array_column($list, 'rating')) / count($list) : 0;

layout_start('Feedback', 'admin/feedback.php');
echo page_header('Patient feedback', 'Publish the best reviews on the website.');
?>
<div class="grid grid-main">
  <div class="card">
    <?php if (!$list): ?><?= empty_state('message', 'No feedback yet') ?><?php else: ?>
    <ul class="list">
      <?php foreach ($list as $f): ?>
        <li class="list-item" style="align-items:flex-start">
          <?= avatar($f['name']) ?>
          <div class="grow">
            <div class="title"><?= e($f['name']) ?> <?= stars((int) $f['rating']) ?> <?= $f['is_public'] ? '<span class="badge badge-active">On website</span>' : '' ?></div>
            <p style="margin:.25rem 0"><?= e($f['message']) ?></p>
            <span class="muted small"><?= e(fmt_datetime($f['created_at'])) ?> · <?= e($f['email']) ?></span>
          </div>
          <div class="nowrap">
            <?= action_button(url('admin/feedback.php'), $f['is_public'] ? 'Hide' : 'Publish', ['_op' => 'toggle', 'id' => $f['id']], 'btn btn-sm ' . ($f['is_public'] ? 'btn-ghost' : 'btn-soft'), '', $f['is_public'] ? 'eye-off' : 'eye') ?>
            <?= action_button(url('admin/feedback.php'), '', ['_op' => 'delete', 'id' => $f['id']], 'btn btn-sm btn-ghost danger', 'Delete this feedback?', 'trash') ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
  <div class="card">
    <div class="card-head"><h3>Rating overview</h3></div>
    <div class="stat-value"><?= number_format($avg, 1) ?> <span class="stars"><span class="on">★</span></span></div>
    <p class="muted small"><?= count($list) ?> review(s)</p>
    <ul class="legend-list">
      <?php for ($i = 5; $i >= 1; $i--): ?>
        <li><span><?= $i ?> stars</span><b><?= $dist[$i] ?></b><div class="meter"><span style="width:<?= round($dist[$i] / $total * 100) ?>%;background:#f59e0b"></span></div></li>
      <?php endfor; ?>
    </ul>
  </div>
</div>
<?php layout_end();
