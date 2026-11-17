<aside class="auth-side">
  <a class="brand" href="<?= e(url()) ?>"><span class="brand-mark"><?= icon('tooth', 20) ?></span><span><?= e(config('app_name')) ?></span></a>
  <div>
    <h2>Your smile, managed beautifully.</h2>
    <p>Book visits in seconds, keep track of every treatment and never lose an invoice again.</p>
    <ul class="auth-points">
      <li><?= icon('check', 18) ?> Real-time appointment availability</li>
      <li><?= icon('check', 18) ?> Complete treatment history</li>
      <li><?= icon('check', 18) ?> Secure &amp; private</li>
    </ul>
  </div>
  <p class="small" style="opacity:.7">© <?= date('Y') ?> <?= e(config('app_name')) ?></p>
</aside>
