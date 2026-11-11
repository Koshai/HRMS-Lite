# HRMS Lite — User guide

Single-business portal for employees, daily attendance, and monthly salary **payment status** tracking (not payroll or bank transfers). Display timezone defaults to Asia/Dhaka; currency defaults to BDT.

## Super-user initial setup
1. After deployment/migration, create the first admin from SSH/terminal once:
   `php artisan hrms-lite:create-super-user "Owner Name" owner@example.com`
2. Sign in at `/login`. Public self-registration is disabled (`/register` is unavailable).
3. From the dashboard, use shortcuts to **Add Employee**, **Attendance**, or **Salaries**, or use the primary navigation.

## Create an employee
1. Open **Employees → Add employee**.
2. Required: name, unique email, temporary password, designation, monthly base salary.
3. Optional: phone, address, blood group, profile photo (JPEG/PNG/WebP, max 2 MB).
4. Save. The account is created atomically with a hashed password. The temporary password is never shown again in the app.

## How employees receive and change passwords
1. Share the temporary password out-of-band (phone, secure chat, in person).
2. On first sign-in the employee must open **Change your temporary password** before other pages.
3. After the change, they sign in normally with the new password.
4. A super user can **Reset password** on the employee detail page to issue a new temporary password (forced change again).

## Correct employee details
1. Open the employee from **Employees**.
2. Edit profile fields, designation, or base salary. Changing base salary does **not** rewrite past monthly salary snapshots.
3. Upload or replace the profile photo if needed. Photos are private and only visible to authorized viewers.
4. Use deactivate/reactivate as needed (see below).

## Daily attendance (employee self-service)
1. Employee opens **My Attendance** (or dashboard check-in controls).
2. **Check In** records the server time for the business work date.
3. **Check Out** closes the open punch. Duplicate check-in while open is rejected.
4. Employees cannot edit timestamps; only the super user can correct records.

## Manual attendance entry and correction
1. Super user opens **Attendance → Add attendance**.
2. Choose employee, work date, local check-in/out times, optional “check-out is next calendar day”, and a **required reason**.
3. Open a record → **Correct** to change times (work date stays fixed). Reason required. Concurrent stale edits are rejected.
4. Correction history is append-only under audit (actor, before/after, reason, time).

## Monthly salary and bonus
1. Open **Salaries → Add monthly salary**.
2. Choose employee and month. Amount defaults to current base salary; adjust if needed.
3. Optional bonus: enable **Has bonus** and enter amount. Bonus without the flag (or flag with zero when disallowed by validation) is rejected.
4. Record starts as **Unpaid**. A missing month is **not** counted as Unpaid on the dashboard.
5. Edit unpaid amounts with a reason; paid rows must be reversed before amount edits.

## Mark Paid and reverse payment status
1. On the salary detail page, **Mark Paid** confirms administrative payment tracking. Server stores `paid_at` and `paid_by`.
2. Confirm the browser dialog — Paid is not bank-transfer proof.
3. **Reverse to Unpaid** requires a reason. Prior paid details remain in audit history.
4. Employees see status on **My Salaries** but cannot change it.

## Deactivate / reactivate employees
1. On the employee detail page, deactivate staff who left. History (attendance, salaries, audits, photos metadata) is retained.
2. Inactive accounts cannot sign in, punch, or use existing sessions until reactivated.
3. Reactivate from the same page when they return.

## Recover access
| Situation | Action |
|---|---|
| Employee forgot password | Super user resets temporary password; employee must change it on next login |
| Super user locked out | Restore from backup or create access only via authorized ops with a disposable/admin procedure — do not expose public reset for bootstrap admin without policy |
| Lost private photos | Restore `storage/app/private` from the paired backup set (see `docs/BACKUP_RESTORE.md`) |
| Bad deploy | Roll back app files + DB dump taken before migration; re-run `php artisan hrms-lite:preflight` |

## Dashboards
- **Super user:** active employees, checked in today, current-month Paid/Unpaid counts and totals, shortcuts. Missing salary months are not Unpaid.
- **Employee:** name, designation, attendance status, check-in/out, latest salary status when present, shortcuts to profile/attendance/salaries.

## Common errors and troubleshooting
| Message / symptom | Likely cause | What to do |
|---|---|---|
| Validation on temporary password | Password policy (length/complexity) | Use a stronger temporary password |
| Duplicate check-in rejected | Open attendance already exists | Check out first |
| Stale version / concurrent edit | Another admin saved first | Reload the page and retry |
| Duplicate salary for month | One row per employee/month | Edit the existing Unpaid row |
| 403 on another employee URL | Authorization working | Employees may only open their own data |
| Photo will not upload | Type/size rules | JPEG/PNG/WebP ≤ 2 MB |
| Cannot Mark Paid | Already paid or wrong role | Employees cannot mark paid; reverse first to edit amounts |
| Login fails after deactivate | Expected | Reactivate the employee account |

## Security notes
- Public self-registration is disabled.
- Profile photos use private storage and authorized stream routes.
- Login attempts are rate-limited.
- Manual attendance and salary create/edit/payment write append-only audits.
- Salary concurrency uses an explicit `version` column.

## Limitations
Paid/Unpaid is administrative confirmation only. HRMS Lite does not send money, create official payslips, calculate taxes, or validate bank transfers. Default max attendance duration is 16 hours. One attendance record per employee per work date. One salary record per employee per month.
