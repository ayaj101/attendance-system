# Attendance & Overtime Management System

A professional, responsive Attendance & Overtime Management System that replaces
manual Excel attendance sheets. Built with **PHP 8**, **MySQL**, **Bootstrap 5**,
**jQuery**, **Chart.js**, **DataTables** and **Font Awesome** — ready to run on
**XAMPP** with no extra configuration.

## Features

- **Secure authentication** — login/logout, session management, password hashing
  (bcrypt), "Remember Me", CSRF protection, XSS protection and protected pages.
- **Dashboard** — summary cards (present/absent/half-day/leave/overtime), monthly
  attendance %, average working hours, daily attendance trend, overtime trend,
  monthly breakdown doughnut and recent activity feed.
- **Employee management** — full CRUD with photo upload, search, filter,
  pagination (DataTables), view, and confirmation before delete — all via AJAX.
- **Daily attendance** — pick a date, see all employees, set status / in-time /
  out-time / remarks. **Working hours and overtime are calculated automatically**
  (live in JavaScript, re-validated on the server in PHP).
- **Monthly attendance sheet** — Excel-style grid (employees × days) with color
  coding and per-employee totals; click a day to jump to that date's entry.
- **Reports** — Daily, Weekly, Monthly, Employee, Department, Late Arrival,
  Early Exit, Overtime, Leave and Summary reports. Each supports search, date /
  department / employee filters and **Print / Excel / PDF / CSV export**.
- **Employee profile** — basic info, monthly & yearly summary, total overtime,
  working hours, late arrivals, early exits, leaves and attendance history.
- **Departments** — CRUD with employee counts.
- **Settings** — configurable office start/end time, break duration, duty hours,
  company branding (name, logo, address, phone, email), light/dark theme, plus
  **database backup and restore**.
- **UX** — responsive sidebar, top navbar, breadcrumbs, toast notifications,
  confirm dialogs, loading spinner, dark/light theme, mobile-friendly tables.

## Working hours & overtime

```
Working Hours = (Out Time − In Time) − Break Time
Overtime      = max(0, Working Hours − Duty Hours)
```

Office timing, break duration and duty hours are configurable in **Settings**
(defaults: 08:00–17:00, 30 min break, 08:30 duty hours). Overnight shifts are
supported.

## Installation (XAMPP)

1. Copy the `attendance-system` folder into `xampp/htdocs/`.
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Open <http://localhost/attendance-system/install.php> and click
   **Run Installation**. This creates the database + tables and loads demo data
   (30 employees and a full month of attendance).
   - Alternatively import `database/attendance.sql` in phpMyAdmin and run
     `php database/seed.php` from the command line.
4. Open <http://localhost/attendance-system/> and log in.

### Default login

| Username | Password |
|----------|----------|
| `admin`  | `admin123` |

## Configuration

Database credentials live in `config/config.php` and default to the standard
XAMPP setup (`localhost`, user `root`, empty password, database
`attendance_system`). They can also be overridden with environment variables
(`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`).

## Project structure

```
attendance-system/
├── index.php  login.php  logout.php  install.php
├── dashboard.php  attendance.php  monthly.php
├── employees.php  departments.php  profile.php
├── reports.php  settings.php
├── config/      config.php  database.php  helpers.php  auth.php  report_query.php
├── api/         attendance/  employee/  report/  department/  settings/  auth/
├── includes/    header.php  navbar.php  sidebar.php  footer.php
├── assets/      css/  js/  img/  icons/
├── uploads/     photos/  logo/
└── database/    attendance.sql  seed.php
```

## Security

Prepared statements (PDO) everywhere, bcrypt password hashing, CSRF tokens on all
mutations, output escaping for XSS protection, server-side input validation and
session-based authentication guards on every page and API endpoint.

## Tech stack

PHP 8 · MySQL · Bootstrap 5 · jQuery · Chart.js · DataTables · Font Awesome
