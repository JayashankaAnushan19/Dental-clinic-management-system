<?php
require __DIR__ . '/../../src/bootstrap.php';
$u = require_role('dentist');
handle_appointment_status_post();

$id = get_int('id');
// Dentists may only open records of patients who booked with them
$linked = (bool) val('SELECT 1 FROM appointments WHERE patient_id = ? AND dentist_id = ? LIMIT 1', [$id, $u['id']]);
$patient = $linked ? find_patient($id) : null;
if (!$patient) {
    flash('error', 'Patient not found.');
    redirect(url('dentist/patients.php'));
}

layout_start('Patient record', 'dentist/patients.php');
echo page_header('Patient record', '', '<a class="btn btn-ghost" href="' . e(url('dentist/patients.php')) . '">' . icon('chevron-left', 16) . 'Back</a>');
echo '<div class="stack">' . patient_summary($patient) . patient_history($id, 'dentist') . '</div>';
layout_end();
