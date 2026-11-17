<?php
require __DIR__ . '/../../src/bootstrap.php';
require_role('admin');

$self = url('admin/patients.php');
$action = get('action');
$id = get_int('id');
$errors = [];

$fields = [
    'name'              => ['label' => 'Full name', 'required' => true, 'max' => 120],
    'email'             => ['label' => 'Email', 'type' => 'email', 'required' => true, 'max' => 160],
    'phone'             => ['label' => 'Phone', 'type' => 'tel', 'max' => 30],
    'date_of_birth'     => ['label' => 'Date of birth', 'type' => 'date'],
    'gender'            => ['label' => 'Gender', 'type' => 'select', 'options' => ['male' => 'Male', 'female' => 'Female', 'other' => 'Other']],
    'blood_group'       => ['label' => 'Blood group', 'type' => 'select', 'options' => array_combine($bg = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], $bg)],
    'address'           => ['label' => 'Address', 'max' => 255, 'width' => 'full'],
    'emergency_contact' => ['label' => 'Emergency contact', 'max' => 120],
    'allergies'         => ['label' => 'Allergies', 'max' => 255],
    'medical_notes'     => ['label' => 'Medical notes', 'type' => 'textarea'],
    'is_active'         => ['label' => 'Account active', 'type' => 'checkbox', 'default' => 1],
];

