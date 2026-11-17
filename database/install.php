<?php
declare(strict_types=1);

/**
 * Database installer (run from the command line - start.bat does this for you).
 *
 *   php database/install.php           create database + demo data if it does not exist
 *   php database/install.php --fresh   DROP and recreate everything (all data is lost)
 *
 * Uses the MySQL root account (XAMPP default: root with no password).
 * Override with environment variables DB_ROOT_USER / DB_ROOT_PASS.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['timezone']);
$db = $config['db'];
$fresh = in_array('--fresh', $argv, true);

$rootUser = getenv('DB_ROOT_USER') ?: 'root';
$rootPass = getenv('DB_ROOT_PASS') ?: '';

try {
    $root = new PDO("mysql:host={$db['host']};port={$db['port']};charset=utf8mb4", $rootUser, $rootPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "[ERROR] Cannot connect to MySQL as '$rootUser': {$e->getMessage()}\n");
    exit(1);
}

$name = $db['name'];
if (!preg_match('/^\w+$/', $name)) {
    fwrite(STDERR, "[ERROR] Invalid database name in config.\n");
    exit(1);
}

// App database user (least privilege: only this database)
$u = $root->quote($db['user']);
$p = $root->quote($db['pass']);
$root->exec("CREATE USER IF NOT EXISTS $u@'localhost' IDENTIFIED BY $p");
$root->exec("CREATE USER IF NOT EXISTS $u@'127.0.0.1' IDENTIFIED BY $p");
$root->exec("GRANT ALL PRIVILEGES ON `$name`.* TO $u@'localhost'");
$root->exec("GRANT ALL PRIVILEGES ON `$name`.* TO $u@'127.0.0.1'");
$root->exec('FLUSH PRIVILEGES');

$exists = (bool) $root->query('SHOW DATABASES LIKE ' . $root->quote($name))->fetchColumn();
if ($exists && !$fresh) {
    echo "[OK] Database '$name' already exists - keeping your data.\n";
    exit(0);
}
if ($exists) {
    $root->exec("DROP DATABASE `$name`");
    echo "[..] Dropped old '$name'.\n";
}

$root->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo = new PDO("mysql:host={$db['host']};port={$db['port']};dbname=$name;charset=utf8mb4", $rootUser, $rootPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Schema
$sql = file_get_contents(__DIR__ . '/schema.sql');
$sql = preg_replace('/^\s*--.*$/m', '', $sql);
foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    $pdo->exec($stmt);
}
echo "[OK] Tables created.\n";

// ---------------------------------------------------------------------------
// Demo data
// ---------------------------------------------------------------------------
mt_srand(2024);

$ins = function (string $table, array $row) use ($pdo): int {
    $cols = implode(',', array_map(fn($c) => "`$c`", array_keys($row)));
    $ph = implode(',', array_fill(0, count($row), '?'));
    $pdo->prepare("INSERT INTO `$table` ($cols) VALUES ($ph)")->execute(array_values($row));
    return (int) $pdo->lastInsertId();
};
$hash = fn(string $pw) => password_hash($pw, PASSWORD_DEFAULT);

$pdo->beginTransaction();

// Clinics
$c1 = $ins('clinics', ['name' => 'SmileCare City Centre', 'address' => '42 Main Street, City Centre', 'phone' => '+94 11 234 5678', 'email' => 'city@smilecare.test', 'open_time' => '08:30:00', 'close_time' => '18:00:00', 'working_days' => '1,2,3,4,5,6', 'rooms' => 6]);
$c2 = $ins('clinics', ['name' => 'SmileCare Lakeside', 'address' => '7 Lake Drive, Lakeside', 'phone' => '+94 11 876 5432', 'email' => 'lakeside@smilecare.test', 'open_time' => '09:00:00', 'close_time' => '17:00:00', 'working_days' => '1,2,3,4,5', 'rooms' => 3]);

// Services (based on the original dental codes, modernised)
$services = [
    ['D100', 'Check-up & Consultation', 'Full oral examination, X-ray review and a personalised treatment plan.', 2500, 30],
    ['D101', 'Scaling & Polishing', 'Professional cleaning to remove plaque and tartar for healthier gums.', 4500, 45],
    ['D102', 'Tooth-coloured Filling', 'Natural-looking composite fillings for cavities.', 6500, 45],
    ['D103', 'Root Canal Treatment', 'Save an infected tooth with painless, modern endodontics.', 18000, 90],
    ['D104', 'Tooth Extraction', 'Gentle removal of damaged or wisdom teeth.', 5000, 30],
    ['D105', 'Gum Treatment', 'Deep cleaning and care for gum disease.', 6000, 60],
    ['D106', 'Teeth Whitening (Laser)', 'Brighten your smile by several shades in one visit.', 15000, 60],
    ['D107', 'Braces Consultation', 'Orthodontic assessment for metal, ceramic or clear aligners.', 3500, 30],
    ['D108', 'Partial Denture', 'Comfortable removable dentures to replace missing teeth.', 25000, 60],
    ['D109', 'Dental Implant', 'Permanent tooth replacement that looks and feels natural.', 85000, 90],
];
$serviceIds = [];
foreach ($services as [$code, $sname, $desc, $price, $dur]) {
    $serviceIds[] = ['id' => $ins('services', ['code' => $code, 'name' => $sname, 'description' => $desc, 'price' => $price, 'duration_minutes' => $dur]), 'name' => $sname, 'price' => $price, 'dur' => $dur];
}

// Admin
$ins('users', ['role' => 'admin', 'name' => 'Clinic Admin', 'email' => 'admin@smilecare.test', 'phone' => '+94 77 000 0000', 'password_hash' => $hash('Admin@123')]);

// Dentists
$dentistDefs = [
    ['Sarah Mitchell', 'dentist@smilecare.test', 'General & Family Dentistry', 12, $c1, 'Loves helping nervous patients feel at ease. Focus on preventive care for all ages.'],
    ['Arjun Mehta', 'arjun@smilecare.test', 'Orthodontics', 9, $c1, 'Braces and clear aligner specialist creating confident smiles.'],
    ['Nadia Fernando', 'nadia@smilecare.test', 'Cosmetic Dentistry', 7, $c2, 'Whitening, veneers and smile makeovers with a natural look.'],
    ['Kevin Rodrigo', 'kevin@smilecare.test', 'Oral Surgery & Implants', 15, $c2, 'Experienced in implants, extractions and complex procedures.'],
];
$dentists = [];
foreach ($dentistDefs as [$dname, $demail, $spec, $exp, $clinic, $bio]) {
    $id = $ins('users', ['role' => 'dentist', 'name' => $dname, 'email' => $demail, 'phone' => '+94 77 ' . mt_rand(100, 999) . ' ' . mt_rand(1000, 9999), 'password_hash' => $hash('Dentist@123')]);
    $ins('dentists', ['user_id' => $id, 'clinic_id' => $clinic, 'specialty' => $spec, 'experience_years' => $exp, 'bio' => $bio]);
    $dentists[] = ['id' => $id, 'clinic' => $clinic];
}

// Patients
$patientDefs = [
    ['Kasun Silva', 'patient@smilecare.test', '1990-04-12', 'male', 'O+', 'Penicillin', 'Mild hypertension, on medication.'],
    ['Emily Carter', 'emily@example.test', '1985-09-23', 'female', 'A+', null, null],
    ['Ravi Kumar', 'ravi@example.test', '1978-01-30', 'male', 'B+', null, 'Diabetic (Type 2).'],
    ['Amaya Jayasinghe', 'amaya@example.test', '2001-06-05', 'female', 'AB+', 'Latex', null],
    ['Daniel Brooks', 'daniel@example.test', '1995-11-17', 'male', 'O-', null, null],
    ['Priya Nair', 'priya@example.test', '1988-03-08', 'female', 'A-', null, 'Pregnant (2nd trimester).'],
    ['Tharindu Perera', 'tharindu@example.test', '2010-08-14', 'male', 'B-', null, 'Child patient - guardian: Mrs. Perera.'],
    ['Sophie Turner', 'sophie@example.test', '1999-12-01', 'female', 'O+', null, null],
];
$patients = [];
foreach ($patientDefs as [$pname, $pemail, $dob, $gender, $blood, $allergy, $notes]) {
    $id = $ins('users', ['role' => 'patient', 'name' => $pname, 'email' => $pemail, 'phone' => '+94 71 ' . mt_rand(100, 999) . ' ' . mt_rand(1000, 9999), 'password_hash' => $hash('Patient@123'), 'created_at' => date('Y-m-d H:i:s', strtotime('-' . mt_rand(30, 400) . ' days'))]);
    $ins('patients', ['user_id' => $id, 'date_of_birth' => $dob, 'gender' => $gender, 'blood_group' => $blood, 'allergies' => $allergy, 'medical_notes' => $notes, 'address' => mt_rand(1, 200) . ' Flower Road, Colombo', 'emergency_contact' => 'Family - +94 77 ' . mt_rand(100, 999) . ' ' . mt_rand(1000, 9999)]);
    $patients[] = $id;
}

// Staff
foreach ([['Dilani Wickrama', 'Receptionist', $c1], ['Mark Jensen', 'Dental Nurse', $c1], ['Fathima Rizwan', 'Dental Hygienist', $c2], ['Suresh Bandara', 'Practice Manager', $c1]] as [$sname, $pos, $clinic]) {
    $ins('staff', ['name' => $sname, 'position' => $pos, 'clinic_id' => $clinic, 'phone' => '+94 76 ' . mt_rand(100, 999) . ' ' . mt_rand(1000, 9999), 'email' => strtolower(strtok($sname, ' ')) . '@smilecare.test', 'joined_on' => date('Y-m-d', strtotime('-' . mt_rand(60, 1500) . ' days'))]);
}

// Offers
$ins('offers', ['title' => 'New Patient Special', 'description' => 'First check-up and cleaning together at a special price.', 'discount_percent' => 20, 'valid_until' => date('Y-m-d', strtotime('+60 days'))]);
$ins('offers', ['title' => 'Family Plan', 'description' => 'Bring the family - 15% off when booking 3 or more members.', 'discount_percent' => 15, 'valid_until' => date('Y-m-d', strtotime('+90 days'))]);
$ins('offers', ['title' => 'Bright Smile Month', 'description' => 'Laser teeth whitening with a free take-home kit.', 'discount_percent' => 10, 'valid_until' => date('Y-m-d', strtotime('+30 days'))]);

// Closed day (next Wednesday + 2 weeks)
$closed = date('Y-m-d', strtotime('wednesday +2 weeks'));
$ins('closed_days', ['closed_on' => $closed, 'reason' => 'Staff training day']);

// Appointments: past 30 days (history) + today + next 14 days
$clinicHours = [$c1 => ['08:30', '18:00', [1, 2, 3, 4, 5, 6]], $c2 => ['09:00', '17:00', [1, 2, 3, 4, 5]]];
$notesPool = ['Sensitive tooth on the upper left.', 'Bleeding gums when brushing.', 'Regular six-month check-up.', 'Would like whitening advice.', null, null, null];
$visitNotes = ['Patient tolerated procedure well. Advised soft diet for 24h.', 'Good oral hygiene. Recall in 6 months.', 'Mild gingivitis noted; demonstrated flossing technique.', 'Follow-up recommended in 2 weeks.'];
$teeth = ['11', '16', '21', '26', '36', '46', '14', '24', null];
$invoiceCount = 0;

for ($offset = -30; $offset <= 14; $offset++) {
    $date = date('Y-m-d', strtotime("$offset days"));
    if ($date === $closed) continue;
    foreach ($dentists as $di => $d) {
        [$open, $close, $days] = $clinicHours[$d['clinic']];
        if (!in_array((int) date('N', strtotime($date)), $days, true)) continue;
        $perDay = $offset === 0 ? ($di === 0 ? 5 : 3) : mt_rand(0, $offset < 0 ? 3 : 2);
        $cursor = strtotime("$date $open") + mt_rand(0, 2) * 1800;
        for ($k = 0; $k < $perDay; $k++) {
            $svc = $serviceIds[mt_rand(0, count($serviceIds) - 1)];
            $start = $cursor;
            $end = $start + $svc['dur'] * 60;
            if ($end > strtotime("$date $close")) break;
            $cursor = $end + mt_rand(0, 3) * 1800;

            $isPast = $end < time();
            if ($isPast) {
                $r = mt_rand(1, 100);
                $status = $r <= 82 ? 'completed' : ($r <= 92 ? 'cancelled' : 'no_show');
            } else {
                $status = $offset === 0 || mt_rand(1, 100) <= 65 ? 'confirmed' : 'pending';
            }
            $pid = $patients[mt_rand(0, count($patients) - 1)];
            $apptId = $ins('appointments', [
                'patient_id' => $pid, 'dentist_id' => $d['id'], 'service_id' => $svc['id'], 'clinic_id' => $d['clinic'],
                'appointment_date' => $date, 'start_time' => date('H:i:s', $start), 'end_time' => date('H:i:s', $end),
                'status' => $status, 'patient_notes' => $notesPool[mt_rand(0, count($notesPool) - 1)],
                'visit_notes' => $status === 'completed' ? $visitNotes[mt_rand(0, count($visitNotes) - 1)] : null,
                'cancel_reason' => $status === 'cancelled' ? 'Patient rescheduled' : null,
                'created_at' => date('Y-m-d H:i:s', strtotime($date) - mt_rand(2, 10) * 86400),
            ]);

            if ($status === 'completed') {
                $lines = [[$svc['id'], $svc['name'], $svc['price']]];
                if (mt_rand(1, 100) <= 25) {
                    $extra = $serviceIds[mt_rand(0, 2)];
                    $lines[] = [$extra['id'], $extra['name'], $extra['price']];
                }
                $subtotal = array_sum(array_column($lines, 2));
                $discount = mt_rand(1, 100) <= 20 ? round($subtotal * 0.1, 2) : 0;
                $issued = date('Y-m-d H:i:s', $end);
                $paid = mt_rand(1, 100) <= 85 || $offset < -7;
                $invId = $ins('invoices', [
                    'invoice_no' => 'TMP' . $apptId, 'patient_id' => $pid, 'appointment_id' => $apptId,
                    'subtotal' => $subtotal, 'discount' => $discount, 'total' => $subtotal - $discount,
                    'status' => $paid ? 'paid' : 'unpaid', 'payment_method' => $paid ? (mt_rand(0, 1) ? 'Card' : 'Cash') : null,
                    'issued_at' => $issued, 'paid_at' => $paid ? $issued : null,
                ]);
                $pdo->prepare('UPDATE invoices SET invoice_no = ? WHERE id = ?')->execute([sprintf('INV-%s-%04d', date('Y', $end), $invId), $invId]);
                $invoiceCount++;
                foreach ($lines as [$sid, $desc, $price]) {
                    $ins('treatments', ['patient_id' => $pid, 'dentist_id' => $d['id'], 'appointment_id' => $apptId, 'service_id' => $sid, 'invoice_id' => $invId,
                        'description' => $desc, 'tooth' => $teeth[mt_rand(0, count($teeth) - 1)], 'price' => $price, 'created_at' => $issued]);
                }
            }
        }
    }
}

// Feedback
$reviews = [
    [5, 'Dr. Mitchell was so gentle - I actually enjoyed my check-up! Booking online took a minute.'],
    [5, 'Modern clinic, friendly staff and no waiting. My whitening results are amazing.'],
    [4, 'Very professional root canal, completely painless. Parking can be tricky though.'],
    [5, 'Our whole family comes here. The kids love Dr. Fernando.'],
    [5, 'Clear pricing and an invoice straight after the visit. Highly recommend.'],
    [3, 'Good treatment but I had to wait 15 minutes past my slot.'],
];
foreach ($reviews as $i => [$rating, $msg]) {
    $ins('feedback', ['patient_id' => $patients[$i % count($patients)], 'rating' => $rating, 'message' => $msg, 'is_public' => $rating >= 4 ? 1 : 0,
        'created_at' => date('Y-m-d H:i:s', strtotime('-' . mt_rand(1, 60) . ' days'))]);
}

$pdo->commit();

$apptCount = (int) $pdo->query('SELECT COUNT(*) FROM appointments')->fetchColumn();
echo "[OK] Demo data: " . count($dentists) . " dentists, " . count($patients) . " patients, $apptCount appointments, $invoiceCount invoices.\n";
