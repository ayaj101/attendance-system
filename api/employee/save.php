<?php
/**
 * Create or update an employee (multipart form supporting photo upload).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_api_login();
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$id           = (int) input('id', 0);
$code         = input('employee_code', '');
$name         = input('name', '');
$department   = input('department', '');
$designation  = input('designation', '');
$mobile       = input('mobile', '');
$email        = input('email', '');
$joining_date = input('joining_date', '') ?: null;
$status       = input('status', 'Active');

// --- Validation ---
$errors = [];
if ($code === '') { $errors['employee_code'] = 'Employee code is required.'; }
if ($name === '') { $errors['name'] = 'Name is required.'; }
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Invalid email address.'; }
if (!in_array($status, ['Active', 'Inactive'], true)) { $status = 'Active'; }
if ($joining_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $joining_date)) { $errors['joining_date'] = 'Invalid date.'; }

// Unique employee code.
if ($code !== '') {
    $chk = db()->prepare('SELECT id FROM employees WHERE employee_code = ? AND id <> ?');
    $chk->execute([$code, $id]);
    if ($chk->fetch()) { $errors['employee_code'] = 'Employee code already exists.'; }
}

if ($errors) {
    json_response(['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors], 422);
}

// --- Photo upload (optional) ---
$photo = null;
if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $mime = mime_content_type($_FILES['photo']['tmp_name']);
    if (!isset($allowed[$mime])) {
        json_response(['success' => false, 'message' => 'Photo must be JPG, PNG, GIF or WEBP.'], 422);
    }
    if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        json_response(['success' => false, 'message' => 'Photo must be under 2 MB.'], 422);
    }
    $dir = UPLOAD_PATH . '/photos';
    if (!is_dir($dir)) { mkdir($dir, 0775, true); }
    $filename = 'emp_' . time() . '_' . random_int(1000, 9999) . '.' . $allowed[$mime];
    move_uploaded_file($_FILES['photo']['tmp_name'], $dir . '/' . $filename);
    $photo = 'uploads/photos/' . $filename;
}

if ($id > 0) {
    // Update
    if ($photo) {
        $stmt = db()->prepare('UPDATE employees SET employee_code=?, name=?, department=?, designation=?, mobile=?, email=?, joining_date=?, status=?, photo=? WHERE id=?');
        $stmt->execute([$code, $name, $department, $designation, $mobile, $email, $joining_date, $status, $photo, $id]);
    } else {
        $stmt = db()->prepare('UPDATE employees SET employee_code=?, name=?, department=?, designation=?, mobile=?, email=?, joining_date=?, status=? WHERE id=?');
        $stmt->execute([$code, $name, $department, $designation, $mobile, $email, $joining_date, $status, $id]);
    }
    log_activity('employee_update', "Updated employee: {$name} ({$code})");
    json_response(['success' => true, 'message' => 'Employee updated successfully.']);
} else {
    // Create
    $stmt = db()->prepare('INSERT INTO employees (employee_code, name, department, designation, mobile, email, joining_date, status, photo) VALUES (?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$code, $name, $department, $designation, $mobile, $email, $joining_date, $status, $photo]);
    log_activity('employee_create', "Added employee: {$name} ({$code})");
    json_response(['success' => true, 'message' => 'Employee added successfully.']);
}
