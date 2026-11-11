# HRMS Lite — cPanel / Namecheap deployment runbook

Target: affordable shared hosting with PHP 8.3+, MySQL, and Composer (SSH or terminal).  
This product is one business per installation. Do **not** place `.env`, `storage/`, or private photos under the public web root.

## 1. Create MySQL database and restricted user
1. In cPanel → MySQL Databases, create database e.g. `biz_hrms_lite`.
2. Create a user with a strong password; grant that user **only** ALL PRIVILEGES on this database.
3. Note host (usually `localhost`), database name, username, password.

## 2. Upload the application
1. Prefer Git clone or upload a release archive **outside** `public_html` when possible, e.g. `/home/USER/apps/hrms-lite`.
2. Ensure the tree includes `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `storage/`, `artisan`, `composer.json`, `composer.lock`, and prebuilt `public/build/` assets.

## 3. Document root must expose only `public/`
1. Point the domain/subdomain document root to `.../hrms-lite/public`.
2. If the host forces `public_html`, put the Laravel app beside it and symlink/copy only `public/*` carefully, or use an addon domain pointing at `public/`.
3. Confirm `http(s)://your-domain/` serves Laravel, not a directory listing of the project root.

## 4. Install production Composer dependencies
```bash
# Use the host PHP 8.3+ CLI (example path varies)
/usr/local/bin/php83 /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction
```
Required PHP extensions: `bcmath`, `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`.

## 5. Deploy precompiled frontend assets
On a build machine (or CI):
```bash
npm ci
npm run build
```
Copy `public/build/` to the server. Do not rely on running Vite on shared hosting.

## 6. Production environment configuration
1. Copy `.env.example` to `.env` **outside** the web root (project root).
2. Set at minimum:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://your-domain.example`
   - `APP_KEY=` (generate with `php artisan key:generate`)
   - `DB_CONNECTION=mysql` and credentials
   - `SESSION_SECURE_COOKIE=true` when HTTPS is enabled
   - `HRMS_DISPLAY_TIMEZONE=Asia/Dhaka`
   - `HRMS_CURRENCY=BDT`
3. Never commit `.env`. Never put `.env` inside `public/`.

## 7. Apply migrations safely
```bash
php artisan migrate --force
php artisan hrms-lite:preflight
```
Do not run `migrate:fresh` / `migrate:refresh` on a business database.

## 8. Bootstrap the first super user
```bash
php artisan hrms-lite:create-super-user "Owner Name" owner@example.com
```
Enter the password interactively. It is hashed immediately and not logged.

## 9. Verify login and private files
1. Sign in at `/login`.
2. Create a synthetic employee with a photo.
3. Confirm the photo URL requires authorization.
4. Confirm `/register` is unavailable.
5. Confirm inactive employees cannot sign in.

## 10. HTTPS, backups, logs
1. Force HTTPS in cPanel / AutoSSL / Let’s Encrypt.
2. Schedule daily backups of MySQL **and** `storage/app/private`.
3. Rotate `storage/logs` and keep APP_DEBUG=false.
4. After each deploy: `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache` (optional), then `php artisan hrms-lite:preflight`.

## Rollback
1. Restore previous application files (keep matching `public/build`).
2. Restore the MySQL dump taken before migration if a migration failed mid-way.
3. Restore `storage/app/private` from the same backup set.
4. Clear caches and re-run preflight.

## Hosting acceptance status
If a specific cPanel account was not available during development, treat this document as verified against local/staging disposable environments. Mark real Namecheap/cPanel acceptance **outstanding** until an authorized host preflight succeeds.
