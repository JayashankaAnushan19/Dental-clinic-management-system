<?php
declare(strict_types=1);

/**
 * Handle appointment status change POSTs (_op=status) for the current user.
 * Call at the top of any page that renders appointment_table().
 */
function handle_appointment_status_post(): void
{
    if (!is_post() || post('_op') !== 'status') return;
    $u = current_user();
    $appt = find_appointment((int) post('id'));
    $to = post('to');
    $back = $_SERVER['HTTP_REFERER'] ?? home_for($u['role']);

    $owns = match ($u['role']) {
        'admin'   => true,
        'dentist' => $appt && (int) $appt['dentist_id'] === (int) $u['id'],
        'patient' => $appt && (int) $appt['patient_id'] === (int) $u['id'],
        default   => false,
    };
    if (!$appt || !$owns || !can_change_status($appt, $to, $u['role'])) {
        flash('error', 'That change is not allowed.');
        redirect($back);
    }
    if ($u['role'] === 'patient' && strtotime($appt['appointment_date'] . ' ' . $appt['start_time']) < time() + 2 * 3600) {
        flash('error', 'Appointments can only be cancelled online up to 2 hours before. Please call the clinic.');
        redirect($back);
    }
    $reason = mb_substr(post('reason'), 0, 255);
    q('UPDATE appointments SET status = ?, cancel_reason = ? WHERE id = ?', [$to, $to === 'cancelled' ? ($reason ?: 'Cancelled by ' . $u['role']) : $appt['cancel_reason'], $appt['id']]);
    flash('success', 'Appointment ' . strtolower(STATUS_LABELS[$to]) . '.');
    redirect($back);
}

/** Base SELECT for appointment lists. */
function appointment_select(): string
{
    return 'SELECT a.*, p.name AS patient_name, p.phone AS patient_phone, dn.name AS dentist_name,
                   s.name AS service_name, c.name AS clinic_name, i.id AS invoice_id
            FROM appointments a
            JOIN users p  ON p.id = a.patient_id
            JOIN users dn ON dn.id = a.dentist_id
            LEFT JOIN services s ON s.id = a.service_id
            LEFT JOIN clinics c  ON c.id = a.clinic_id
            LEFT JOIN invoices i ON i.appointment_id = a.id';
}

function appointment_table(array $list, string $viewer): string
{
    if (!$list) return '';
    $self = $_SERVER['REQUEST_URI'] ?? '';
    $h = '<div class="table-wrap"><table class="table"><thead><tr><th>When</th>';
    if ($viewer !== 'patient') $h .= '<th>Patient</th>';
    if ($viewer !== 'dentist') $h .= '<th>Dentist</th>';
    $h .= '<th>Treatment</th><th>Status</th><th class="right">Actions</th></tr></thead><tbody>';
    foreach ($list as $a) {
        $isToday = $a['appointment_date'] === date('Y-m-d');
        $h .= '<tr><td data-label="When"><div class="when"><span class="when-date' . ($isToday ? ' today' : '') . '">' . ($isToday ? 'Today' : e(fmt_date($a['appointment_date'], 'D, j M Y'))) . '</span>'
            . '<span class="muted small">' . e(fmt_time($a['start_time'])) . ' – ' . e(fmt_time($a['end_time'])) . '</span></div></td>';
        if ($viewer !== 'patient') {
            $link = $viewer === 'admin' ? url('admin/patient.php?id=' . $a['patient_id']) : url('dentist/patient.php?id=' . $a['patient_id']);
            $h .= '<td data-label="Patient"><a class="person" href="' . e($link) . '">' . avatar($a['patient_name'], 'sm') . '<span>' . e($a['patient_name']) . '<small class="muted">' . e($a['patient_phone']) . '</small></span></a></td>';
        }
        if ($viewer !== 'dentist') $h .= '<td data-label="Dentist">Dr. ' . e($a['dentist_name']) . '</td>';
        $h .= '<td data-label="Treatment">' . e($a['service_name'] ?? '—') . ($a['patient_notes'] ? '<div class="muted small truncate" title="' . e($a['patient_notes']) . '">“' . e($a['patient_notes']) . '”</div>' : '') . '</td>';
        $h .= '<td data-label="Status">' . badge($a['status']) . '</td><td class="right nowrap">' . appointment_actions($a, $viewer, $self) . '</td></tr>';
    }
    return $h . '</tbody></table></div>';
}

