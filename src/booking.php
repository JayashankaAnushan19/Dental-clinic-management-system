<?php
declare(strict_types=1);

/** Statuses that occupy a time slot. */
const BLOCKING_STATUSES = ['pending', 'confirmed', 'completed'];

/** The clinic a dentist works at (falls back to the first active clinic). */
function dentist_clinic(int $dentistId): ?array
{
    return row(
        'SELECT c.* FROM clinics c
         LEFT JOIN dentists d ON d.clinic_id = c.id AND d.user_id = ?
         WHERE c.is_active = 1
         ORDER BY (d.user_id IS NULL), c.id LIMIT 1',
        [$dentistId]
    );
}

function active_dentists(): array
{
    return rows(
        "SELECT u.id, u.name, d.specialty, d.experience_years, d.bio, d.clinic_id, c.name AS clinic_name
         FROM users u JOIN dentists d ON d.user_id = u.id
         LEFT JOIN clinics c ON c.id = d.clinic_id
         WHERE u.role = 'dentist' AND u.is_active = 1 ORDER BY u.name"
    );
}

/**
 * Why a date cannot be booked for this clinic, or null if it can.
 */
function date_unavailable_reason(string $date, ?array $clinic): ?string
{
    if (!valid_date($date)) return 'Invalid date.';
    if (!$clinic) return 'No clinic is configured yet.';
    $today = date('Y-m-d');
    if ($date < $today) return 'That date is in the past.';
    $max = date('Y-m-d', strtotime('+' . (int) config('booking_days_ahead', 60) . ' days'));
    if ($date > $max) return 'Bookings open ' . config('booking_days_ahead') . ' days ahead.';
    $weekday = (int) date('N', strtotime($date));
    if (!in_array($weekday, array_map('intval', explode(',', $clinic['working_days'])), true)) {
        return 'The clinic is closed on ' . date('l', strtotime($date)) . 's.';
    }
    $closed = row('SELECT reason FROM closed_days WHERE closed_on = ?', [$date]);
    if ($closed) return 'The clinic is closed' . ($closed['reason'] ? ': ' . $closed['reason'] : '') . '.';
    return null;
}

/**
 * Time slots for a dentist on a date.
 * @return array{reason:?string, slots:list<array{time:string,label:string,available:bool}>}
 */
function available_slots(int $dentistId, string $date, int $duration, ?int $ignoreAppointmentId = null): array
{
    $clinic = dentist_clinic($dentistId);
    $reason = date_unavailable_reason($date, $clinic);
    if ($reason) return ['reason' => $reason, 'slots' => []];

    $busy = rows(
        'SELECT start_time, end_time FROM appointments
         WHERE dentist_id = ? AND appointment_date = ? AND status IN (' . placeholders(BLOCKING_STATUSES) . ') AND id <> ?',
        array_merge([$dentistId, $date], BLOCKING_STATUSES, [$ignoreAppointmentId ?? 0])
    );

    $step  = max(5, (int) config('slot_minutes', 30));
    $open  = strtotime("$date {$clinic['open_time']}");
    $close = strtotime("$date {$clinic['close_time']}");
    $earliest = time() + 30 * 60; // need at least 30 minutes notice

    $slots = [];
    for ($t = $open; $t + $duration * 60 <= $close; $t += $step * 60) {
        $start = date('H:i:s', $t);
        $end   = date('H:i:s', $t + $duration * 60);
        $free  = $t >= $earliest;
        foreach ($busy as $b) {
            if ($start < $b['end_time'] && $end > $b['start_time']) { $free = false; break; }
        }
        $slots[] = ['time' => substr($start, 0, 5), 'label' => date('g:i A', $t), 'available' => $free];
    }
    return ['reason' => null, 'slots' => $slots];
}

function placeholders(array $items): string
{
    return implode(',', array_fill(0, count($items), '?'));
}

/**
 * Book an appointment safely (row locks prevent double-booking).
 * @throws DomainException with a user-friendly message
 */
