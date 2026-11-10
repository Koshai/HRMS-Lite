# AGENTS.md — HRMS Lite

Read `AGENTS.local.md` when present (Cursor/Codex may not discover it automatically). Then read `docs/PRODUCT_SPEC.md`, `docs/BUILD_PLAN.md`, and the private wiki identified in the local file. This file is the repository's **operational entry point**, not the design/history corpus.

## Product boundaries
- Single business per installation. Exactly two roles: `super_user`, `employee`.
- Three modules only: employees, daily attendance, monthly salary payment tracking.
- Do not import payroll processing, leave, schedule, organizational hierarchy, statutory calculation, or universal MCP dependencies from the full HRMS.
- Treat the main HRMS repository as **read-only reference**. Copy/adapt only reviewed, necessary files; never share its database, private configuration, or secrets.

## How to work
1. Inspect existing files, migrations, tests, policies, and relevant HRMS reference implementation.
2. Define one small, demonstrable feature slice with acceptance criteria.
3. Implement migration, model, authorization, validation, UI, and tests for that slice.
4. Run relevant unit/feature tests and browser tests when UI changes; run format/static checks; report actual results.
5. Update the user guide when workflow changes and private wiki when design, decisions, or lessons change.
6. End with changed files, test evidence, known gaps, and the next recommended slice.

## Rules
- No public self-registration. A super user provisions employees. Never store/display plaintext passwords after creation; hash using Laravel facilities.
- Employee access must be server-authorized for **own** profile, attendance, and salary only; never rely on hidden UI links.
- Salary payments are **tracking only**: `unpaid` or `paid`, not a real disbursement or official payroll record.
- No silent change of historical salary snapshots or attendance. Material corrections need actor/time/reason audit.
- Avoid new runtime packages, background jobs, queues, external services and API layers without concrete MVP requirements.
- No destructive database reset, production migration, or deployment without approval and environment verification.
- Never commit `AGENTS.local.md`, `.env`, credentials, real employee PII, photos, or production dumps.

## Usual checks (adapt to actual installed dependencies)
- `php artisan test`
- `composer test` (if defined)
- `./vendor/bin/pint --test`
- `npm run build`
- `npx playwright test` (if configured)

## Reporting template
State what changed; tests and results; user-visible behavior; security/authorization notes; assumptions; wiki updates; next testable slice. Never claim tests were run unless they were.
