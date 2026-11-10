# Existing HRMS reuse assessment
**Inspected:** public source in `Koshai/HRMS` on 2026-11-10. Validate file paths/version before transplanting. Do not copy the whole HRMS into Lite.

## Reuse or adapt
| Reference in main HRMS | HRMS Lite treatment |
|---|---|
| `composer.json` — Laravel 13 / PHP 8.3 / Fortify / Livewire / Tailwind/Flux stack | Use compatible versioned stack; retain only dependencies actually required |
| `app/Models/User.php` and Fortify actions | Reuse patterns, reduce to two roles, disable public registration, introduce first-password-change |
| `resources/views/layouts/`, `resources/views/components/`, authenticated navigation | Selectively adapt reusable Blade UI and responsive styling; remove inactive modules |
| `app/Employees/EmployeeProfile.php`, `resources/views/employees/` | Simplify profile/CRUD; no employment assignments or org hierarchy |
| `app/Attendance/AttendanceService.php`, `resources/views/attendance/` | Reuse server-time punch, validation, audit and concurrency approaches; simplify shifts and corrections |
| `resources/views/payroll/` | Reuse only generic table, formatting, and display patterns, **not** business payroll code |
| `tests/Feature`, `tests/Unit`, `tests/e2e` | Copy test *patterns*, fixtures, permission assertions; rewrite for Lite schema and roles |
| `docs/user-guide/portal.md`, `docs/operations/deployment.md` | Adapt documentation conventions and cPanel constraints to Lite |
| `AGENTS.md`, local private wiki convention | Preserve governance process, create separate HRMS Lite project knowledge |

## Do not transplant
- Organization/department/position/location hierarchy unless later required
- Compensation agreements, payroll runs, finalized entries, statutory deductions, compliance packs
- Scheduling/leave approvals, manager/HR role hierarchy, company tenancy implementation
- Production data, `.env`, APP_KEY, email addresses, uploads, password hashes

## Source references
- https://github.com/Koshai/HRMS
- https://github.com/Koshai/HRMS/blob/main/composer.json
- https://github.com/Koshai/HRMS/blob/main/AGENTS.md
- https://github.com/Koshai/HRMS/tree/main/tests/e2e
