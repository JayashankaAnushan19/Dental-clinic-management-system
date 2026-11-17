<?php
declare(strict_types=1);

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $user = row('SELECT id, role, name, email, phone, is_active FROM users WHERE id = ?', [$_SESSION['uid']]);
            if (!$user || !$user['is_active']) {
                $user = null;
                unset($_SESSION['uid']);
            }
        }
    }
    return $user;
}

function user_id(): int
{
    return (int) (current_user()['id'] ?? 0);
}

function has_role(string ...$roles): bool
{
    $u = current_user();
    return $u !== null && in_array($u['role'], $roles, true);
}

function home_for(string $role): string
{
    return match ($role) {
        'admin'   => url('admin/'),
        'dentist' => url('dentist/'),
        default   => url('patient/'),
    };
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $user['id'];
    q('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Redirect to login unless signed in (optionally with one of the given roles). */
function require_role(string ...$roles): array
{
    $u = current_user();
    if (!$u) {
        flash('info', 'Please sign in to continue.');
        redirect(url('login.php') . '?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
    if ($roles && !in_array($u['role'], $roles, true)) {
        http_response_code(403);
        layout_start('Access denied');
        echo empty_state('shield', 'Access denied', 'You do not have permission to view this page.',
            '<a class="btn btn-primary" href="' . e(home_for($u['role'])) . '">Go to my dashboard</a>');
        layout_end();
        exit;
    }
    return $u;
}

// ---- Brute-force protection ----------------------------------------------

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function too_many_login_attempts(string $email): bool
{
    $byEmail = (int) val('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > NOW() - INTERVAL 15 MINUTE', [$email]);
    $byIp    = (int) val('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > NOW() - INTERVAL 15 MINUTE', [client_ip()]);
    return $byEmail >= 5 || $byIp >= 20;
}

function record_failed_login(string $email): void
{
    q('INSERT INTO login_attempts (email, ip) VALUES (?, ?)', [$email, client_ip()]);
}

function clear_login_attempts(string $email): void
{
    q('DELETE FROM login_attempts WHERE email = ? OR attempted_at < NOW() - INTERVAL 1 DAY', [$email]);
}

/** Safe "next" redirect target (same app only). */
function safe_next(string $next, string $fallback): string
{
    $base = rtrim((string) config('base_url'), '/') . '/';
    return ($next !== '' && str_starts_with($next, $base) && !str_contains($next, '//', )) ? $next : $fallback;
}
