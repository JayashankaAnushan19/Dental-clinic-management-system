<?php
declare(strict_types=1);

/**
 * Generic list / create / edit / delete page for simple admin tables.
 *
 * $cfg keys:
 *   table, title, singular, active (nav href), icon, subtitle
 *   fields:  [column => [label, type(text|email|number|date|time|textarea|checkbox|select|weekdays),
 *             required, max, min, step, default, options(array|callable), help, width(full|half)]]
 *   columns: [column => label | [label, callable($row): string html]]
 *   order:   SQL ORDER BY
 *   search:  [columns searched by ?q=]
 *   select:  optional custom SELECT (must expose `id` and column names)
 */
function crud_page(array $cfg): void
{
    require_role('admin');
    $table = $cfg['table'];
    $self = url($cfg['active']);
    $fields = $cfg['fields'];
    $errors = [];
    $action = get('action');
    $id = get_int('id');

    // ---------- POST: save / delete ----------
    if (is_post()) {
        $op = post('_op');
        if ($op === 'delete') {
            $delId = (int) post('id');
            try {
                q("DELETE FROM `$table` WHERE id = ?", [$delId]);
                flash('success', ucfirst($cfg['singular']) . ' deleted.');
            } catch (PDOException $e) {
                flash('error', 'This ' . $cfg['singular'] . ' is in use and cannot be deleted. Try marking it inactive instead.');
            }
            redirect($self);
        }

        $id = (int) post('id');
        $data = [];
        foreach ($fields as $col => $f) {
            $type = $f['type'] ?? 'text';
            $label = $f['label'];
            if ($type === 'checkbox') { $data[$col] = isset($_POST[$col]) ? 1 : 0; continue; }
            if ($type === 'weekdays') {
                $days = array_values(array_filter(array_map('intval', (array) ($_POST[$col] ?? [])), fn($d) => $d >= 1 && $d <= 7));
                sort($days);
                if (!$days) $errors[$col] = 'Choose at least one day.';
                $data[$col] = implode(',', $days);
                continue;
            }
            $value = post($col);
            if ($value === '') {
                if (!empty($f['required'])) $errors[$col] = "$label is required.";
                $data[$col] = $type === 'number' && !empty($f['required']) ? 0 : null;
                continue;
            }
            if (isset($f['max']) && in_array($type, ['text', 'email', 'textarea'], true) && mb_strlen($value) > $f['max']) {
                $errors[$col] = "$label is too long (max {$f['max']}).";
            }
            if ($type === 'email' && !valid_email($value)) $errors[$col] = 'Enter a valid email address.';
            if ($type === 'number') {
                if (!is_numeric($value)) $errors[$col] = "$label must be a number.";
                elseif (isset($f['min']) && (float) $value < $f['min']) $errors[$col] = "$label must be at least {$f['min']}.";
                elseif (isset($f['maxv']) && (float) $value > $f['maxv']) $errors[$col] = "$label must be at most {$f['maxv']}.";
            }
            if ($type === 'date' && !valid_date($value)) $errors[$col] = 'Enter a valid date.';
            if ($type === 'time' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) $errors[$col] = 'Enter a valid time.';
            if ($type === 'select') {
                $opts = is_callable($f['options']) ? ($f['options'])() : $f['options'];
                if (!array_key_exists($value, $opts)) $errors[$col] = "Choose a valid $label.";
            }
            $data[$col] = $value;
        }
        if (!$errors && isset($cfg['validate'])) {
            $errors = ($cfg['validate'])($data, $id);
        }

        if (!$errors) {
            try {
                if ($id) {
                    $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($data)));
                    q("UPDATE `$table` SET $set WHERE id = ?", [...array_values($data), $id]);
                    flash('success', ucfirst($cfg['singular']) . ' updated.');
                } else {
                    $cols = implode(', ', array_map(fn($c) => "`$c`", array_keys($data)));
                    q("INSERT INTO `$table` ($cols) VALUES (" . placeholders($data) . ')', array_values($data));
                    flash('success', ucfirst($cfg['singular']) . ' added.');
                }
                redirect($self);
            } catch (PDOException $e) {
                if (!is_duplicate_error($e)) throw $e;
                $errors['_form'] = 'A ' . $cfg['singular'] . ' with these details already exists.';
            }
        }
        $action = $id ? 'edit' : 'new';
    }

    // ---------- Form view ----------
    if ($action === 'new' || $action === 'edit') {
        $record = $id ? row("SELECT * FROM `$table` WHERE id = ?", [$id]) : null;
        if ($id && !$record) { flash('error', 'Not found.'); redirect($self); }
        $heading = ($record ? 'Edit ' : 'New ') . $cfg['singular'];
        layout_start($cfg['title'], $cfg['active']);
        echo page_header($heading, '', '<a class="btn btn-ghost" href="' . e($self) . '">' . icon('chevron-left', 16) . 'Back</a>');
        echo '<form method="post" action="' . e($self) . '" class="card form-card" novalidate>' . csrf_field();
        echo '<input type="hidden" name="id" value="' . (int) ($record['id'] ?? 0) . '">';
        if (isset($errors['_form'])) echo '<div class="alert alert-error">' . e($errors['_form']) . '</div>';
        echo '<div class="form-grid">';
        foreach ($fields as $col => $f) {
            echo render_field($col, $f, $record, $errors[$col] ?? null);
        }
        echo '</div><div class="form-actions"><a class="btn btn-ghost" href="' . e($self) . '">Cancel</a>'
           . '<button class="btn btn-primary" type="submit">' . icon('check', 16) . 'Save ' . e($cfg['singular']) . '</button></div></form>';
        layout_end();
        return;
    }

    // ---------- List view ----------
    $search = get('q');
    $where = '';
    $params = [];
    if ($search !== '' && !empty($cfg['search'])) {
        $where = 'WHERE ' . implode(' OR ', array_map(fn($c) => "$c LIKE ?", $cfg['search']));
        $params = array_fill(0, count($cfg['search']), '%' . $search . '%');
    }
    $base = $cfg['select'] ?? "SELECT * FROM `$table`";
    $list = rows("SELECT * FROM ($base) t $where ORDER BY {$cfg['order']} LIMIT 500", $params);

    layout_start($cfg['title'], $cfg['active']);
    echo page_header($cfg['title'], $cfg['subtitle'] ?? '', '<a class="btn btn-primary" href="' . e($self) . '?action=new">' . icon('plus', 16) . 'Add ' . e($cfg['singular']) . '</a>');
    echo '<div class="card">';
    if (!empty($cfg['search'])) {
        echo '<form class="toolbar" method="get"><label class="search">' . icon('search', 16)
           . '<input type="search" name="q" value="' . e($search) . '" placeholder="Search ' . e(strtolower($cfg['title'])) . '…"></label></form>';
    }
    if (!$list) {
        echo empty_state($cfg['icon'], $search ? 'No matches' : 'No ' . strtolower($cfg['title']) . ' yet',
            $search ? 'Try a different search.' : 'Add the first ' . $cfg['singular'] . ' to get started.');
    } else {
        echo '<div class="table-wrap"><table class="table"><thead><tr>';
        foreach ($cfg['columns'] as $col => $c) echo '<th>' . e(is_array($c) ? $c[0] : $c) . '</th>';
        echo '<th class="right">Actions</th></tr></thead><tbody>';
        foreach ($list as $r) {
            echo '<tr>';
            foreach ($cfg['columns'] as $col => $c) {
                echo '<td data-label="' . e(is_array($c) ? $c[0] : $c) . '">' . (is_array($c) ? ($c[1])($r) : e($r[$col] ?? '')) . '</td>';
            }
            echo '<td class="right nowrap"><a class="btn btn-sm btn-ghost" href="' . e($self) . '?action=edit&id=' . (int) $r['id'] . '">' . icon('edit', 16) . 'Edit</a>'
               . action_button($self, '', ['_op' => 'delete', 'id' => $r['id']], 'btn btn-sm btn-ghost danger', 'Delete this ' . $cfg['singular'] . '?', 'trash')
               . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
    echo '</div>';
    layout_end();
}

/** Render a single form field. */
function render_field(string $col, array $f, ?array $record, ?string $error): string
{
    $type = $f['type'] ?? 'text';
    $label = e($f['label']);
    $req = !empty($f['required']) ? ' required' : '';
    $wide = in_array($type, ['textarea', 'weekdays'], true) || ($f['width'] ?? '') === 'full' ? ' full' : '';
    $value = v($record, $col, $f['default'] ?? '');
    $html = '<div class="field' . $wide . ($error ? ' has-error' : '') . '">';

    if ($type === 'checkbox') {
        $isOn = is_post() ? isset($_POST[$col]) : (bool) ($record[$col] ?? $f['default'] ?? 0);
        $html .= '<label class="switch"><input type="checkbox" name="' . e($col) . '" value="1"' . checked($isOn) . '><span class="switch-ui"></span>' . $label . '</label>';
    } elseif ($type === 'weekdays') {
        $sel = is_post() ? array_map('intval', (array) ($_POST[$col] ?? [])) : array_map('intval', explode(',', (string) ($record[$col] ?? $f['default'] ?? '')));
        $html .= '<span class="label">' . $label . '</span><div class="chips">';
        foreach (WEEKDAYS as $n => $d) {
            $html .= '<label class="chip"><input type="checkbox" name="' . e($col) . '[]" value="' . $n . '"' . checked(in_array($n, $sel, true)) . '><span>' . $d . '</span></label>';
        }
        $html .= '</div>';
    } else {
        $html .= '<label class="label" for="f-' . e($col) . '">' . $label . ($req ? ' <span class="req">*</span>' : '') . '</label>';
        if ($type === 'textarea') {
            $html .= '<textarea id="f-' . e($col) . '" name="' . e($col) . '" rows="4"' . $req . '>' . e($value) . '</textarea>';
        } elseif ($type === 'select') {
            $opts = is_callable($f['options']) ? ($f['options'])() : $f['options'];
            $html .= '<select id="f-' . e($col) . '" name="' . e($col) . '"' . $req . '>';
            if (empty($f['required'])) $html .= '<option value="">— None —</option>';
            foreach ($opts as $k => $o) $html .= '<option value="' . e($k) . '"' . selected($k, $value) . '>' . e($o) . '</option>';
            $html .= '</select>';
        } else {
            if ($type === 'time') $value = substr($value, 0, 5);
            $extra = '';
            foreach (['min', 'step'] as $a) if (isset($f[$a])) $extra .= " $a=\"" . e($f[$a]) . '"';
            if (isset($f['maxv'])) $extra .= ' max="' . e($f['maxv']) . '"';
            if (isset($f['max']) && $type !== 'number') $extra .= ' maxlength="' . (int) $f['max'] . '"';
            $html .= '<input id="f-' . e($col) . '" type="' . e($type) . '" name="' . e($col) . '" value="' . e($value) . '"' . $req . $extra . '>';
        }
    }
    if (!empty($f['help'])) $html .= '<p class="help">' . e($f['help']) . '</p>';
    if ($error) $html .= '<p class="error-text">' . e($error) . '</p>';
    return $html . '</div>';
}
