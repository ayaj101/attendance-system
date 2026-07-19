# Test Plan — Attendance & Overtime Management System (PR #1)

Environment: PHP dev server http://127.0.0.1:8080, MariaDB `attendance_system` seeded
(30 employees, 570 attendance rows, month = July 2026). Admin: admin/admin123.
Settings: office 08:00–17:00, break 30 min, duty 08:30. Today = Sun 2026-07-19.
Use weekday **2026-07-17 (Fri)** for attendance entry.

## T1 — Login (auth gate)
1. Open `/` → expect redirect to login.php.
2. Enter admin / **wrongpass** → Submit.
   - PASS: stays on login, visible error (e.g. "Invalid credentials"), NOT on dashboard.
3. Enter admin / admin123 → Submit.
   - PASS: redirects to dashboard.php, navbar shows user "Admin".

## T2 — Dashboard rendering
1. On dashboard, read summary cards.
   - PASS: "Total Employees" = 30 (non-zero). Present/Absent/Half Day/Leave/Overtime
     cards render numbers (Present may be 0 since today is Sunday — acceptable).
2. Verify 3 Chart.js canvases render pixels: daily trend (line), overtime (bar),
   breakdown (doughnut). PASS: all three draw non-empty; no blank boxes; no console errors.
3. Recent activity list shows ≥1 entry.

## T3 — Employees CRUD
1. Open employees.php → DataTable loads with 30 rows, search box present.
2. Add Employee: click Add, fill code `TST999`, name `QA Tester`, pick department,
   status Active, Save.
   - PASS: success toast; new row `TST999 / QA Tester` appears in table.
3. Search `TST999` → only that row shows.
4. Edit that employee: change name to `QA Tester Edited`, Save → toast; row updates.
5. View modal: opens showing the employee details.
6. Delete `TST999`: confirm dialog appears; confirm → toast; row removed; search empty.
   - (Cleanup of the test row.)

## T4 — Daily Attendance live calc + persistence (core)
1. Open attendance.php, set date = 2026-07-17.
2. For first employee row: Status=Present, In=`08:00`, Out=`19:30`.
   - PASS (live, before save): Working cell = **11:00**, Overtime cell = **02:30**.
     (gross 11:30 − 30 break = 11:00 worked; 11:00 − 08:30 duty = 02:30 OT.)
   - Adversarial: a broken calc would show a different value (e.g. 11:30/03:00 or 00:00).
3. Click Save → PASS: success toast.
4. Change date away then back to 2026-07-17 (reload).
   - PASS: that employee still Present, In 08:00, Out 19:30, Working 11:00, OT 02:30 (persisted).

## T5 — Monthly Sheet
1. Open monthly.php (year 2026, month July).
   - PASS: grid renders employees × days; cells color-coded with codes P/A/H/L/O/W;
     per-employee P/A/H/L + Hours + OT totals columns show values.
2. Verify the 2026-07-17 row for the edited employee reflects P (from T4).
3. Click a colored P/A/H/L day cell → PASS: navigates to attendance.php?date=YYYY-MM-DD
   for that exact date.

## T6 — Reports + export
1. Open reports.php. For each type, select and click Generate:
   - Daily (date 2026-07-17): table populates with Code/Employee/Status columns.
   - Employee: table with per-employee Present/Absent/Working/Overtime.
   - Overtime: table listing employees with OT Days + Total Overtime.
   - Summary: metric/value rows (Active Employees, Total Present, Total Overtime...).
   - PASS: each shows a non-empty table with the expected columns.
2. Click Excel export → PASS: a `.xls` file download starts (verify file lands in Downloads
   or via curl of the export URL returning spreadsheet content-type).
3. Click CSV export → PASS: a `.csv` download starts.

## T7 — Departments (delete guard)
1. Open departments.php → list renders.
2. Add department `QA Dept` → toast; appears in list.
3. Edit `QA Dept` name → toast; updates.
4. Attempt delete a department that HAS employees assigned (e.g. an existing seeded dept)
   → PASS: delete blocked with message (cannot delete, employees assigned).
5. Delete `QA Dept` (no employees) → PASS: confirm dialog, success, removed. (Cleanup.)

## T8 — Settings + theme toggle
1. Open settings.php. Change Company Name to `Acme QA Corp`, Save.
   - PASS: success toast. Reload → field still `Acme QA Corp`. (Restore to Acme Corporation after.)
2. Click navbar theme toggle (moon/sun icon).
   - PASS: page switches light↔dark (data-bs-theme changes, background/colors change,
     icon flips). Reload page → theme persists (read from settings.theme).

## Evidence
Record browser walkthrough T1–T8 with annotations. Capture screenshots of: live
11:00/02:30 calc, persisted reload, monthly grid, a generated report, dark theme.
Confirm downloads via filesystem/curl. Watch browser console for JS errors throughout.
