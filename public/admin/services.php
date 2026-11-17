<?php
require __DIR__ . '/../../src/bootstrap.php';

crud_page([
    'table' => 'services', 'title' => 'Services', 'singular' => 'service', 'active' => 'admin/services.php', 'icon' => 'tooth',
    'subtitle' => 'Treatments patients can book, with prices and duration.',
    'order' => 'code', 'search' => ['code', 'name'],
    'fields' => [
        'code'             => ['label' => 'Code', 'required' => true, 'max' => 20, 'help' => 'Short unique code, e.g. D110'],
        'name'             => ['label' => 'Name', 'required' => true, 'max' => 120],
        'price'            => ['label' => 'Price (' . config('currency') . ')', 'type' => 'number', 'required' => true, 'min' => 0, 'step' => '0.01'],
        'duration_minutes' => ['label' => 'Duration (minutes)', 'type' => 'number', 'required' => true, 'min' => 5, 'maxv' => 480, 'step' => '5', 'default' => 30],
        'description'      => ['label' => 'Description', 'type' => 'textarea', 'max' => 500],
        'is_active'        => ['label' => 'Bookable online', 'type' => 'checkbox', 'default' => 1],
    ],
    'columns' => [
        'code'  => ['Code', fn($r) => '<span class="pill">' . e($r['code']) . '</span>'],
        'name'  => ['Service', fn($r) => '<b>' . e($r['name']) . '</b><div class="muted small truncate">' . e($r['description']) . '</div>'],
        'duration_minutes' => ['Duration', fn($r) => (int) $r['duration_minutes'] . ' min'],
        'price' => ['Price', fn($r) => e(money($r['price']))],
        'is_active' => ['Status', fn($r) => $r['is_active'] ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-inactive">Hidden</span>'],
    ],
]);
