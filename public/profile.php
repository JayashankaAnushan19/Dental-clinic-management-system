<?php
require __DIR__ . '/../src/bootstrap.php';
$u = require_role();
$isPatient = $u['role'] === 'patient';
$patient = $isPatient ? (row('SELECT * FROM patients WHERE user_id = ?', [$u['id']]) ?? []) : [];
$record = array_merge($u, $patient);
$errors = [];

if (is_post() && post('_op') === 'profile') {
    $name = post('name');
    $phone = post('phone');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) $errors['name'] = 'Enter your full name.';
    if ($phone !== '' && !preg_match('/^[+\d][\d\s()-]{6,20}$/', $phone)) $errors['phone'] = 'Enter a valid phone number.';
    $dob = post('date_of_birth');
    if ($isPatient && $dob !== '' && (!valid_date($dob) || $dob > date('Y-m-d'))) $errors['date_of_birth'] = 'Enter a valid date of birth.';
    $gender = post('gender');
    if ($isPatient && $gender !== '' && !in_array($gender, ['male', 'female', 'other'], true)) $errors['gender'] = 'Invalid choice.';

    if (!$errors) {
        q('UPDATE users SET name = ?, phone = ? WHERE id = ?', [$name, $phone ?: null, $u['id']]);
        if ($isPatient) {
            q('INSERT INTO patients (user_id, date_of_birth, gender, address, blood_group, allergies, emergency_contact, medical_notes)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?)
               ON DUPLICATE KEY UPDATE date_of_birth = VALUES(date_of_birth), gender = VALUES(gender), address = VALUES(address),
               blood_group = VALUES(blood_group), allergies = VALUES(allergies), emergency_contact = VALUES(emergency_contact), medical_notes = VALUES(medical_notes)',
              [$u['id'], $dob ?: null, $gender ?: null, mb_substr(post('address'), 0, 255) ?: null, mb_substr(post('blood_group'), 0, 5) ?: null,
               mb_substr(post('allergies'), 0, 255) ?: null, mb_substr(post('emergency_contact'), 0, 120) ?: null, post('medical_notes') ?: null]);
        }
        flash('success', 'Profile saved.');
        redirect(url('profile.php'));
    }
}

if (is_post() && post('_op') === 'password') {
    $hash = val('SELECT password_hash FROM users WHERE id = ?', [$u['id']]);
    $new = (string) ($_POST['new_password'] ?? '');
    if (!password_verify((string) ($_POST['current_password'] ?? ''), $hash)) $errors['current_password'] = 'Current password is incorrect.';
    elseif ($p = password_problem($new)) $errors['new_password'] = $p;
    elseif ($new !== ($_POST['new_password_confirm'] ?? '')) $errors['new_password_confirm'] = 'Passwords do not match.';
    if (!$errors) {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        session_regenerate_id(true);
        flash('success', 'Password changed.');
        redirect(url('profile.php'));
    }
}

$fields = [
    'name'  => ['label' => 'Full name', 'required' => true, 'max' => 120],
    'phone' => ['label' => 'Phone', 'type' => 'tel', 'max' => 30],
];
if ($isPatient) {
    $fields += [
        'date_of_birth'     => ['label' => 'Date of birth', 'type' => 'date'],
        'gender'            => ['label' => 'Gender', 'type' => 'select', 'options' => ['male' => 'Male', 'female' => 'Female', 'other' => 'Other']],
        'address'           => ['label' => 'Address', 'max' => 255, 'width' => 'full'],
        'blood_group'       => ['label' => 'Blood group', 'type' => 'select', 'options' => array_combine($bg = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], $bg)],
        'emergency_contact' => ['label' => 'Emergency contact', 'max' => 120],
        'allergies'         => ['label' => 'Allergies', 'max' => 255, 'width' => 'full', 'help' => 'e.g. Penicillin, latex. Leave blank if none.'],
        'medical_notes'     => ['label' => 'Medical conditions / medication', 'type' => 'textarea'],
    ];
}

layout_start('Profile');
echo page_header('Profile & settings', 'Keep your details up to date.');
?>
<div class="grid grid-main">
  <form method="post" class="card" novalidate>
    <?= csrf_field() ?><input type="hidden" name="_op" value="profile">
    <div class="card-head"><h2>Personal details</h2></div>
    <div class="field"><span class="label">Email</span><input type="email" value="<?= e($u['email']) ?>" disabled></div>
    <div class="form-grid">
      <?php foreach ($fields as $col => $f) echo render_field($col, $f, $record, post('_op') === 'profile' ? ($errors[$col] ?? null) : null); ?>
    </div>
    <div class="form-actions"><button class="btn btn-primary" type="submit"><?= icon('check', 16) ?>Save changes</button></div>
  </form>

  <form method="post" class="card" novalidate>
    <?= csrf_field() ?><input type="hidden" name="_op" value="password">
    <div class="card-head"><h2>Change password</h2></div>
    <?php foreach (['current_password' => 'Current password', 'new_password' => 'New password', 'new_password_confirm' => 'Confirm new password'] as $k => $label): ?>
      <div class="field<?= isset($errors[$k]) ? ' has-error' : '' ?>">
        <label class="label" for="<?= $k ?>"><?= e($label) ?></label>
        <input id="<?= $k ?>" type="password" name="<?= $k ?>" autocomplete="<?= $k === 'current_password' ? 'current-password' : 'new-password' ?>">
        <?php if (isset($errors[$k])): ?><p class="error-text"><?= e($errors[$k]) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
    <p class="help">At least 8 characters with letters and numbers.</p>
    <div class="form-actions"><button class="btn btn-primary" type="submit"><?= icon('shield', 16) ?>Update password</button></div>
  </form>
</div>
<?php layout_end();
