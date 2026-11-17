<?php
require __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!has_role('patient', 'admin')) {
    http_response_code(401);
    echo json_encode(['reason' => 'Please sign in again.', 'slots' => []]);
    exit;
}

$service = row('SELECT duration_minutes FROM services WHERE id = ? AND is_active = 1', [get_int('service')]);
$dentistId = get_int('dentist');
$date = get('date');

if (!$service || !$dentistId) {
    echo json_encode(['reason' => 'Choose a treatment and dentist.', 'slots' => []]);
    exit;
}

echo json_encode(available_slots($dentistId, $date, (int) $service['duration_minutes']));
