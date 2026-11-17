<?php
require __DIR__ . '/../../src/bootstrap.php';

crud_page([
    'table' => 'clinics', 'title' => 'Clinics', 'singular' => 'clinic', 'active' => 'admin/clinics.php', 'icon' => 'building',
    'subtitle' => 'Locations, opening hours and working days (used for booking slots).',
    'order' => 'name', 'search' => ['name', 'address'],
    'fields' => [
        'name'         => ['label' => 'Name', 'required' => true, 'max' => 120],
        'phone'        => ['label' => 'Phone', 'max' => 30],
        'address'      => ['label' => 'Address', 'required' => true, 'max' => 255, 'width' => 'full'],
        'email'        => ['label' => 'Email', 'type' => 'email', 'max' => 160],
        'rooms'        => ['label' => 'Treatment rooms', 'type' => 'number', 'required' => true, 'min' => 1, 'maxv' => 100, 'default' => 1],
        'open_time'    => ['label' => 'Opens at', 'type' => 'time', 'required' => true, 'default' => '09:00'],
        'close_time'   => ['label' => 'Closes at', 'type' => 'time', 'required' => true, 'default' => '18:00'],
        'working_days' => ['label' => 'Working days', 'type' => 'weekdays', 'default' => '1,2,3,4,5,6'],
        'is_active'    => ['label' => 'Active', 'type' => 'checkbox', 'default' => 1],
    ],
    'validate' => fn($d) => $d['open_time'] && $d['close_time'] && $d['open_time'] >= $d['close_time'] ? ['close_time' => 'Closing time must be after opening time.'] : [],
    'columns' => [
        'name'  => ['Clinic', fn($r) => '<b>' . e($r['name']) . '</b><div class="muted small">' . e($r['address']) . '</div>'],
        'phone' => 'Phone',
        'hours' => ['Hours', fn($r) => e(fmt_time($r['open_time']) . ' – ' . fmt_time($r['close_time']))],
        'working_days' => ['Days', fn($r) => e(weekdays_label($r['working_days']))],
        'rooms' => 'Rooms',
        'is_active' => ['Status', fn($r) => $r['is_active'] ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-inactive">Inactive</span>'],
    ],
]);
