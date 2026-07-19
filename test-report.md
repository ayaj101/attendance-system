# Test Report — Attendance & Overtime Management System (PR #1)

**PR:** https://github.com/ayaj101/attendance-system/pull/1 (branch `devin/attendance-system`)
**Method:** End-to-end UI testing of the running app (PHP dev server at `http://127.0.0.1:8080`, MariaDB `attendance_system` seeded with 30 employees + one month of July 2026 attendance). Logged in as `admin`/`admin123`.
**Simulated date:** Sunday 2026-07-19. Attendance entry tested on weekday Friday **2026-07-17**.
**Result:** All 8 requested golden paths PASSED. No JS console errors. No bugs found.

---

## Summary of Results

| # | Test | Result |
|---|------|--------|
| T1 | Login / auth gate (reject wrong password, accept valid) | ✅ Pass |
| T2 | Dashboard: cards + 3 charts + recent activity | ✅ Pass |
| T3 | Employees CRUD (add/edit/view/delete + search) | ✅ Pass |
| T4 | Daily Attendance live calc (11:00 / 02:30) + persistence | ✅ Pass |
| T5 | Monthly Sheet grid + colored-cell navigation | ✅ Pass |
| T6 | Reports (Daily/Overtime/Summary) + Excel/CSV export | ✅ Pass |
| T7 | Departments add/edit/delete + delete guard | ✅ Pass |
| T8 | Settings save + persistence + dark/light theme | ✅ Pass |

---

## T1 — Login / Auth Gate ✅
- Wrong password (`admin` / `wrongpass`) → stayed on login with error **"Invalid username or password."**
- Valid `admin` / `admin123` → redirected to dashboard, navbar shows "System Administrator".

![Invalid credentials rejected](https://app.devin.ai/attachments/a63bd57e-6ea1-48d9-9276-21d53a2c8b06/ss_c0827604.png)

## T2 — Dashboard ✅
- Summary cards populate (Total Employees, Present/Absent/Half Day/Leave/Overtime Today). Present = 0 for today, expected since 2026-07-19 is Sunday.
- All three Chart.js charts render (daily trend line, overtime bar, breakdown doughnut). Recent activity list populated. No console errors.

![Dashboard cards + charts](https://app.devin.ai/attachments/a2c0555c-0081-484f-8eeb-a55a0770b9ea/ss_959d817a.png)

## T3 — Employees CRUD ✅
- DataTable loads with employees; search/filter works.
- Add Employee (`TST999` / `QA Tester`) → success toast + row appears.
- Edit, View modal, and Delete (with confirm dialog) all worked; test row cleaned up.

![Employee added with toast](https://app.devin.ai/attachments/c1481cf7-4ddb-4a9b-b162-dc780442d314/ss_b1298a9d.png)

## T4 — Daily Attendance (core) ✅
- Date 2026-07-17, employee set Present, In `08:00` / Out `19:30`.
- **Live calculation showed Working `11:00` and Overtime `02:30`** (gross 11:30 − 30 min break = 11:00; 11:00 − 08:30 duty = 02:30). Matches spec exactly.
- Save → success toast. Reloaded date → values persisted.

<table>
<tr><td><b>Live calc (before save)</b></td><td><b>After reload (persisted)</b></td></tr>
<tr>
<td><img src="https://app.devin.ai/attachments/9b80d374-98b1-4f40-9989-874cb057fda1/ss_zoom_8dd81b0b.png" width="420"></td>
<td><img src="https://app.devin.ai/attachments/6c11c3d3-552e-4656-a5bc-83b411762b90/ss_zoom_9651bfdb.png" width="420"></td>
</tr>
</table>

## T5 — Monthly Sheet ✅
- Excel-style grid renders employees × days with color-coded P/A/H/L/O/W cells and per-employee totals.
- Clicking a colored day cell navigates to `attendance.php?date=YYYY-MM-DD` for that date.

![Monthly grid](https://app.devin.ai/attachments/ff460598-1d61-4fa7-b668-e023c260de65/ss_8cd39bf0.png)

## T6 — Reports + Export ✅
- **Daily** (17 Jul): table populates with Code/Employee/Department/Status/In/Out/Working/Overtime/Remarks. EMP0014 (my saved entry) correctly shows 08:00 / 19:30 → 11:00 / 02:30.
- **Overtime**: distinct columns (OT Days / Total Overtime), 30 rows.
- **Summary**: Metric/Value rows (Active Employees 27, Total Present 350, Total Overtime 435:30, etc.).
- **Excel** and **CSV** export buttons both triggered downloads with correct content:
  - `summary_report_july_2026.csv` (valid CSV)
  - `summary_report_july_2026.xls` (HTML-table spreadsheet with matching data)

![Daily report 17 Jul](https://app.devin.ai/attachments/9044ba87-3fd2-49d1-9a6e-d2fdb2894217/ss_zoom_b2280700.png)

## T7 — Departments (delete guard) ✅
- Add `QA Dept` → toast + row. Edit (rename + revert) → update toast.
- **Delete guard verified:** deleting Engineering (6 employees assigned) was blocked with **"Cannot delete: employees are assigned to this department."**
- Deleting `QA Dept` (0 employees) succeeded with confirm dialog + toast.

![Department delete blocked](https://app.devin.ai/attachments/b8899df6-925a-4cbd-b155-d14670ad207e/ss_7a128bae.png)

## T8 — Settings + Theme ✅
- Changed Break (min) 30 → 45, Save → "Settings saved successfully." toast; reload confirmed persistence. Restored to 30.
- Navbar moon/sun toggle switched UI light ↔ dark (background/colors + icon flip). Reload confirmed theme persisted (settings.theme). Restored to light.

<table>
<tr><td><b>Dark theme applied</b></td><td><b>Dark theme persists after reload</b></td></tr>
<tr>
<td><img src="https://app.devin.ai/attachments/8811d905-3979-43d8-9ebc-ff1cd0b362a7/ss_fa5fac61.png" width="420"></td>
<td><img src="https://app.devin.ai/attachments/5f702e6a-c119-48ab-8b9e-8db4fc281041/ss_08f6dae6.png" width="420"></td>
</tr>
</table>

---

## Notes / Caveats
- The Reports date `<input type=date>` uses ISO value binding; typing into it required arrow-key segment navigation. This is browser input behavior, **not an app bug**.
- After selecting a report type that changes the filter set (e.g. Overtime uses Month/Year), the **Generate** button shifts down a row — expected layout reflow.
- All test data changes were reverted (settings, department name, theme). The one intentional attendance entry for EMP0014 on 2026-07-17 was left in place as test evidence.

## Environment / Setup performed
- `sudo service mariadb start`
- Schema/seed already present (30 employees, July 2026 attendance).
- `php -S 127.0.0.1:8080` from repo root.
