<?php
require __DIR__ . '/../../src/bootstrap.php';

crud_page([
    'table' => 'closed_days', 'title' => 'Closed days', 'singular' => 'closed day', 'active' => 'admin/closed-days.php', 'icon' => 'calendar-x',
    'subtitle' => 'Holidays and other days when no bookings are accepted.',
    'order' => 'closed_on DESC', 'search' => ['reason'],
    'fields' => [
        'closed_on' => ['label' => 'Date', 'type' => 'date', 'required' => true],
        'reason'    => ['label' => 'Reason', 'max' => 160, 'help' => 'Shown to patients, e.g. "Public holiday"'],
    ],
    'validate' => function (array $d, int $id): array {
        if (!$id && $d['closed_on'] < date('Y-m-d')) return ['closed_on' => 'Choose today or a future date.'];
        $booked = (int) val("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND status IN ('pending','confirmed')", [$d['closed_on']]);
        if ($booked) flash('warning', "Note: $booked appointment(s) are already booked on this day. Please contact those patients.");
        return [];
    },
    'columns' => [
        'closed_on' => ['Date', fn($r) => '<b>' . e(fmt_date($r['closed_on'])) . '</b>' . ($r['closed_on'] < date('Y-m-d') ? ' <span class="badge badge-cancelled">Past</span>' : '')],
        'reason'    => ['Reason', fn($r) => e($r['reason'] ?: '—')],
    ],
]);
