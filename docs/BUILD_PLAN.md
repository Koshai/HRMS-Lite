# Cursor implementation plan — tested vertical slices
Do not represent this as implemented until it exists and tests pass. Each phase adds documentation, migrations, policies, functional/browser tests, and a wiki handoff.

## M0 — Bootstrap and governance
- Create independent Git repository; Laravel with existing-HRMS-compatible framework and lightweight Livewire/Blade stack.
- Bring in AGENTS.md + .gitignore; keep AGENTS.local.md untracked and external wiki linked.
- Configure SQLite test DB + local MySQL, timezone Asia/Dhaka, currency BDT.
- Set format, PHP/static checks, PHPUnit and optional Playwright runner. Set GitHub CI.
- Add first-super-user CLI command, no public registration.
**Gate:** clean install, homepage/login works, tests/build pass, read-only source reference documented.

## M1 — Login, super-user and employee provisioning
- Fortify session login/logout, secure password hashing, throttling, role policies.
- Create employee form (all required user fields), private photo storage and guarded serving.
- Provision with temporary password, force change on first login; admin reset without reveal.
- Edit/deactivate employees; block deactivated sessions.
**Gate:** admin can create employee and employee can sign in; authorization and file-upload attacks fail in tests.

## M2 — Attendance
- Create daily records with server-time employee self check-in/out and admin manual entry.
- Admin reports/list/filter by employee/date; employee sees own entries.
- Manual entry correction audit; concurrent/double-punch checks; explicit overnight next-day choice.
**Gate:** 1/day uniqueness, no unauthorized access, correct timezone and duplicate request behavior.

## M3 — Monthly salary tracking
- Create monthly row manually or via explicit bulk 'generate unpaid rows for month' action (optional if useful).
- Snapshot base salary per employee/month; bonus toggle and amount; paid/unpaid toggle with paid_by/paid_at and audit.
- Admin month summary, filters, CSV export only if user asks; employee own salary history.
**Gate:** unique (employee,month), salary history survives base salary changes, concurrency safe, employee cannot edit status.

## M4 — Dashboard, acceptance and deployment
- Lightweight two-role dashboards, mobile-friendly tables/forms. **M4B done.**
- Complete Playwright journeys, unit/feature tests, docs/user guide and human acceptance record. **M4A–M4D done** (see `docs/CLIENT_ACCEPTANCE_REPORT.md`).
- cPanel staging deployment + HTTPS, DB backups and a verified restore drill; no production deployment without approval. **M4C preflight + docs + disposable restore drill done; live cPanel host outstanding.**
**Gate:** complete admin and employee workflow, negative authorization, browser review, staging preflight — **met on disposable environments; live host pending.**

## Delivery cadence
Use 1 coherent commit per verified slice. Update `../hrms-lite-wiki/HANDOFF.md` with last commit, state of tests, what changed, unfinished items, exact next step. Do not start the next phase unless the previous acceptance gate is objectively met or a documented exception is approved.
