# HRMS Lite — Client acceptance report (MVP closure)

**Product:** HRMS Lite (single business; employees, attendance, salary status)  
**Repository:** `Koshai/HRMS-Lite`  
**Report date:** 2026-11-10  
**Milestone:** M4 Client Acceptance & Deployment Readiness  

## Scope confirmed
- Modules: employees, daily attendance, monthly salary Paid/Unpaid tracking.
- Roles: `super_user`, `employee` only.
- Out of scope: payroll calculations, leave, schedules, multi-tenancy, MCP, payment rails.

## Completed journeys (evidence)
| Journey | Evidence |
|---|---|
| A21 Super-user browser E2E | Playwright `e2e/admin-journey.spec.ts` (create employee, photo, edit, password reset, attendance + audit, salary paid/reverse, deactivate) |
| A22 Employee browser E2E | Playwright `e2e/employee-journey.spec.ts` (temp password, forced change, punch, history, own salary, unauthorized access) |
| A23 Mobile viewport | Playwright mobile project + M4B responsive nav/dashboard |
| Security negatives | Playwright `e2e/security.spec.ts` + PHPUnit authorization suites |
| A24 Backup/restore | Disposable SQLite drill `BackupRestoreDrillTest` + `docs/BACKUP_RESTORE.md` |
| Dashboards | M4B `DashboardController` + `DashboardTest` |
| Preflight | `php artisan hrms-lite:preflight` (read-only) |

Screenshots: Playwright failure artifacts only (synthetic fixtures). No real employee PII used.

## Operator walkthrough (no terminal after bootstrap)
After `hrms-lite:create-super-user`, a super user can complete employee provisioning, attendance, and salary Paid/Unpaid workflows entirely in the browser. Employees complete password change, self punch, and read-only profile/attendance/salary views in the browser.

## Deployment evidence
- Runbook: `docs/DEPLOYMENT_CPANEL.md`
- Preflight command implemented and tested (`PreflightCommandTest`)
- Local/disposable preflight: pass when Vite manifest present and migrations applied
- **Real cPanel/Namecheap host acceptance: OUTSTANDING** (no authorized production host in this milestone)

## Backup / restore evidence
- Procedure: `docs/BACKUP_RESTORE.md`
- Drill: synthetic users, attendance, paid salary, audit event, private photo retained after restore to disposable SQLite file
- Live client restore: not performed (requires explicit authorization)

## Outstanding issues / limitations
1. Live shared-hosting HTTPS/session preflight not executed on a customer cPanel account.
2. True multi-connection parallel DB stress is **not** claimed; stale-version and transactional audit coverage is in-process / sequential simulation.
3. Paid status is administrative confirmation only — not bank proof.
4. No email delivery of temporary passwords (out-of-band sharing).
5. Optional bulk “generate unpaid rows for month” remains deferred.

## Readiness verdict
| Level | Status |
|---|---|
| Client demonstration (synthetic data) | **Ready** |
| Controlled pilot on disposable/staging host | **Ready after** authorized host preflight + first backup schedule |
| General production use on customer cPanel | **Not claimed** until host-specific A24/preflight signed off |

Do not deploy to a live client environment without explicit authorization.