if (is_post()) {
    $id = (int) post('id');
    $email = mb_strtolower(post('email'));
    $_POST['email'] = $email;
    if (mb_strlen(post('name')) < 2) $errors['name'] = 'Enter the full name.';
    if (!valid_email($email)) $errors['email'] = 'Enter a valid email address.';
    elseif (val('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$email, $id])) $errors['email'] = 'This email is already used by another account.';
    $dob = post('date_of_birth');
    if ($dob !== '' && (!valid_date($dob) || $dob > date('Y-m-d'))) $errors['date_of_birth'] = 'Enter a valid date.';
    $gender = post('gender');
    if ($gender !== '' && !isset($fields['gender']['options'][$gender])) $errors['gender'] = 'Invalid.';
    $blood = post('blood_group');
    if ($blood !== '' && !isset($fields['blood_group']['options'][$blood])) $errors['blood_group'] = 'Invalid.';

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        $active = isset($_POST['is_active']) ? 1 : 0;
        $tempPassword = null;
        if ($id) {
            q("UPDATE users SET name = ?, email = ?, phone = ?, is_active = ? WHERE id = ? AND role = 'patient'", [post('name'), $email, post('phone') ?: null, $active, $id]);
        } else {
            $tempPassword = 'Smile' . random_int(1000, 9999) . '!';
            q("INSERT INTO users (role, name, email, phone, password_hash, is_active) VALUES ('patient', ?, ?, ?, ?, ?)",
              [post('name'), $email, post('phone') ?: null, password_hash($tempPassword, PASSWORD_DEFAULT), $active]);
            $id = (int) $pdo->lastInsertId();
        }
        q('INSERT INTO patients (user_id, date_of_birth, gender, blood_group, address, emergency_contact, allergies, medical_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
           ON DUPLICATE KEY UPDATE date_of_birth = VALUES(date_of_birth), gender = VALUES(gender), blood_group = VALUES(blood_group), address = VALUES(address),
           emergency_contact = VALUES(emergency_contact), allergies = VALUES(allergies), medical_notes = VALUES(medical_notes)',
          [$id, $dob ?: null, $gender ?: null, $blood ?: null, post('address') ?: null, post('emergency_contact') ?: null, post('allergies') ?: null, post('medical_notes') ?: null]);
        $pdo->commit();
        flash('success', $tempPassword ? "Patient added. Temporary password: $tempPassword (ask them to change it after signing in)." : 'Patient updated.');
        redirect(url('admin/patient.php?id=' . $id));
    }
    $action = $id ? 'edit' : 'new';
}

if ($action === 'new' || $action === 'edit') {
    $record = $id ? find_patient($id) : null;
    if ($id && !$record) { flash('error', 'Patient not found.'); redirect($self); }
    layout_start('Patients', 'admin/patients.php');
    echo page_header($record ? 'Edit patient' : 'New patient', $record ? '' : 'A temporary password will be generated.', '<a class="btn btn-ghost" href="' . e($record ? url('admin/patient.php?id=' . $id) : $self) . '">' . icon('chevron-left', 16) . 'Back</a>');
    echo '<form method="post" action="' . e($self) . '" class="card form-card" novalidate>' . csrf_field() . '<input type="hidden" name="id" value="' . (int) $id . '"><div class="form-grid">';
    foreach ($fields as $col => $f) echo render_field($col, $f, $record, $errors[$col] ?? null);
    echo '</div><div class="form-actions"><a class="btn btn-ghost" href="' . e($self) . '">Cancel</a><button class="btn btn-primary" type="submit">' . icon('check', 16) . 'Save patient</button></div></form>';
    layout_end();
    exit;
}

$search = get('q');
$params = [];
$filter = '';
if ($search !== '') {
    $filter = 'AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
    $params = ["%$search%", "%$search%", "%$search%"];
}
$list = rows("SELECT u.id, u.name, u.email, u.phone, u.is_active, u.created_at, p.date_of_birth, p.allergies,
                     (SELECT MAX(appointment_date) FROM appointments WHERE patient_id = u.id AND status = 'completed') AS last_visit,
                     (SELECT MIN(appointment_date) FROM appointments WHERE patient_id = u.id AND status IN ('pending','confirmed') AND appointment_date >= CURDATE()) AS next_visit,
                     (SELECT COALESCE(SUM(total),0) FROM invoices WHERE patient_id = u.id AND status = 'unpaid') AS balance
              FROM users u LEFT JOIN patients p ON p.user_id = u.id
              WHERE u.role = 'patient' $filter ORDER BY u.name LIMIT 500", $params);

layout_start('Patients', 'admin/patients.php');
echo page_header('Patients', count($list) . ' patient(s)', '<a class="btn btn-primary" href="' . e($self) . '?action=new">' . icon('plus', 16) . 'Add patient</a>');
?>
<div class="card">
  <form class="toolbar" method="get"><label class="search"><?= icon('search', 16) ?><input type="search" name="q" value="<?= e($search) ?>" placeholder="Search by name, email or phone…"></label></form>
  <?php if (!$list): ?><?= empty_state('users', 'No patients found') ?><?php else: ?>
  <div class="table-wrap"><table class="table"><thead><tr><th>Patient</th><th>Phone</th><th>Age</th><th>Last visit</th><th>Next visit</th><th class="right">Balance</th><th class="right"></th></tr></thead><tbody>
  <?php foreach ($list as $p): $a = age($p['date_of_birth']); ?>
    <tr>
      <td data-label="Patient"><a class="person" href="<?= e(url('admin/patient.php?id=' . $p['id'])) ?>"><?= avatar($p['name'], 'sm') ?><span><?= e($p['name']) ?><?= $p['is_active'] ? '' : ' <span class="badge badge-inactive">Inactive</span>' ?><small class="muted"><?= e($p['email']) ?></small></span></a>
        <?php if ($p['allergies']): ?> <span class="badge badge-pending" title="Allergies: <?= e($p['allergies']) ?>">Allergy</span><?php endif; ?></td>
      <td data-label="Phone"><?= e($p['phone'] ?: '—') ?></td>
      <td data-label="Age"><?= $a !== null ? $a : '—' ?></td>
      <td data-label="Last visit"><?= e(fmt_date($p['last_visit'], 'j M Y')) ?></td>
      <td data-label="Next visit"><?= e(fmt_date($p['next_visit'], 'j M Y')) ?></td>
      <td data-label="Balance" class="right"><?= (float) $p['balance'] > 0 ? '<b style="color:var(--danger)">' . e(money($p['balance'])) . '</b>' : '<span class="muted">—</span>' ?></td>
      <td class="right nowrap"><a class="btn btn-sm btn-ghost" href="<?= e(url('admin/book.php?patient=' . $p['id'])) ?>"><?= icon('calendar-plus', 16) ?>Book</a><a class="btn btn-sm btn-ghost" href="<?= e(url('admin/patient.php?id=' . $p['id'])) ?>">Open</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
</div>
<?php layout_end();
