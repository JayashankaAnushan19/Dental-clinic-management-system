<?php
require __DIR__ . '/../../src/bootstrap.php';
require_role('admin');

$self = url('admin/dentists.php');
$action = get('action');
$id = get_int('id');
$errors = [];
$clinics = array_column(rows('SELECT id, name FROM clinics ORDER BY name'), 'name', 'id');

$fields = [
    'name'             => ['label' => 'Full name', 'required' => true, 'max' => 120, 'help' => 'Without the "Dr." prefix'],
    'email'            => ['label' => 'Login email', 'type' => 'email', 'required' => true, 'max' => 160],
    'phone'            => ['label' => 'Phone', 'type' => 'tel', 'max' => 30],
    'specialty'        => ['label' => 'Specialty', 'required' => true, 'max' => 100, 'default' => 'General Dentistry'],
    'clinic_id'        => ['label' => 'Clinic', 'type' => 'select', 'options' => $clinics],
    'employment_type'  => ['label' => 'Employment', 'type' => 'select', 'required' => true, 'options' => ['permanent' => 'Permanent', 'visiting' => 'Visiting', 'trainee' => 'Trainee']],
    'experience_years' => ['label' => 'Years of experience', 'type' => 'number', 'min' => 0, 'maxv' => 60, 'default' => 0],
    'is_active'        => ['label' => 'Active (can sign in & take bookings)', 'type' => 'checkbox', 'default' => 1],
    'bio'              => ['label' => 'Short bio (shown on website)', 'type' => 'textarea'],
];

if (is_post() && post('_op') === 'reset_password') {
    $temp = 'Dent' . random_int(1000, 9999) . '!';
    q("UPDATE users SET password_hash = ? WHERE id = ? AND role = 'dentist'", [password_hash($temp, PASSWORD_DEFAULT), (int) post('id')]);
    flash('success', "New temporary password: $temp");
    redirect($self);
}

