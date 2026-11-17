<?php
declare(strict_types=1);

function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['__config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/** HTML-escape. Use for every value printed into a page. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim((string) config('base_url'), '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = APP_ROOT . '/public/assets/' . $path;
    $v = is_file($file) ? filemtime($file) : 1;
    return url('assets/' . $path) . '?v=' . $v;
}

function redirect(string $to): never
{
    if (!preg_match('~^(https?:)?//~', $to) && !str_starts_with($to, '/')) {
        $to = url($to);
    }
    header('Location: ' . $to);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Trimmed POST string. */
function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

/** Trimmed GET string. */
function get(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function get_int(string $key, int $default = 0): int
{
    return filter_var($_GET[$key] ?? null, FILTER_VALIDATE_INT) ?: $default;
}

/** Form value: submitted value first, then the record, then default. */
function v(?array $record, string $key, mixed $default = ''): string
{
    if (is_post() && array_key_exists($key, $_POST) && is_string($_POST[$key])) {
        return $_POST[$key];
    }
    return (string) ($record[$key] ?? $default);
}

function selected(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}

function checked(mixed $cond): string
{
    return $cond ? ' checked' : '';
}

// ---- Flash messages -------------------------------------------------------

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---- CSRF -----------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(mixed $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

// ---- Formatting -----------------------------------------------------------

function money(mixed $amount): string
{
    return config('currency', '$') . ' ' . number_format((float) $amount, 2);
}

function fmt_date(?string $date, string $format = 'D, j M Y'): string
{
    return $date ? date($format, strtotime($date)) : '—';
}

function fmt_time(?string $time): string
{
    return $time ? date('g:i A', strtotime($time)) : '';
}

function fmt_datetime(?string $dt): string
{
    return $dt ? date('j M Y, g:i A', strtotime($dt)) : '—';
}

function time_ago(?string $dt): string
{
    if (!$dt) return '—';
    $diff = time() - strtotime($dt);
    return match (true) {
        $diff < 60     => 'just now',
        $diff < 3600   => floor($diff / 60) . ' min ago',
        $diff < 86400  => floor($diff / 3600) . ' h ago',
        $diff < 604800 => floor($diff / 86400) . ' d ago',
        default        => fmt_date($dt, 'j M Y'),
    };
}

function age(?string $dob): ?int
{
    return $dob ? (int) date_diff(date_create($dob), date_create('today'))->y : null;
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $i = mb_substr($parts[0] ?? '', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '');
    return mb_strtoupper($i ?: '?');
}

function avatar(string $name, string $size = ''): string
{
    $hue = crc32($name) % 360;
    return '<span class="avatar ' . e($size) . '" style="--h:' . $hue . '">' . e(initials($name)) . '</span>';
}

const STATUS_LABELS = [
    'pending'   => 'Pending',
    'confirmed' => 'Confirmed',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
    'no_show'   => 'No-show',
    'unpaid'    => 'Unpaid',
    'paid'      => 'Paid',
    'void'      => 'Void',
];

function badge(string $status): string
{
    return '<span class="badge badge-' . e($status) . '">' . e(STATUS_LABELS[$status] ?? ucfirst($status)) . '</span>';
}

function stars(int $rating): string
{
    $out = '<span class="stars" aria-label="' . $rating . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) {
        $out .= '<span class="' . ($i <= $rating ? 'on' : '') . '">★</span>';
    }
    return $out . '</span>';
}

const WEEKDAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

function weekdays_label(string $days): string
{
    $list = array_filter(array_map('intval', explode(',', $days)));
    return implode(', ', array_map(fn($d) => WEEKDAYS[$d] ?? '', $list));
}

function valid_date(string $d): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt !== false && $dt->format('Y-m-d') === $d;
}

function valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/** Returns an error message, or null when the password is strong enough. */
function password_problem(string $pw): ?string
{
    if (strlen($pw) < 8) return 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) return 'Password must contain letters and numbers.';
    return null;
}
