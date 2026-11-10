# HRMS Lite

Independent Laravel 13 application for a single small business: employees, daily attendance, and monthly salary paid/unpaid tracking. Not a fork of the full HRMS and not a payroll engine.

## Requirements

- PHP 8.3+ (8.4/8.5 also fine) with `pdo_sqlite` for tests and `pdo_mysql` for staging/production
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

## Verification / CI command plan

| Command | Purpose |
|---|---|
| `composer lint:check` | Pint formatting gate |
| `php artisan test` | PHPUnit feature/unit suite (SQLite in-memory) |
| `composer ci:check` | Lint + tests (GitHub Actions `tests` workflow) |
| `npm run build` | Vite/Tailwind production assets |

Optional later: Playwright browser journeys (M4), MySQL-targeted concurrency tests (M2/M3).

## Governance

- Read `AGENTS.md` and local `AGENTS.local.md` (untracked)
- Product docs under `docs/`
- Private design wiki: sibling `../hrms-lite-wiki`
- Full HRMS reference is read-only; do not share its database or secrets
