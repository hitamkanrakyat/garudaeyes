Garuda Eyes - Migrated MVC PHP App

This repository contains a modernized migration of an older PHP app into a small MVC layout with security improvements, single-session enforcement, CSRF protection, and LAMPP deployment helpers.

Quick facts
- Default seeded admin: username: admin  email: admin@example.local
- Default seeded password: Admin@Garuda2025!
  *Change this password after first login.*

Required: PHP 7.4+ (8.x recommended), MySQL/MariaDB, LAMPP (for target deploy).

Setup locally (development)
1) Import DB schema:
   mysql -u root < garuda.sql
   or using LAMPP's mysql: /opt/lampp/bin/mysql -u root < garuda.sql

2) Configure DB credentials if needed: edit `app/config.php` and set DB_USER/DB_PASS/DB_NAME.

3) Run built-in PHP server for quick testing:
   php -S localhost:8000 -t .

4) Visit: http://localhost:8000/login (or / if served from subfolder)

Deploy to LAMPP (on same machine)
1) Run the deploy script (needs sudo):
   sudo bash deploy.sh
   This will:
   - create timestamped backup of existing /opt/lampp/htdocs/garudaeyes
   - rsync repository contents to /opt/lampp/htdocs/garudaeyes
   - set file permissions
   - restart LAMPP

Smoke tests
- After deploy, run:
  ./tests/smoke.sh
  or run the commands:
  curl -sS http://localhost/garudaeyes/login | grep -q 'name="csrf_token"' && echo OK
  curl -sS http://localhost/garudaeyes/login | grep -E "action=\"/garudaeyes/login\"" && echo action OK

Notes & assumptions
- The application stores a `current_session` per user to enforce single active session; when a new login occurs, older sessions are invalidated.
- Legacy MD5 passwords are detected (32 hex chars) and migrated automatically on first successful login.
- All DB access uses PDO with prepared statements.
- BASE_PATH is auto-detected by `init.php` so the app can be installed in a subfolder like `/garudaeyes`.

Next recommended steps
- Move inline styles to a proper CSS file and serve static assets with correct caching headers.
- Add proper logging (instead of silent catches) and centralize error handling.
- Consider rate-limiting at IP + username level using Redis or DB table for production.

If you need me to run the deploy and smoke tests here, allow me to run `sudo bash deploy.sh` (requires sudo).
# garudaeyes
