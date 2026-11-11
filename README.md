# HRMS Lite

Independent Laravel 13 application for a single small business: employees, daily attendance, and monthly salary paid/unpaid tracking. Not a fork of the full HRMS and not a payroll engine.

## Requirements

- PHP 8.3+ (8.4/8.5 also fine) with `pdo_sqlite` for tests and `pdo_mysql` for staging/production; **BCMath** required
- Composer 2
- Node.js 20+ (24 used in CI)
- SQLite for local/tests; MySQL 8.x for staging parity

## Quick setup

```bash
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
php artisan hrms-lite:create-super-user "Your Name" "you@example.com"
php artisan serve
```

Sign in at `/login`. Public `/register` is disabled.

## Verification

| Command | Purpose |
|---|---|
| `composer ci:check` | Pint + PHPUnit (SQLite in-memory) |
| `php artisan test` | PHPUnit feature/unit suite |
| `npm run build` | Vite/Tailwind production assets |
| `npm run test:e2e` | Playwright journeys on disposable SQLite |
| `vendor/bin/phpunit -c phpunit.mysql.xml` | MySQL disposable suites (attendance + salary) |
| `php artisan hrms-lite:preflight` | Read-only production readiness checks |

## Documentation

- Product: `docs/PRODUCT_SPEC.md`, `docs/BUILD_PLAN.md`, `docs/ACCEPTANCE_TESTS.md`
- Operator manual: `docs/USER_GUIDE.md`
- Deployment: `docs/DEPLOYMENT_CPANEL.md`
- Backup/restore: `docs/BACKUP_RESTORE.md`
- Client acceptance: `docs/CLIENT_ACCEPTANCE_REPORT.md`

## Operations notes

- One business per installation; two roles only (`super_user`, `employee`).
- Paid/Unpaid is administrative confirmation — not bank-transfer proof.
- Never run tests or restore drills against a production database.
- Real cPanel/Namecheap host acceptance remains outstanding until an authorized preflight succeeds.

## Governance

- Read `AGENTS.md` and local `AGENTS.local.md` (untracked)
- Private design wiki: sibling `../hrms-lite-wiki`
- Full HRMS reference is read-only; do not share its database or secrets