function book_appointment(int $patientId, int $dentistId, int $serviceId, string $date, string $time, string $notes = '', string $status = 'pending'): int
{
    $service = row('SELECT id, duration_minutes FROM services WHERE id = ? AND is_active = 1', [$serviceId]);
    if (!$service) throw new DomainException('Please choose a treatment.');
    $dentist = row("SELECT u.id FROM users u JOIN dentists d ON d.user_id = u.id WHERE u.id = ? AND u.is_active = 1", [$dentistId]);
    if (!$dentist) throw new DomainException('Please choose a dentist.');
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) throw new DomainException('Please choose a time.');

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Lock this dentist's day so two people cannot grab the same slot.
        q('SELECT id FROM appointments WHERE dentist_id = ? AND appointment_date = ? FOR UPDATE', [$dentistId, $date]);

        $result = available_slots($dentistId, $date, (int) $service['duration_minutes']);
        if ($result['reason']) throw new DomainException($result['reason']);
        $ok = false;
        foreach ($result['slots'] as $s) {
            if ($s['time'] === $time && $s['available']) { $ok = true; break; }
        }
        if (!$ok) throw new DomainException('Sorry, that time was just taken. Please pick another slot.');

        $clinic = dentist_clinic($dentistId);
        $start = "$time:00";
        $end = date('H:i:s', strtotime("$date $start") + (int) $service['duration_minutes'] * 60);
        q('INSERT INTO appointments (patient_id, dentist_id, service_id, clinic_id, appointment_date, start_time, end_time, status, patient_notes)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
          [$patientId, $dentistId, $serviceId, $clinic['id'] ?? null, $date, $start, $end, $status, $notes !== '' ? mb_substr($notes, 0, 500) : null]);
        $id = (int) $pdo->lastInsertId();
        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Load one appointment with all related names. */
function find_appointment(int $id): ?array
{
    return row(
        'SELECT a.*, p.name AS patient_name, p.email AS patient_email, p.phone AS patient_phone,
                dn.name AS dentist_name, s.name AS service_name, s.code AS service_code, s.price AS service_price,
                c.name AS clinic_name, c.address AS clinic_address, i.id AS invoice_id
         FROM appointments a
         JOIN users p  ON p.id = a.patient_id
         JOIN users dn ON dn.id = a.dentist_id
         LEFT JOIN services s ON s.id = a.service_id
         LEFT JOIN clinics c  ON c.id = a.clinic_id
         LEFT JOIN invoices i ON i.appointment_id = a.id
         WHERE a.id = ?',
        [$id]
    );
}

/** Allowed status transitions per role. */
function can_change_status(array $appt, string $to, string $role): bool
{
    $from = $appt['status'];
    if (in_array($from, ['completed', 'cancelled', 'no_show'], true)) return false;
    return match ($role) {
        'admin'   => in_array($to, ['confirmed', 'cancelled', 'no_show'], true) && $to !== $from,
        'dentist' => in_array($to, ['confirmed', 'no_show'], true) && $to !== $from,
        'patient' => $to === 'cancelled',
        default   => false,
    };
}

/**
 * Finish a visit: mark completed and issue an invoice from its treatments.
 * @throws DomainException
 */
function complete_appointment(int $appointmentId, string $visitNotes, float $discountPercent = 0): int
{
    $appt = find_appointment($appointmentId);
    if (!$appt) throw new DomainException('Appointment not found.');
    if (!in_array($appt['status'], ['pending', 'confirmed'], true)) throw new DomainException('This visit is already closed.');

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $count = (int) val('SELECT COUNT(*) FROM treatments WHERE appointment_id = ?', [$appointmentId]);
        if ($count === 0 && $appt['service_id']) {
            // No treatments recorded - bill the booked service.
            q('INSERT INTO treatments (patient_id, dentist_id, appointment_id, service_id, description, price) VALUES (?, ?, ?, ?, ?, ?)',
              [$appt['patient_id'], $appt['dentist_id'], $appointmentId, $appt['service_id'], $appt['service_name'], $appt['service_price']]);
        }
        $subtotal = (float) val('SELECT COALESCE(SUM(price), 0) FROM treatments WHERE appointment_id = ?', [$appointmentId]);
        $discountPercent = max(0, min(100, $discountPercent));
        $discount = round($subtotal * $discountPercent / 100, 2);

        q('INSERT INTO invoices (invoice_no, patient_id, appointment_id, subtotal, discount, total) VALUES (?, ?, ?, ?, ?, ?)',
          ['TMP-' . bin2hex(random_bytes(6)), $appt['patient_id'], $appointmentId, $subtotal, $discount, $subtotal - $discount]);
        $invoiceId = (int) $pdo->lastInsertId();
        q('UPDATE invoices SET invoice_no = ? WHERE id = ?', [sprintf('INV-%s-%04d', date('Y'), $invoiceId), $invoiceId]);
        q('UPDATE treatments SET invoice_id = ? WHERE appointment_id = ?', [$invoiceId, $appointmentId]);
        q("UPDATE appointments SET status = 'completed', visit_notes = ? WHERE id = ?", [$visitNotes !== '' ? $visitNotes : null, $appointmentId]);
        $pdo->commit();
        return $invoiceId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
