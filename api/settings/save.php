<?php
/**
 * Update application settings (office timing + company branding).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_api_login();
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$officeStart = input('office_start', '08:00');
$officeEnd   = input('office_end', '17:00');
$breakTime   = (int) input('break_time', 30);
$dutyHours   = input('duty_hours', '08:30');
$companyName = input('company_name', 'Company');
$address     = input('address', '');
$phone       = input('phone', '');
$email       = input('email', '');
$theme       = in_array(input('theme', 'light'), ['light', 'dark'], true) ? input('theme', 'light') : 'light';

$errors = [];
foreach (['office_start' => $officeStart, 'office_end' => $officeEnd] as $k => $v) {
    if (!preg_match('/^\d{1,2}:\d{2}/', (string) $v)) { $errors[$k] = 'Invalid time.'; }
}
if (!preg_match('/^\d{1,2}:\d{2}$/', (string) $dutyHours)) { $errors['duty_hours'] = 'Use HH:MM format.'; }
if ($companyName === '') { $errors['company_name'] = 'Company name is required.'; }
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Invalid email.'; }
if ($breakTime < 0 || $breakTime > 240) { $errors['break_time'] = '0-240 minutes.'; }
if ($errors) {
    json_response(['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors], 422);
}

// Optional logo upload.
$logo = null;
if (!empty($_FILES['company_logo']['name']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
    $mime = mime_content_type($_FILES['company_logo']['tmp_name']);
    if (isset($allowed[$mime]) && $_FILES['company_logo']['size'] <= 2 * 1024 * 1024) {
        $dir = UPLOAD_PATH . '/logo';
        if (!is_dir($dir)) { mkdir($dir, 0775, true); }
        $filename = 'logo_' . time() . '.' . $allowed[$mime];
        move_uploaded_file($_FILES['company_logo']['tmp_name'], $dir . '/' . $filename);
        $logo = 'uploads/logo/' . $filename;
    }
}

$id = (int) db()->query('SELECT id FROM settings ORDER BY id ASC LIMIT 1')->fetchColumn();
if ($id) {
    $fields = 'office_start=?, office_end=?, break_time=?, duty_hours=?, company_name=?, address=?, phone=?, email=?, theme=?';
    $params = [$officeStart, $officeEnd, $breakTime, $dutyHours, $companyName, $address, $phone, $email, $theme];
    if ($logo) { $fields .= ', company_logo=?'; $params[] = $logo; }
    $params[] = $id;
    db()->prepare("UPDATE settings SET {$fields} WHERE id=?")->execute($params);
} else {
    db()->prepare('INSERT INTO settings (office_start, office_end, break_time, duty_hours, company_name, address, phone, email, theme, company_logo) VALUES (?,?,?,?,?,?,?,?,?,?)')
        ->execute([$officeStart, $officeEnd, $breakTime, $dutyHours, $companyName, $address, $phone, $email, $theme, $logo]);
}

log_activity('settings_update', 'Updated application settings.');
json_response(['success' => true, 'message' => 'Settings saved successfully.']);
