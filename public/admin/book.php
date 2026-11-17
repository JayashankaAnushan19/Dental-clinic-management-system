<?php
require __DIR__ . '/../../src/bootstrap.php';
require_role('admin');

$bookError = '';
if (is_post()) {
    $pid = (int) post('patient_id');
    if (!val("SELECT 1 FROM users WHERE id = ? AND role = 'patient'", [$pid])) {
        $bookError = 'Please choose a patient.';
    } else {
        try {
            // Bookings made by the clinic are confirmed straight away
            book_appointment($pid, (int) post('dentist_id'), (int) post('service_id'), post('date'), post('time'), post('notes'), 'confirmed');
            flash('success', 'Appointment booked and confirmed.');
            redirect(url('admin/appointments.php'));
        } catch (DomainException $e) {
            $bookError = $e->getMessage();
        }
    }
}

$patients = rows("SELECT id, name, email FROM users WHERE role = 'patient' AND is_active = 1 ORDER BY name");

layout_start('New appointment', 'admin/appointments.php');
echo page_header('New appointment', 'Book on behalf of a patient (walk-in or phone).',
    '<a class="btn btn-ghost" href="' . e(url('admin/patients.php?action=new')) . '">' . icon('plus', 16) . 'New patient</a>');
require APP_ROOT . '/src/views/book_form.php';
layout_end();