function appointment_actions(array $a, string $viewer, string $self): string
{
    $out = '';
    $open = in_array($a['status'], ['pending', 'confirmed'], true);
    if ($viewer === 'dentist' && $open) {
        $out .= '<a class="btn btn-sm btn-primary" href="' . e(url('dentist/visit.php?id=' . $a['id'])) . '">' . icon('stethoscope', 16) . 'Start visit</a>';
    }
    if ($viewer === 'admin' && $open && $a['status'] === 'pending') {
        $out .= action_button($self, 'Confirm', ['_op' => 'status', 'id' => $a['id'], 'to' => 'confirmed'], 'btn btn-sm btn-soft', '', 'check');
    }
    if ($viewer === 'admin' && $open && $a['appointment_date'] <= date('Y-m-d')) {
        $out .= '<a class="btn btn-sm btn-ghost" href="' . e(url('dentist/visit.php?id=' . $a['id'])) . '">' . icon('stethoscope', 16) . 'Visit</a>';
    }
    if ($viewer === 'dentist' && $a['status'] === 'pending') {
        $out .= action_button($self, 'Confirm', ['_op' => 'status', 'id' => $a['id'], 'to' => 'confirmed'], 'btn btn-sm btn-soft', '', 'check');
    }
    if (in_array($viewer, ['admin', 'dentist'], true) && $open && $a['appointment_date'] <= date('Y-m-d')) {
        $out .= action_button($self, 'No-show', ['_op' => 'status', 'id' => $a['id'], 'to' => 'no_show'], 'btn btn-sm btn-ghost', 'Mark this patient as a no-show?');
    }
    if (in_array($viewer, ['admin', 'patient'], true) && $open) {
        $out .= action_button($self, 'Cancel', ['_op' => 'status', 'id' => $a['id'], 'to' => 'cancelled'], 'btn btn-sm btn-ghost danger', 'Cancel this appointment?', 'x');
    }
    if ($a['invoice_id']) {
        $out .= '<a class="btn btn-sm btn-ghost" href="' . e(url('invoice.php?id=' . $a['invoice_id'])) . '">' . icon('receipt', 16) . 'Invoice</a>';
    }
    return $out ?: '<span class="muted small">—</span>';
}

function treatments_table(array $list, bool $showPatient = false): string
{
    if (!$list) return empty_state('clipboard', 'No treatments recorded yet');
    $h = '<div class="table-wrap"><table class="table"><thead><tr><th>Date</th>' . ($showPatient ? '<th>Patient</th>' : '') . '<th>Treatment</th><th>Tooth</th><th>Dentist</th><th>Notes</th><th class="right">Price</th></tr></thead><tbody>';
    foreach ($list as $t) {
        $h .= '<tr><td data-label="Date" class="nowrap">' . e(fmt_date($t['created_at'], 'j M Y')) . '</td>'
            . ($showPatient ? '<td data-label="Patient">' . e($t['patient_name']) . '</td>' : '')
            . '<td data-label="Treatment"><b>' . e($t['description']) . '</b>' . ($t['service_code'] ? ' <span class="muted small">' . e($t['service_code']) . '</span>' : '') . '</td>'
            . '<td data-label="Tooth">' . e($t['tooth'] ?: '—') . '</td>'
            . '<td data-label="Dentist">' . ($t['dentist_name'] ? 'Dr. ' . e($t['dentist_name']) : '—') . '</td>'
            . '<td data-label="Notes" class="pre-line">' . e($t['notes'] ?: '—') . '</td>'
            . '<td data-label="Price" class="right nowrap">' . e(money($t['price'])) . '</td></tr>';
    }
    return $h . '</tbody></table></div>';
}

