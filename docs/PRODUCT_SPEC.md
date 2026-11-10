# Product specification — HRMS Lite v0.1
**Status:** Proposed MVP contract (validate during kickoff). **Date:** 2026-11-10.

## Goal & non-goals
One small business per deployment, 3–50 staff as a target. Super user provisions employees, manages daily attendance records, and marks each month's salary paid or unpaid. Employees sign in to view their own information, attendance, and salary history. They can check in/out with server time. No payroll engine, taxation, statutory reports, leave, shift rosters, company org charts, or payment processing. No MCP in MVP.

## Roles & permissions
| Action | Super user | Employee |
|---|---:|---:|
| Provision / deactivate employees | Yes | No |
| Set initial employee password / reset access | Yes | No |
| View/edit employee HR profile & salary | All | Own read-only (except own password change) |
| View attendance | All | Own only |
| Check in/out | Own (if linked to profile) or admin manual entry | Own server-time punch |
| Enter/correct attendance manually | All employees; reason and audit | No |
| Create/edit monthly salary records | Yes | No |
| Mark salary as paid/unpaid | Yes, audited | No |
| View salary history | All | Own only |
| Promote a super user | Controlled bootstrap/admin action only | No |

Never expose salary or blood group through employee directory rows visible to other employees.

## Employee form
**User-facing fields:** employee name, email, password (at creation or reset), phone number, address, blood group, profile image, designation, base monthly salary.
- Name required, email required/unique and normalized, password required on new account (or an administrator-provisioned secure setup flow), phone optional, address optional, blood group optional, profile image optional (jpeg/png/webp size cap), designation required, salary required nonnegative BDT decimal(12,2).
- Password is **not** an employee database profile field: store a hash only in `users` and never return it in responses.
- Admin creates a temporary password with `must_change_password` flag or issues a one-time setup link if email transport is configured. On first login employee must change it. Super user cannot retrieve a password; only reset it.
- Archive/deactivate rather than hard-delete employees with attendance or salary history; block inactive account login and existing sessions.
- Images are stored privately and served through an authorization-gated endpoint.

## Attendance form
**Fields:** date, check-in time, check-out time. Employee is implicit in self-service; a super user selects an employee when entering on their behalf.
- Self-service: click Check In / Check Out; server supplies immutable current timestamp, never client time. One active open attendance per employee; one work-date row per employee in MVP.
- Super user: manually enter/correct date and times (using configured business time zone), with required reason and audit. Check-out may be initially empty.
- Persist UTC datetime instants (`check_in_at`, `check_out_at`), derive `work_date` in configured installation timezone. Show local date/time in UI.
- For overnight shifts, a super user must explicitly select next-day checkout (or complete date) rather than silently inferring it. Validate checkout after checkin and maximum allowed shift window. UI can keep compact time fields plus a small next-day toggle.
- Protect against double submissions/concurrent check-in/out; enforce uniqueness and transactional transitions.

## Salary form
**Fields:** employee, salary, month, given_status, has_bonus, bonus_amount.
- `month`: year-month, stored as first-day-of-month `salary_month` DATE.
- One record per employee/month, enforced by unique database constraint.
- `salary_amount`: snapshot decimal(12,2) of that month's base amount. Do **not** revise prior records when an employee's current salary changes.
- `given_status`: `unpaid`/`paid`. Default `unpaid`; paid is a **manual confirmation**, not financial disbursement proof.
- `has_bonus`: boolean. `bonus_amount`: decimal(12,2) default 0 and must be 0 when `has_bonus=false`.
- Display total = salary_amount + bonus_amount. Upon marking `paid`, store `paid_at`, `paid_by`. Upon reversing to unpaid, record a required reason; preserve audit trail of prior paid state, actor, timestamp and before/after values.
- No tax, deduction, leave, pro-rata, payroll run, payslip, bank transfer, or automatic payment in MVP.

## Database proposal
`users`: id, name, email unique, password hash, role enum(`super_user`,`employee`), must_change_password, is_active, timestamps.
`employees`: id, user_id unique FK, phone, address, blood_group, image_path, designation, base_salary_amount DECIMAL(12,2), active, timestamps.
`attendance_records`: id, employee_id FK, work_date DATE, check_in_at UTC datetime, check_out_at UTC nullable datetime, created_by FK, updated_by FK nullable, timestamps; UNIQUE(employee_id, work_date).
`monthly_salaries`: id, employee_id FK, salary_month DATE, salary_amount DECIMAL(12,2), given_status enum(`unpaid`,`paid`), has_bonus boolean, bonus_amount DECIMAL(12,2), paid_at nullable, paid_by nullable FK, timestamps; UNIQUE(employee_id,salary_month).
`audit_events`: id, actor_user_id FK, entity_type, entity_id, action, before_json, after_json, reason nullable, created_at. Never log hashes, temporary passwords, session tokens, or raw image content.

## Navigation
Super user: Dashboard, Employees, Attendance, Salaries, My Account. Employee: Dashboard, My Profile, My Attendance, My Salaries, Change Password.

## Small-business dashboard
Counts: total active employees, checked in today, monthly paid / unpaid count, sum of paid/unpaid monthly salary amounts. Avoid claiming these totals are actual bank payments.

## Operations & privacy
CSRF, session hardening, login rate limiting; policies for every read/write; validation and output escaping; audit; profile image MIME/content checks; HTTPS and secure cookies in production. Business setting: `Asia/Dhaka`, currency BDT by default. Do not store production PII in test fixtures.

## Deferred decisions (not implemented)
- MCP / UniversalMcpGateway participation
- Multi-tenant database and billing
- Leave, overtime, multiple daily shifts, approval workflows
- Official payroll, statutory rules, payment integration
- Email onboarding vs temporary password onboarding defaults