if (is_post()) {
    $id = (int) post('id');
    $email = mb_strtolower(post('email'));
    $_POST['email'] = $email;
    if (mb_strlen(post('name')) < 2) $errors['name'] = 'Enter the full name.';
    if (!valid_email($email)) $errors['email'] = 'Enter a valid email address.';
    elseif (val('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$email, $id])) $errors['email'] = 'This email is already used by another account.';
    if (post('specialty') === '') $errors['specialty'] = 'Specialty is required.';
    $clinic = post('clinic_id');
    if ($clinic !== '' && !isset($clinics[$clinic])) $errors['clinic_id'] = 'Invalid clinic.';
    $type = post('employment_type');
    if (!isset($fields['employment_type']['options'][$type])) $errors['employment_type'] = 'Choose an option.';
    $exp = post('experience_years', '0');
    if (!ctype_digit($exp) || (int) $exp > 60) $errors['experience_years'] = 'Enter 0–60.';

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        $active = isset($_POST['is_active']) ? 1 : 0;
        $temp = null;
        if ($id) {
            q("UPDATE users SET name = ?, email = ?, phone = ?, is_active = ? WHERE id = ? AND role = 'dentist'", [post('name'), $email, post('phone') ?: null, $active, $id]);
        } else {
            $temp = 'Dent' . random_int(1000, 9999) . '!';
            q("INSERT INTO users (role, name, email, phone, password_hash, is_active) VALUES ('dentist', ?, ?, ?, ?, ?)",
              [post('name'), $email, post('phone') ?: null, password_hash($temp, PASSWORD_DEFAULT), $active]);
            $id = (int) $pdo->lastInsertId();
        }
        q('INSERT INTO dentists (user_id, clinic_id, specialty, bio, experience_years, employment_type) VALUES (?, ?, ?, ?, ?, ?)
           ON DUPLICATE KEY UPDATE clinic_id = VALUES(clinic_id), specialty = VALUES(specialty), bio = VALUES(bio),
           experience_years = VALUES(experience_years), employment_type = VALUES(employment_type)',
          [$id, $clinic ?: null, post('specialty'), post('bio') ?: null, (int) $exp, $type]);
        $pdo->commit();
        flash('success', $temp ? "Dentist added. Login: $email / temporary password: $temp" : 'Dentist updated.');
        redirect($self);
    }
    $action = $id ? 'edit' : 'new';
}

if ($action === 'new' || $action === 'edit') {
    $record = $id ? row("SELECT u.*, d.* FROM users u JOIN dentists d ON d.user_id = u.id WHERE u.id = ?", [$id]) : null;
    if ($id && !$record) { flash('error', 'Dentist not found.'); redirect($self); }
    layout_start('Dentists', 'admin/dentists.php');
    echo page_header($record ? 'Edit dentist' : 'New dentist', $record ? '' : 'A login with a temporary password will be created.', '<a class="btn btn-ghost" href="' . e($self) . '">' . icon('chevron-left', 16) . 'Back</a>');
    echo '<form method="post" action="' . e($self) . '" class="card form-card" novalidate>' . csrf_field() . '<input type="hidden" name="id" value="' . (int) $id . '"><div class="form-grid">';
    foreach ($fields as $col => $f) echo render_field($col, $f, $record, $errors[$col] ?? null);
    echo '</div><div class="form-actions"><a class="btn btn-ghost" href="' . e($self) . '">Cancel</a><button class="btn btn-primary" type="submit">' . icon('check', 16) . 'Save dentist</button></div></form>';
    layout_end();
    exit;
}

$list = rows("SELECT u.id, u.name, u.email, u.phone, u.is_active, u.last_login_at, d.specialty, d.experience_years, d.employment_type, c.name AS clinic_name,
                     (SELECT COUNT(*) FROM appointments a WHERE a.dentist_id = u.id AND a.appointment_date = CURDATE() AND a.status IN ('pending','confirmed','completed')) AS today,
                     (SELECT COUNT(*) FROM appointments a WHERE a.dentist_id = u.id AND a.status = 'completed' AND a.appointment_date >= CURDATE() - INTERVAL 30 DAY) AS month_done,
                     (SELECT COALESCE(SUM(i.total),0) FROM invoices i JOIN appointments a ON a.id = i.appointment_id WHERE a.dentist_id = u.id AND i.status = 'paid' AND i.paid_at >= CURDATE() - INTERVAL 30 DAY) AS month_rev
              FROM users u JOIN dentists d ON d.user_id = u.id LEFT JOIN clinics c ON c.id = d.clinic_id
              WHERE u.role = 'dentist' ORDER BY u.is_active DESC, u.name");

layout_start('Dentists', 'admin/dentists.php');
echo page_header('Dentists', 'Each dentist signs in to see their own schedule and record treatments.', '<a class="btn btn-primary" href="' . e($self) . '?action=new">' . icon('plus', 16) . 'Add dentist</a>');
?>
<div class="grid grid-3">
  <?php foreach ($list as $d): ?>
    <div class="card">
      <div class="patient-head" style="margin-bottom:.75rem"><?= avatar($d['name'], 'lg') ?>
        <div style="min-width:0"><h3 style="margin:0">Dr. <?= e($d['name']) ?></h3><div class="muted small"><?= e($d['specialty']) ?></div>
          <div style="margin-top:.3rem"><?= $d['is_active'] ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-inactive">Inactive</span>' ?> <span class="pill"><?= e(ucfirst($d['employment_type'])) ?></span></div></div>
      </div>
      <dl class="facts" style="grid-template-columns:1fr 1fr 1fr;margin-bottom:1rem">
        <div><dt>Today</dt><dd><?= (int) $d['today'] ?></dd></div>
        <div><dt>30-day visits</dt><dd><?= (int) $d['month_done'] ?></dd></div>
        <div><dt>30-day revenue</dt><dd class="small"><?= e(money($d['month_rev'])) ?></dd></div>
      </dl>
      <p class="muted small" style="margin:0 0 1rem"><?= icon('building', 14) ?> <?= e($d['clinic_name'] ?? 'No clinic') ?> · <?= (int) $d['experience_years'] ?> yrs<br><?= icon('mail', 14) ?> <?= e($d['email']) ?><br><?= icon('clock', 14) ?> Last sign-in: <?= e($d['last_login_at'] ? time_ago($d['last_login_at']) : 'never') ?></p>
      <div class="page-actions">
        <a class="btn btn-sm btn-ghost" href="<?= e($self) ?>?action=edit&id=<?= (int) $d['id'] ?>"><?= icon('edit', 16) ?>Edit</a>
        <a class="btn btn-sm btn-ghost" href="<?= e(url('admin/appointments.php?dentist=' . $d['id'])) ?>"><?= icon('calendar', 16) ?>Schedule</a>
        <?= action_button($self, 'Reset password', ['_op' => 'reset_password', 'id' => $d['id']], 'btn btn-sm btn-ghost', 'Generate a new temporary password?') ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php if (!$list): ?><div class="card"><?= empty_state('stethoscope', 'No dentists yet', '', '<a class="btn btn-primary" href="' . e($self) . '?action=new">Add dentist</a>') ?></div><?php endif; ?>
<?php layout_end();
