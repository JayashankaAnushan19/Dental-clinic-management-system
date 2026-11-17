<?php
require __DIR__ . '/../src/bootstrap.php';

if ($u = current_user()) redirect(home_for($u['role']));

$errors = [];
if (is_post()) {
    $name = post('name');
    $email = mb_strtolower(post('email'));
    $phone = post('phone');
    $password = (string) ($_POST['password'] ?? '');

    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) $errors['name'] = 'Enter your full name.';
    if (!valid_email($email)) $errors['email'] = 'Enter a valid email address.';
    if ($phone !== '' && !preg_match('/^[+\d][\d\s()-]{6,20}$/', $phone)) $errors['phone'] = 'Enter a valid phone number.';
    if ($p = password_problem($password)) $errors['password'] = $p;
    if (($_POST['password_confirm'] ?? '') !== $password) $errors['password_confirm'] = 'Passwords do not match.';
    if (empty($_POST['terms'])) $errors['terms'] = 'Please accept to continue.';

    if (!$errors && val('SELECT 1 FROM users WHERE email = ?', [$email])) {
        $errors['email'] = 'An account with this email already exists. Try signing in.';
    }

    if (!$errors) {
        db()->beginTransaction();
        q("INSERT INTO users (role, name, email, phone, password_hash) VALUES ('patient', ?, ?, ?, ?)",
          [$name, $email, $phone ?: null, password_hash($password, PASSWORD_DEFAULT)]);
        $id = (int) db()->lastInsertId();
        q('INSERT INTO patients (user_id) VALUES (?)', [$id]);
        db()->commit();
        login_user(['id' => $id]);
        flash('success', 'Welcome to ' . config('app_name') . '! Your account is ready.');
        redirect(url('patient/book.php'));
    }
}

function reg_field(string $name, string $label, string $type, array $errors, string $icon, string $auto = ''): void
{
    $val = $type === 'password' ? '' : e(post($name));
    echo '<div class="field' . (isset($errors[$name]) ? ' has-error' : '') . '"><label class="label" for="' . $name . '">' . e($label) . '</label>'
       . '<div class="input-icon">' . icon($icon, 16) . '<input id="' . $name . '" type="' . $type . '" name="' . $name . '" value="' . $val . '"' . ($auto ? ' autocomplete="' . $auto . '"' : '') . '></div>'
       . (isset($errors[$name]) ? '<p class="error-text">' . e($errors[$name]) . '</p>' : '') . '</div>';
}

auth_start('Create account');
require __DIR__ . '/../src/views/auth_side.php';
?>
<main class="auth-main">
  <div class="auth-box">
    <h1>Create your account</h1>
    <p class="muted">It takes less than a minute.</p>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <?php reg_field('name', 'Full name', 'text', $errors, 'user', 'name'); ?>
      <?php reg_field('email', 'Email', 'email', $errors, 'mail', 'email'); ?>
      <?php reg_field('phone', 'Phone (optional)', 'tel', $errors, 'phone', 'tel'); ?>
      <?php reg_field('password', 'Password', 'password', $errors, 'shield', 'new-password'); ?>
      <?php reg_field('password_confirm', 'Confirm password', 'password', $errors, 'shield', 'new-password'); ?>
      <p class="help" style="margin-top:-.6rem;margin-bottom:1rem">At least 8 characters with letters and numbers.</p>
      <div class="field<?= isset($errors['terms']) ? ' has-error' : '' ?>">
        <label class="switch" style="margin-top:0"><input type="checkbox" name="terms" value="1"<?= checked(!empty($_POST['terms'])) ?>><span class="switch-ui"></span>I agree to the clinic's privacy policy</label>
        <?php if (isset($errors['terms'])): ?><p class="error-text"><?= e($errors['terms']) ?></p><?php endif; ?>
      </div>
      <button class="btn btn-primary btn-lg btn-block" type="submit">Create account <?= icon('arrow-right', 16) ?></button>
    </form>
    <p class="muted" style="margin-top:1.25rem;text-align:center">Already registered? <a href="<?= e(url('login.php')) ?>">Sign in</a></p>
  </div>
</main>
<?php auth_end();
