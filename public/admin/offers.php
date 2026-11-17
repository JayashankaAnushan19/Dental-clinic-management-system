<?php
require __DIR__ . '/../../src/bootstrap.php';

crud_page([
    'table' => 'offers', 'title' => 'Offers', 'singular' => 'offer', 'active' => 'admin/offers.php', 'icon' => 'tag',
    'subtitle' => 'Promotions shown on the website and patient dashboard. Dentists can apply them at checkout.',
    'order' => 'is_active DESC, valid_until', 'search' => ['title'],
    'fields' => [
        'title'            => ['label' => 'Title', 'required' => true, 'max' => 120],
        'discount_percent' => ['label' => 'Discount (%)', 'type' => 'number', 'required' => true, 'min' => 1, 'maxv' => 100],
        'valid_until'      => ['label' => 'Valid until', 'type' => 'date', 'help' => 'Leave empty for no end date.'],
        'is_active'        => ['label' => 'Active', 'type' => 'checkbox', 'default' => 1],
        'description'      => ['label' => 'Description', 'type' => 'textarea', 'max' => 500],
    ],
    'columns' => [
        'title' => ['Offer', fn($r) => '<b>' . e($r['title']) . '</b><div class="muted small truncate">' . e($r['description']) . '</div>'],
        'discount_percent' => ['Discount', fn($r) => '<span class="pill">' . (int) $r['discount_percent'] . '% off</span>'],
        'valid_until' => ['Valid until', fn($r) => e($r['valid_until'] ? fmt_date($r['valid_until'], 'j M Y') : 'No end date')],
        'is_active' => ['Status', fn($r) => !$r['is_active'] ? '<span class="badge badge-inactive">Inactive</span>'
            : ($r['valid_until'] && $r['valid_until'] < date('Y-m-d') ? '<span class="badge badge-cancelled">Expired</span>' : '<span class="badge badge-active">Live</span>')],
    ],
]);
