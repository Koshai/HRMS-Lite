# First Cursor Agent prompt — copy everything below this heading

You are the implementation agent for **HRMS Lite**, a NEW, independent Laravel application. Your first assignment is **M0 bootstrap only**. Do not implement all modules in one pass.

## Required reading
1. Read `AGENTS.md`, then `AGENTS.local.md` if available.
2. Read `docs/PRODUCT_SPEC.md`, `docs/BUILD_PLAN.md`, `docs/REUSE_MAP.md`, and `docs/ACCEPTANCE_TESTS.md`.
3. Read the separate private wiki via the path in AGENTS.local.md: `HOME.md`, `CHARTER.md`, `ARCHITECTURE.md`, `DECISIONS.md`, `HANDOFF.md`.
4. Inspect the main HRMS at the local reference path (or its public GitHub repository). It is a read-only reference; inspect the authentication, UI, employee, attendance and test patterns before selecting reusable code.

## M0 delivery
- Confirm this is NOT the original HRMS repo, and create independent Git history if needed.
- Verify installed PHP, Composer, Node, MySQL/SQLite, and compare against actual Laravel framework requirements; report incompatibilities.
- Bootstrap a minimal, cPanel-compatible Laravel 13 app using an existing-compatible Blade / Livewire approach. Avoid paid UI assets and unnecessary runtime packages.
- Disable public registration, include authorization foundation for two roles, and implement a secure first-super-user command/seed mechanism with no hardcoded credentials.
- Establish tests, formatting/static checks, frontend build, and baseline routes/dashboard placeholders.
- Provide a `.env.example` containing only safe placeholders, a minimal setup README, and CI command plan.
- Run concrete verification and record outcomes; do not state success for unchecked commands.
- Update the local private wiki HANDOFF.md with exact status, decisions, lessons, tests, next step.
- Commit a small, coherent M0 change if Git is initialized and working tree is safe; report commit SHA.

## Hard constraints
- No changes to production HRMS, production database, real customer accounts or private files.
- No MCP implementation, official payroll, statutory calculations, leave/schedule/organization modules.
- Do not write sensitive fields into logs, fixtures or version control.
- Don't silently introduce multi-tenancy; one company per installation.
- If the private wiki is missing, report that rather than fabricating prior decisions.

## Final response format
1. What was actually built
2. Exact changed files and commit
3. Commands and verified test results
4. Open decisions/risks
5. Exactly one recommended M1 next slice
