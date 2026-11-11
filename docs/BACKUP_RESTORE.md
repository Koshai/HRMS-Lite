# Backup and restore drill

## What to back up
1. MySQL (or SQLite) database dump containing users, employees, attendance, salaries, audits.
2. Private photos: `storage/app/private/` (recursive).
3. `.env` stored securely offline (never in public backups shared broadly).

## Backup procedure (MySQL example)
```bash
mysqldump -u USER -p --single-transaction --routines biz_hrms_lite > hrms-lite-YYYYMMDD.sql
tar -czf hrms-lite-private-YYYYMMDD.tgz -C /path/to/app storage/app/private
```

## Restore drill (disposable environment only)
1. Create an empty database `hrms_lite_restore_drill`.
2. Import the dump.
3. Extract private photos into `storage/app/private`.
4. Point a disposable `.env` at the restore database.
5. Run `php artisan migrate --force` only if the dump predates new migrations; otherwise skip.
6. Run `php artisan hrms-lite:preflight`.
7. Sign in as the restored super user and verify:
   - Employees list
   - Attendance history
   - Salary Paid/Unpaid history
   - Audit events on corrected attendance / salary
   - Authorized photo still loads; unauthorized requests fail

## Evidence template
- Date/time of drill:
- Source backup identifiers:
- Destination environment:
- Preflight result:
- Smoke checks (employees/attendance/salary/audit/photo):
- Outcome: pass / fail

## Disposable drill evidence (M4C)
- Date/time: 2026-11-10 (local developer machine)
- Method: PHPUnit `Tests\Feature\BackupRestoreDrillTest` — in-memory SQLite snapshot exported to disposable `database/testing-backup-drill.sqlite` plus private photo file copy
- Source: synthetic admin, employee, closed attendance, paid salary, `salary.mark_paid` audit, private photo bytes
- Destination: isolated SQLite file + `storage/app/backup-drill-private` (deleted in tearDown)
- Preflight: `php artisan hrms-lite:preflight` on local app env (read-only)
- Smoke checks: users ≥2, employees/attendance/salaries = 1, audits ≥1, paid status retained, photo bytes match
- Outcome: **pass** on disposable environment
- Host-specific cPanel/Namecheap restore: **outstanding** (no authorized production host access)

Never run restore drills against a live client database without explicit authorization.
