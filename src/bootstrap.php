<?php
declare(strict_types=1);

/**
 * Loaded at the top of every page in /public.
 * Sets up config, error handling, session, CSRF protection and helpers.
 */

define('APP_ROOT', dirname(__DIR__));

$GLOBALS['__config'] = require APP_ROOT . '/config/config.php';

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/booking.php';
require __DIR__ . '/layout.php';
require __DIR__ . '/crud.php';
require __DIR__ . '/records.php';

date_default_timezone_set((string) config('timezone', 'UTC'));

error_reporting(E_ALL);
ini_set('display_errors', config('debug') ? '1' : '0');

set_exception_handler(function (Throwable $e): void {
    http_response_code(500);
    error_log((string) $e);
    $detail = config('debug') ? '<pre class="error-detail">' . e($e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine()) . '</pre>' : '';
    $hint = $e instanceof PDOException
        ? '<p>Could not talk to the database. Make sure MySQL is running (use <b>start.bat</b>).</p>'
        : '<p>Something went wrong on our side. Please try again.</p>';
    echo '<!doctype html><meta charset="utf-8"><title>Error</title>'
       . '<link rel="stylesheet" href="' . e(asset('css/app.css')) . '">'
       . '<div class="error-page"><div class="card"><h1>Oops</h1>' . $hint . $detail
       . '<a class="btn btn-primary" href="' . e(url()) . '">Go home</a></div></div>';
});

// ---- Session -------------------------------------------------------------
session_name('smilecare_sid');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => config('base_url') ?: '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

// Expire idle sessions after 2 hours
if (isset($_SESSION['last_seen']) && time() - $_SESSION['last_seen'] > 7200) {
    $_SESSION = [];
    session_regenerate_id(true);
}
$_SESSION['last_seen'] = time();

// ---- Security headers ----------------------------------------------------
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ---- CSRF: every POST must carry a valid token ----------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !csrf_valid($_POST['_csrf'] ?? '')) {
    http_response_code(419);
    flash('error', 'Your session expired. Please try again.');
    redirect($_SERVER['HTTP_REFERER'] ?? url());
}
