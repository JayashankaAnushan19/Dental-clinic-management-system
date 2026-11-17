<?php
require __DIR__ . '/../../src/bootstrap.php';

$clinicOptions = fn() => array_column(rows('SELECT id, name FROM clinics ORDER BY name'), 'name', 'id');

crud_page([
    'table' => 'staff', 'title' => 'Staff', 'singular' => 'staff member', 'active' => 'admin/staff.php', 'icon' => 'briefcase',
    'subtitle' => 'Reception, nurses, hygienists and other team members.',
    'select' => 'SELECT s.*, c.name AS clinic_name FROM staff s LEFT JOIN clinics c ON c.id = s.clinic_id',
    'order' => 'name', 'search' => ['name', 'position', 'email'],
    'fields' => [
        'name'      => ['label' => 'Full name', 'required' => true, 'max' => 120],
        'position'  => ['label' => 'Position', 'required' => true, 'max' => 80, 'help' => 'e.g. Receptionist, Dental Nurse'],
        'email'     => ['label' => 'Email', 'type' => 'email', 'max' => 160],
        'phone'     => ['label' => 'Phone', 'max' => 30],
        'clinic_id' => ['label' => 'Clinic', 'type' => 'select', 'options' => $clinicOptions],
        'joined_on' => ['label' => 'Joined on', 'type' => 'date'],
        'address'   => ['label' => 'Address', 'max' => 255, 'width' => 'full'],
    ],
    'columns' => [
        'name'        => ['Name', fn($r) => '<span class="person">' . avatar($r['name'], 'sm') . '<span>' . e($r['name']) . '<small class="muted">' . e($r['email']) . '</small></span></span>'],
        'position'    => 'Position',
        'clinic_name' => ['Clinic', fn($r) => e($r['clinic_name'] ?? '—')],
        'phone'       => 'Phone',
        'joined_on'   => ['Joined', fn($r) => e(fmt_date($r['joined_on'], 'M Y'))],
    ],
]);
