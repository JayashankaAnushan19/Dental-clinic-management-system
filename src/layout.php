<?php
declare(strict_types=1);

/** Inline SVG icons (stroke style). */
function icon(string $name, int $size = 18): string
{
    static $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'calendar'  => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'calendar-plus' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M12 14v5M9.5 16.5h5"/>',
        'users'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'user'      => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'tooth'     => '<path d="M12 5.5C10.5 4 8.5 3 6.5 3.5 3.8 4.2 3 7 3.5 9.5c.4 2 1.3 3.4 1.7 5.5.5 2.7.9 6 2.8 6 1.6 0 1.7-3 2.3-4.8.3-1 .9-1.7 1.7-1.7s1.4.7 1.7 1.7c.6 1.8.7 4.8 2.3 4.8 1.9 0 2.3-3.3 2.8-6 .4-2.1 1.3-3.5 1.7-5.5.5-2.5-.3-5.3-3-6-2-.5-4 .5-5.5 2Z"/>',
        'stethoscope' => '<path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6 6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"/><path d="M8 15v1a6 6 0 0 0 6 6 6 6 0 0 0 6-6v-4"/><circle cx="20" cy="10" r="2"/>',
        'building'  => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/>',
        'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
        'receipt'   => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8M12 17.5v-11"/>',
        'star'      => '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>',
        'tag'       => '<path d="M12.6 2.6A2 2 0 0 0 11.2 2H4a2 2 0 0 0-2 2v7.2a2 2 0 0 0 .6 1.4l8.7 8.7a2.4 2.4 0 0 0 3.4 0l6.6-6.6a2.4 2.4 0 0 0 0-3.4Z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>',
        'calendar-x'=> '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M10 14l4 4M14 14l-4 4"/>',
        'clipboard' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2M12 11h4M12 16h4M8 11h.01M8 16h.01"/>',
        'message'   => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'settings'  => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'edit'      => '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'trash'     => '<path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'check'     => '<path d="M20 6 9 17l-5-5"/>',
        'x'         => '<path d="M18 6 6 18M6 6l12 12"/>',
        'clock'     => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'search'    => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'chevron-left'  => '<path d="m15 18-6-6 6-6"/>',
        'arrow-right'   => '<path d="M5 12h14M12 5l7 7-7 7"/>',
        'menu'      => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'sun'       => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>',
        'moon'      => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
        'phone'     => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'mail'      => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'map-pin'   => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'file'      => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4M10 9H8M16 13H8M16 17H8"/>',
        'alert'     => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
        'info'      => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
        'shield'    => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
        'sparkles'  => '<path d="M9.94 15.5A2 2 0 0 0 8.5 14.06l-6.14-1.58a.5.5 0 0 1 0-.96L8.5 9.94A2 2 0 0 0 9.94 8.5l1.58-6.14a.5.5 0 0 1 .96 0l1.58 6.14a2 2 0 0 0 1.44 1.44l6.14 1.58a.5.5 0 0 1 0 .96l-6.14 1.58a2 2 0 0 0-1.44 1.44l-1.58 6.14a.5.5 0 0 1-.96 0z"/>',
        'heart'     => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
        'printer'   => '<path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
        'trend'     => '<path d="m22 7-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/>',
        'wallet'    => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
        'eye'       => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off'   => '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24M10.73 5.08A10.4 10.4 0 0 1 12 5c7 0 10 7 10 7a13.2 13.2 0 0 1-1.67 2.68M6.61 6.61A13.5 13.5 0 0 0 2 12s3 7 10 7a9.7 9.7 0 0 0 5.39-1.61M2 2l20 20"/>',
    ];
    $p = $paths[$name] ?? $paths['info'];
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function nav_items(string $role): array
{
    return match ($role) {
        'admin' => [
            'Overview' => [
                ['dashboard', 'Dashboard', 'admin/'],
                ['calendar', 'Appointments', 'admin/appointments.php'],
                ['users', 'Patients', 'admin/patients.php'],
                ['stethoscope', 'Dentists', 'admin/dentists.php'],
                ['receipt', 'Invoices', 'admin/invoices.php'],
            ],
            'Manage' => [
                ['tooth', 'Services', 'admin/services.php'],
                ['building', 'Clinics', 'admin/clinics.php'],
                ['briefcase', 'Staff', 'admin/staff.php'],
                ['tag', 'Offers', 'admin/offers.php'],
                ['calendar-x', 'Closed days', 'admin/closed-days.php'],
                ['message', 'Feedback', 'admin/feedback.php'],
            ],
        ],
        'dentist' => [
            'Work' => [
                ['dashboard', 'My schedule', 'dentist/'],
                ['calendar', 'All appointments', 'dentist/appointments.php'],
                ['users', 'My patients', 'dentist/patients.php'],
            ],
        ],
        default => [
            'Menu' => [
                ['dashboard', 'Home', 'patient/'],
                ['calendar-plus', 'Book appointment', 'patient/book.php'],
                ['calendar', 'My appointments', 'patient/appointments.php'],
                ['clipboard', 'Treatment history', 'patient/records.php'],
                ['receipt', 'Invoices', 'patient/invoices.php'],
                ['star', 'Feedback', 'patient/feedback.php'],
            ],
        ],
    };
}

