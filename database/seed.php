<?php
/**
 * Demo data seeder.
 *
 * Generates 30 employees and a full month of attendance with a realistic mix of
 * Present / Absent / Half Day / Leave / Weekend / Holiday records, including
 * varied overtime. Safe to run repeatedly (truncates employees & attendance).
 *
 * Run from CLI:   php database/seed.php
 * Or from browser: http://localhost/attendance-system/install.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';

function seed_database(): array
{
    $pdo = db();
    $messages = [];

    // Reset demo tables (keep users & settings).
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('TRUNCATE TABLE attendance');
    $pdo->exec('TRUNCATE TABLE employees');
    $pdo->exec('TRUNCATE TABLE holidays');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    $departments = ['Engineering', 'Sales', 'Marketing', 'Finance', 'Human Resources', 'Operations', 'IT Support', 'Administration'];
    $designations = [
        'Engineering'     => ['Software Engineer', 'Senior Developer', 'QA Engineer', 'Team Lead'],
        'Sales'           => ['Sales Executive', 'Account Manager', 'Sales Head'],
        'Marketing'       => ['Marketing Executive', 'Content Writer', 'SEO Specialist'],
        'Finance'         => ['Accountant', 'Finance Manager', 'Auditor'],
        'Human Resources' => ['HR Executive', 'HR Manager', 'Recruiter'],
        'Operations'      => ['Operations Executive', 'Operations Manager'],
        'IT Support'      => ['Support Engineer', 'System Administrator'],
        'Administration'  => ['Admin Assistant', 'Office Manager'],
    ];
    $first = ['Aarav', 'Vivaan', 'Aditya', 'Vihaan', 'Arjun', 'Sai', 'Reyansh', 'Krishna', 'Ishaan', 'Rohan',
              'Priya', 'Ananya', 'Diya', 'Aadhya', 'Saanvi', 'Riya', 'Neha', 'Pooja', 'Kavya', 'Meera',
              'Rahul', 'Amit', 'Suresh', 'Vikram', 'Nikhil', 'Sneha', 'Deepak', 'Manish', 'Anjali', 'Karthik'];
    $last = ['Sharma', 'Verma', 'Patel', 'Gupta', 'Singh', 'Kumar', 'Reddy', 'Nair', 'Iyer', 'Das',
             'Mehta', 'Shah', 'Rao', 'Joshi', 'Malhotra', 'Chopra', 'Bose', 'Menon', 'Pillai', 'Ghosh'];

    // ---- Employees ----
    $empStmt = $pdo->prepare(
        'INSERT INTO employees (employee_code, name, department, designation, mobile, email, joining_date, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $employeeIds = [];
    for ($i = 1; $i <= 30; $i++) {
        $dept = $departments[array_rand($departments)];
        $desig = $designations[$dept][array_rand($designations[$dept])];
        $name = $first[($i - 1) % count($first)] . ' ' . $last[array_rand($last)];
        $code = 'EMP' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
        $mobile = '+91 9' . random_int(100000000, 999999999);
        $email = strtolower(str_replace(' ', '.', $name)) . $i . '@acme.example';
        $joining = date('Y-m-d', strtotime('-' . random_int(30, 1500) . ' days'));
        $status = random_int(1, 20) === 1 ? 'Inactive' : 'Active';
        $empStmt->execute([$code, $name, $dept, $desig, $mobile, $email, $joining, $status]);
        $employeeIds[] = (int) $pdo->lastInsertId();
    }
    $messages[] = count($employeeIds) . ' employees created.';

    // ---- Holidays (a couple within the current month) ----
    $year = (int) date('Y');
    $month = (int) date('n');
    $holStmt = $pdo->prepare('INSERT INTO holidays (title, holiday_date) VALUES (?, ?)');
    $holidayDay = min(15, (int) date('t'));
    $holStmt->execute(['Company Foundation Day', sprintf('%04d-%02d-%02d', $year, $month, $holidayDay)]);
    $holidayDates = [sprintf('%04d-%02d-%02d', $year, $month, $holidayDay)];

    // ---- Attendance for the whole current month ----
    $set = settings();
    $officeStart = $set['office_start'] ?? '08:00:00';
    $daysInMonth = (int) date('t');
    $attStmt = $pdo->prepare(
        'INSERT INTO attendance (employee_id, attendance_date, status, in_time, out_time, working_hours, overtime, remarks)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $count = 0;
    foreach ($employeeIds as $empId) {
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
            // Skip future dates.
            if (strtotime($date) > strtotime(date('Y-m-d'))) {
                continue;
            }
            $dow = (int) date('N', strtotime($date));
            $status = 'Present';
            $in = $out = null;
            $remarks = '';

            if (in_array($date, $holidayDates, true)) {
                $status = 'Holiday';
                $remarks = 'Public Holiday';
            } elseif ($dow === 7) { // Sunday
                $status = 'Weekend';
            } else {
                $roll = random_int(1, 100);
                if ($roll <= 78) {
                    $status = 'Present';
                } elseif ($roll <= 86) {
                    $status = 'Half Day';
                } elseif ($roll <= 92) {
                    $status = 'Leave';
                    $remarks = ['Sick leave', 'Casual leave', 'Personal work'][array_rand([0, 1, 2])];
                } else {
                    $status = 'Absent';
                }
            }

            if ($status === 'Present') {
                // In-time near office start, out-time with varied overtime.
                $inMin = time_to_minutes($officeStart) + random_int(-10, 25);
                $extra = [0, 0, 0, 30, 60, 90, 120, 150, 180][array_rand(range(0, 8))];
                $outMin = $inMin + 510 + 30 + $extra; // duty + break + overtime
                $in = minutes_to_hhmm($inMin) . ':00';
                $out = minutes_to_hhmm($outMin) . ':00';
                if ($inMin > time_to_minutes($officeStart) + 10) {
                    $remarks = 'Late arrival';
                }
            } elseif ($status === 'Half Day') {
                $inMin = time_to_minutes($officeStart) + random_int(-5, 15);
                $outMin = $inMin + 240 + 30;
                $in = minutes_to_hhmm($inMin) . ':00';
                $out = minutes_to_hhmm($outMin) . ':00';
            }

            $calc = calculate_hours($in, $out, $status);
            $attStmt->execute([
                $empId, $date, $status, $in, $out,
                $calc['working'], $calc['overtime'], $remarks,
            ]);
            $count++;
        }
    }
    $messages[] = $count . ' attendance records created for ' . date('F Y') . '.';

    return $messages;
}

// Allow direct CLI execution.
if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    foreach (seed_database() as $line) {
        echo $line . PHP_EOL;
    }
}
