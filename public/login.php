<?php
require __DIR__ . '/../src/bootstrap.php';

if ($u = current_user()) redirect(home_for($u['role']));

$error = '';
if (is_post()) {
    $email = mb_strtolower(post('email'));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Enter your email and password.';
    } elseif (too_many_login_attempts($email)) {
        $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
    } else {
        $user = row('SELECT * FROM users WHERE email = ?', [$email]);
        if ($user && password_verify((string) $password, $user['password_hash'])) {
            if (!$user['is_active']) {
                $error = 'This account has been deactivated. Please contact the clinic.';
            } else {
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash((string) $password, PASSWORD_DEFAULT), $user['id']]);
                }
                clear_login_attempts($email);
                login_user($user);
                flash('success', 'Welcome back, ' . strtok($user['name'], ' ') . '!');
                redirect(safe_next(get('next'), home_for($user['role'])));
            }
        } else {
            record_failed_login($email);
            $error = 'Incorrect email or password.';
        }
    }
}

auth_start('Sign in');
require __DIR__ . '/../src/views/auth_side.php';
?>
<main class="auth-main">
  <div class="auth-box">
    <h1>Welcome back</h1>
    <p class="muted">Sign in to manage your appointments.</p>
    <?php if ($error): ?><div class="alert alert-error"><?= icon('alert', 16) ?><span><?= e($error) ?></span></div><?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label class="label" for="email">Email</label>
        <div class="input-icon"><?= icon('mail', 16) ?><input id="email" type="email" name="email" value="<?= e(post('email')) ?>" autocomplete="email" required autofocus></div>
      </div>
      <div class="field">
        <label class="label" for="password">Password</label>
        <div class="input-icon"><?= icon('shield', 16) ?><input id="password" type="password" name="password" autocomplete="current-password" required>
          <button type="button" class="reveal" data-reveal="password" aria-label="Show password"><?= icon('eye', 16) ?></button></div>
      </div>
      <button class="btn btn-primary btn-lg btn-block" type="submit">Sign in <?= icon('arrow-right', 16) ?></button>
    </form>
    <p class="muted" style="margin-top:1.25rem;text-align:center">New here? <a href="<?= e(url('register.php')) ?>">Create a free account</a></p>

    <?php if (config('debug')): ?>
    <div class="demo-box">
      <b>Demo accounts (click to sign in)</b>
      <button type="button" data-demo="admin@smilecare.test|Admin@123"><span>Admin</span><code>admin@smilecare.test</code></button>
      <button type="button" data-demo="dentist@smilecare.test|Dentist@123"><span>Dentist</span><code>dentist@smilecare.test</code></button>
      <button type="button" data-demo="patient@smilecare.test|Patient@123"><span>Patient</span><code>patient@smilecare.test</code></button>
    </div>
    <?php endif; ?>
  </div>
</main>
<?php auth_end();
