<?php
require __DIR__ . '/../../src/bootstrap.php';
$u = require_role('patient');

$bookError = '';
if (is_post()) {
    try {
        book_appointment($u['id'], (int) post('dentist_id'), (int) post('service_id'), post('date'), post('time'), post('notes'));
        flash('success', 'Booked! Your appointment is pending confirmation by the clinic.');
        redirect(url('patient/appointments.php'));
    } catch (DomainException $e) {
        $bookError = $e->getMessage();
    }
}

layout_start('Book appointment', 'patient/book.php');
echo page_header('Book an appointment', 'Choose a treatment, a dentist and a time that suits you.');
$patients = null;
require APP_ROOT . '/src/views/book_form.php';
layout_end();