function theme_script(): string
{
    // Runs before paint to avoid a flash of the wrong theme.
    return "<script>try{var t=localStorage.getItem('theme');if(t)document.documentElement.dataset.theme=t;}catch(e){}</script>";
}

function html_head(string $title): void
{
    $app = e(config('app_name'));
    $t = $title !== '' ? e($title) . ' · ' . $app : $app;
    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$t}</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='16' fill='%230d9488'/%3E%3Cpath d='M32 17c-3-3-7-5-11-4-6 1-7 7-6 12 1 4 3 7 3 11 1 6 2 13 6 13 3 0 3-6 5-10 .5-2 1.5-3 3-3s2.5 1 3 3c2 4 2 10 5 10 4 0 5-7 6-13 0-4 2-7 3-11 1-5 0-11-6-12-4-1-8 1-11 4Z' fill='white'/%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
HTML;
    echo '<link rel="stylesheet" href="' . e(asset('css/app.css')) . '">';
    echo theme_script();
    echo "\n</head>\n";
}

function render_flashes(): string
{
    $out = '';
    foreach (take_flashes() as $f) {
        $type = in_array($f['type'], ['success', 'error', 'info', 'warning'], true) ? $f['type'] : 'info';
        $ic = ['success' => 'check', 'error' => 'alert', 'warning' => 'alert', 'info' => 'info'][$type];
        $out .= '<div class="toast toast-' . $type . '" role="status">' . icon($ic) . '<span>' . e($f['message']) . '</span>'
              . '<button type="button" class="toast-close" aria-label="Dismiss">' . icon('x', 16) . '</button></div>';
    }
    return $out ? '<div class="toasts">' . $out . '</div>' : '';
}

/** Start an authenticated app page (sidebar + top bar). */
function layout_start(string $title, string $active = ''): void
{
    $u = current_user();
    html_head($title);
    echo '<body class="app">';
    echo render_flashes();
    echo '<aside class="sidebar" id="sidebar">';
    echo '<a class="brand" href="' . e($u ? home_for($u['role']) : url()) . '"><span class="brand-mark">' . icon('tooth', 20) . '</span><span>' . e(config('app_name')) . '</span></a>';
    if ($u) {
        echo '<nav class="nav">';
        foreach (nav_items($u['role']) as $group => $items) {
            echo '<div class="nav-group">' . e($group) . '</div>';
            foreach ($items as [$ic, $label, $href]) {
                $is = $active === $href ? ' active' : '';
                echo '<a class="nav-link' . $is . '" href="' . e(url($href)) . '">' . icon($ic) . '<span>' . e($label) . '</span></a>';
            }
        }
        echo '</nav>';
        echo '<div class="sidebar-foot"><a class="nav-link" href="' . e(url()) . '" target="_blank">' . icon('heart') . '<span>Public website</span></a></div>';
    }
    echo '</aside><div class="sidebar-backdrop" data-sidebar-close></div>';

    echo '<div class="main"><header class="topbar">';
    echo '<button class="icon-btn only-mobile" type="button" data-sidebar-open aria-label="Open menu">' . icon('menu', 20) . '</button>';
    echo '<div class="topbar-title">' . e($title) . '</div>';
    echo '<div class="topbar-actions">';
    echo '<button class="icon-btn" type="button" data-theme-toggle aria-label="Toggle dark mode"><span class="show-light">' . icon('moon') . '</span><span class="show-dark">' . icon('sun') . '</span></button>';
    if ($u) {
        echo '<details class="usermenu"><summary>' . avatar($u['name'], 'sm') . '<span class="usermenu-name">' . e($u['name']) . '<small>' . e(ucfirst($u['role'])) . '</small></span></summary>';
        echo '<div class="menu"><a href="' . e(url('profile.php')) . '">' . icon('settings', 16) . ' Profile &amp; password</a>';
        echo '<form method="post" action="' . e(url('logout.php')) . '">' . csrf_field() . '<button type="submit">' . icon('logout', 16) . ' Sign out</button></form></div></details>';
    }
    echo '</div></header><main class="content">';
}

