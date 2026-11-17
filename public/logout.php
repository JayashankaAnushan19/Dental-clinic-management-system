<?php
require __DIR__ . '/../src/bootstrap.php';

// Logout only via POST (CSRF-protected in bootstrap)
if (is_post()) {
    logout_user();
    session_start();
    flash('success', 'You have been signed out.');
}
redirect(url('login.php'));