function treatments_for_patient(int $patientId): array
{
    return rows('SELECT t.*, s.code AS service_code, d.name AS dentist_name FROM treatments t
                 LEFT JOIN services s ON s.id = t.service_id LEFT JOIN users d ON d.id = t.dentist_id
                 WHERE t.patient_id = ? ORDER BY t.created_at DESC, t.id DESC', [$patientId]);
}

function invoices_table(array $list, bool $showPatient = false, bool $adminActions = false): string
{
    if (!$list) return empty_state('receipt', 'No invoices yet');
    $self = $_SERVER['REQUEST_URI'] ?? '';
    $h = '<div class="table-wrap"><table class="table"><thead><tr><th>Invoice</th>' . ($showPatient ? '<th>Patient</th>' : '') . '<th>Issued</th><th class="right">Total</th><th>Status</th><th class="right">Actions</th></tr></thead><tbody>';
    foreach ($list as $i) {
        $h .= '<tr><td data-label="Invoice"><b>' . e($i['invoice_no']) . '</b></td>'
            . ($showPatient ? '<td data-label="Patient">' . e($i['patient_name']) . '</td>' : '')
            . '<td data-label="Issued">' . e(fmt_date($i['issued_at'], 'j M Y')) . '</td>'
            . '<td data-label="Total" class="right nowrap"><b>' . e(money($i['total'])) . '</b></td>'
            . '<td data-label="Status">' . badge($i['status']) . '</td><td class="right nowrap">'
            . '<a class="btn btn-sm btn-ghost" href="' . e(url('invoice.php?id=' . $i['id'])) . '">' . icon('eye', 16) . 'View</a>';
        if ($adminActions && $i['status'] === 'unpaid') {
            $h .= action_button($self, 'Mark paid', ['_op' => 'paid', 'id' => $i['id']], 'btn btn-sm btn-soft', '', 'check');
        }
        $h .= '</td></tr>';
    }
    return $h . '</tbody></table></div>';
}

/** Patient header card with contact and medical info. */
function patient_summary(array $p, string $editLink = ''): string
{
    $age = age($p['date_of_birth']);
    $h = '<div class="card patient-card"><div class="patient-head">' . avatar($p['name'], 'lg') . '<div><h2>' . e($p['name']) . '</h2><p class="muted">'
       . e(implode(' · ', array_filter([$age !== null ? "$age yrs" : null, $p['gender'] ? ucfirst($p['gender']) : null, $p['blood_group'] ? 'Blood ' . $p['blood_group'] : null])))
       . '</p></div>' . ($editLink ? '<a class="btn btn-sm btn-ghost ml-auto" href="' . e($editLink) . '">' . icon('edit', 16) . 'Edit</a>' : '') . '</div>';
    $h .= '<dl class="facts">'
        . '<div><dt>' . icon('mail', 14) . ' Email</dt><dd>' . e($p['email']) . '</dd></div>'
        . '<div><dt>' . icon('phone', 14) . ' Phone</dt><dd>' . e($p['phone'] ?: '—') . '</dd></div>'
        . '<div><dt>' . icon('map-pin', 14) . ' Address</dt><dd>' . e($p['address'] ?: '—') . '</dd></div>'
        . '<div><dt>' . icon('user', 14) . ' Emergency contact</dt><dd>' . e($p['emergency_contact'] ?: '—') . '</dd></div>'
        . '</dl>';
    if ($p['allergies'] || $p['medical_notes']) {
        $h .= '<div class="medical">';
        if ($p['allergies']) $h .= '<div class="alert alert-warning">' . icon('alert', 16) . '<span><b>Allergies:</b> ' . e($p['allergies']) . '</span></div>';
        if ($p['medical_notes']) $h .= '<p class="pre-line"><b>Medical notes:</b> ' . e($p['medical_notes']) . '</p>';
        $h .= '</div>';
    }
    return $h . '</div>';
}

function find_patient(int $id): ?array
{
    return row("SELECT u.id, u.name, u.email, u.phone, u.is_active, u.created_at, p.*
                FROM users u LEFT JOIN patients p ON p.user_id = u.id WHERE u.id = ? AND u.role = 'patient'", [$id]);
}

/** Tabs: appointments / treatments / invoices for a patient. */
function patient_history(int $patientId, string $viewer): string
{
    $appts = rows(appointment_select() . ' WHERE a.patient_id = ? ORDER BY a.appointment_date DESC, a.start_time DESC', [$patientId]);
    $treat = treatments_for_patient($patientId);
    $inv = rows('SELECT * FROM invoices WHERE patient_id = ? ORDER BY issued_at DESC', [$patientId]);
    $h = '<div class="card"><div class="tabs" data-tabs>'
       . '<button type="button" class="tab active" data-tab="t-appts">Appointments <span class="count">' . count($appts) . '</span></button>'
       . '<button type="button" class="tab" data-tab="t-treat">Treatments <span class="count">' . count($treat) . '</span></button>'
       . '<button type="button" class="tab" data-tab="t-inv">Invoices <span class="count">' . count($inv) . '</span></button></div>';
    $h .= '<div class="tab-panel" id="t-appts">' . ($appts ? appointment_table($appts, $viewer) : empty_state('calendar', 'No appointments yet')) . '</div>';
    $h .= '<div class="tab-panel" id="t-treat" hidden>' . treatments_table($treat) . '</div>';
    $h .= '<div class="tab-panel" id="t-inv" hidden>' . invoices_table($inv, false, $viewer === 'admin') . '</div>';
    return $h . '</div>';
}

/** Bar chart (pure CSS) from [label => value]. */
function bar_chart(array $data, string $format = 'int'): string
{
    $max = max(1, ...array_values($data ?: [0]));
    $h = '<div class="bars">';
    foreach ($data as $label => $value) {
        $pct = round($value / $max * 100);
        $shown = $format === 'money' ? money($value) : (string) $value;
        $h .= '<div class="bar-col" title="' . e($label . ': ' . $shown) . '"><span class="bar-val">' . e($format === 'money' ? number_format((float) $value / 1000, 1) . 'k' : $shown) . '</span>'
            . '<div class="bar" style="--pct:' . $pct . '%"></div><span class="bar-label">' . e($label) . '</span></div>';
    }
    return $h . '</div>';
}
