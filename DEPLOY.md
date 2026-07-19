# Deployment Guide

The app is standard PHP 8 + MySQL, so it runs on almost any host. Below are three
paths, easiest first.

> After deploying, **log in and immediately change the admin password** (default
> `admin` / `admin123`). Set the env var `APP_ENV=production` so PHP errors are
> hidden from visitors.

---

## Option A — InfinityFree (recommended, 100% free, no card)

Free PHP 8 + MySQL + phpMyAdmin + a free subdomain. Closest to XAMPP.

1. Sign up at <https://infinityfree.com> and **Create Account** → pick a free
   subdomain (e.g. `yourname.rf.gd`) or connect your own domain.
2. In the client area open **MySQL Databases** → create a database. Note the
   generated values: **DB host**, **DB name**, **DB user**, **DB password**.
3. Edit `config/config.php` (or set env vars) with those four values, e.g.:
   ```php
   define('DB_HOST', 'sqlXXX.infinityfree.com');
   define('DB_NAME', 'if0_XXXX_attendance');
   define('DB_USER', 'if0_XXXX');
   define('DB_PASS', 'your-db-password');
   ```
   Also set `define('APP_ENV', 'production');` at the top (or leave the env var).
4. Upload the project files to the `htdocs/` folder via the **File Manager** or
   FTP (host/user/password are in **FTP Accounts**). Upload the *contents* of
   `attendance-system/` into `htdocs/`.
5. Import the database: open **phpMyAdmin** from the client area, select your
   database, go to **Import**, and upload `database/attendance.sql`.
6. Load demo data (optional): visit `https://yoursite/install.php` — but on
   InfinityFree the DB user can't create databases, so instead just run the
   seed by temporarily uploading and hitting a small script, or skip demo data
   and add employees through the UI. (The schema import in step 5 already creates
   the admin user, settings and departments.)
7. Open `https://yoursite/` and log in with `admin` / `admin123`.

---

## Option B — Docker (Render, Railway, Fly.io, or any VPS)

A `Dockerfile` and `docker-compose.yml` are included.

### Local / VPS with Docker
```bash
docker compose up -d --build
docker compose exec app php database/seed.php   # optional demo data
# open http://localhost:8080/  (admin / admin123)
```
The MySQL schema is imported automatically on first boot.

### Render.com (free web service)
1. Push this repo to GitHub (already done).
2. Create a **MySQL** database (Render offers PostgreSQL free; for MySQL use a
   free provider such as Aiven/Clever Cloud, or Railway's MySQL) and copy its
   host/name/user/password.
3. On Render → **New → Web Service** → connect the repo → environment **Docker**.
4. Add environment variables: `APP_ENV=production`, `DB_HOST`, `DB_PORT`,
   `DB_NAME`, `DB_USER`, `DB_PASS`.
5. Deploy. Then import `database/attendance.sql` into your MySQL (via its web
   console or `mysql -h <host> -u <user> -p <db> < database/attendance.sql`).

### Railway
1. **New Project → Deploy from GitHub repo**, select this repo.
2. **Add → Database → MySQL**. Railway injects `MYSQLHOST`, `MYSQLDATABASE`, etc.
3. In the app service **Variables**, map them:
   `DB_HOST=${{MySQL.MYSQLHOST}}`, `DB_PORT=${{MySQL.MYSQLPORT}}`,
   `DB_NAME=${{MySQL.MYSQLDATABASE}}`, `DB_USER=${{MySQL.MYSQLUSER}}`,
   `DB_PASS=${{MySQL.MYSQLPASSWORD}}`, `APP_ENV=production`.
4. Import `database/attendance.sql` using Railway's MySQL connection string.

---

## Option C — Your own VPS (Ubuntu + Apache)

```bash
sudo apt update
sudo apt install -y apache2 php php-mysql mysql-server libapache2-mod-php
sudo a2enmod rewrite && sudo systemctl restart apache2

# Deploy code
sudo git clone https://github.com/ayaj101/attendance-system.git /var/www/html/attendance
sudo chown -R www-data:www-data /var/www/html/attendance/uploads

# Database
sudo mysql -e "CREATE DATABASE attendance_system; \
  CREATE USER 'attendance'@'localhost' IDENTIFIED BY 'STRONGPASS'; \
  GRANT ALL ON attendance_system.* TO 'attendance'@'localhost';"
sudo mysql attendance_system < /var/www/html/attendance/database/attendance.sql

# Configure env (e.g. in the vhost or an .htaccess SetEnv)
#   APP_ENV=production DB_USER=attendance DB_PASS=STRONGPASS
php /var/www/html/attendance/database/seed.php   # optional demo data
```
Point an Apache vhost `DocumentRoot` at `/var/www/html/attendance` and browse to it.

---

## Environment variables

| Variable  | Default            | Purpose                          |
|-----------|--------------------|----------------------------------|
| `APP_ENV` | `development`      | `production` hides PHP errors    |
| `DB_HOST` | `127.0.0.1`       | MySQL host                       |
| `DB_PORT` | `3306`            | MySQL port                       |
| `DB_NAME` | `attendance_system`| Database name                    |
| `DB_USER` | `root`            | Database user                    |
| `DB_PASS` | *(empty)*         | Database password                |
| `APP_TZ`  | `Asia/Kolkata`    | Default timezone                 |