function layout_end(): void
{
    echo '</main></div>';
    echo '<script src="' . e(asset('js/app.js')) . '" defer></script>';
    echo '</body></html>';
}

/** Page title row with optional action buttons. */
function page_header(string $title, string $subtitle = '', string $actions = ''): string
{
    return '<div class="page-header"><div><h1>' . e($title) . '</h1>' . ($subtitle ? '<p class="muted">' . e($subtitle) . '</p>' : '') . '</div>'
         . ($actions ? '<div class="page-actions">' . $actions . '</div>' : '') . '</div>';
}

function empty_state(string $ic, string $title, string $text = '', string $action = ''): string
{
    return '<div class="empty"><div class="empty-icon">' . icon($ic, 28) . '</div><h3>' . e($title) . '</h3>'
         . ($text ? '<p class="muted">' . e($text) . '</p>' : '') . $action . '</div>';
}

function stat_card(string $ic, string $label, string $value, string $hint = '', string $tone = 'primary'): string
{
    return '<div class="stat tone-' . e($tone) . '"><div class="stat-icon">' . icon($ic, 20) . '</div><div><div class="stat-label">' . e($label) . '</div>'
         . '<div class="stat-value">' . e($value) . '</div>' . ($hint ? '<div class="stat-hint">' . e($hint) . '</div>' : '') . '</div></div>';
}

/** Small POST button (for actions like confirm / delete). */
function action_button(string $action, string $label, array $fields = [], string $class = 'btn btn-sm btn-ghost', string $confirm = '', string $ic = ''): string
{
    $html = '<form method="post" action="' . e($action) . '" class="inline"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . csrf_field();
    foreach ($fields as $k => $v) {
        $html .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    return $html . '<button type="submit" class="' . e($class) . '">' . ($ic ? icon($ic, 16) : '') . e($label) . '</button></form>';
}

// ---- Public (marketing) pages ----------------------------------------------

function public_start(string $title = ''): void
{
    $u = current_user();
    html_head($title);
    echo '<body class="site">';
    echo render_flashes();
    echo '<header class="site-nav"><div class="container nav-inner">';
    echo '<a class="brand" href="' . e(url()) . '"><span class="brand-mark">' . icon('tooth', 20) . '</span><span>' . e(config('app_name')) . '</span></a>';
    echo '<nav class="site-links"><a href="' . e(url('#services')) . '">Services</a><a href="' . e(url('#dentists')) . '">Dentists</a><a href="' . e(url('#offers')) . '">Offers</a><a href="' . e(url('#contact')) . '">Contact</a></nav>';
    echo '<div class="site-actions"><button class="icon-btn" type="button" data-theme-toggle aria-label="Toggle dark mode"><span class="show-light">' . icon('moon') . '</span><span class="show-dark">' . icon('sun') . '</span></button>';
    if ($u) {
        echo '<a class="btn btn-primary" href="' . e(home_for($u['role'])) . '">My dashboard</a>';
    } else {
        echo '<a class="btn btn-ghost hide-sm" href="' . e(url('login.php')) . '">Sign in</a><a class="btn btn-primary" href="' . e(url('register.php')) . '">Book now</a>';
    }
    echo '</div></div></header>';
}

function public_end(): void
{
    $year = date('Y');
    $app = e(config('app_name'));
    echo '<footer class="site-footer"><div class="container footer-inner"><div class="brand">' . '<span class="brand-mark">' . icon('tooth', 18) . '</span><span>' . $app . '</span></div>'
       . '<p class="muted">© ' . $year . ' ' . $app . '. All rights reserved.</p></div></footer>';
    echo '<script src="' . e(asset('js/app.js')) . '" defer></script></body></html>';
}

/** Minimal centered layout used by login / register. */
function auth_start(string $title): void
{
    html_head($title);
    echo '<body class="auth">';
    echo render_flashes();
}

function auth_end(): void
{
    echo '<script src="' . e(asset('js/app.js')) . '" defer></script></body></html>';
}
