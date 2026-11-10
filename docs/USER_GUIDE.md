# HRMS Lite — User guide

Accurate for **M1** (employee provisioning) and **M2A** (employee self-attendance). Admin manual attendance is M2B; salary remains planned.

## Super user
1. Sign in with the account created by `php artisan hrms-lite:create-super-user`.
2. Open **Employees → Add employee**. Enter name, unique email, temporary password, optional phone/address/blood group/photo, required designation and monthly base salary (BDT). Save.
3. Share the temporary password securely outside the app. The password is hashed immediately and cannot be viewed again in HRMS Lite.
4. Open an employee record to edit details, reset a temporary password, deactivate, or reactivate.
5. Deactivate staff who have left. Historical employee records remain; login and existing sessions are blocked until reactivation.
6. Open **Attendance** to list and filter all employee attendance records. Manual create/correct (with required reason and audit) ships in M2B.
7. Salary workflows ship in a later milestone.

## Employee
1. Sign in with the email and temporary password from the super user.
2. You are redirected to **Change your temporary password**. Enter the current temporary password and a new password. Logout remains available on that screen.
3. After changing the password, open **My Profile** to view your own details, including blood group and base salary.
4. Open **My Attendance** to check in or check out. Timestamps come from the server (shown in Asia/Dhaka by default). You cannot edit times.
5. If you already have an open attendance (including from a previous day), you must check out before checking in again.
6. You cannot open other employees’ profiles, photos, attendance, or admin routes.
7. Salary history ships in a later milestone.

## Security notes
- Public self-registration is disabled.
- Profile photos are stored in private application storage and served only through authorized routes.
- Login attempts are rate-limited.
- Inactive accounts cannot authenticate or punch attendance.
- Attendance stores UTC instants; the UI displays the installation timezone.

## Limitations
Paid/Unpaid salary status (future module) is an administrative confirmation only. This product does not send money, create official payslips, calculate taxes, or validate financial transfers. Maximum single attendance duration defaults to 16 hours (`HRMS_ATTENDANCE_MAX_HOURS`).
