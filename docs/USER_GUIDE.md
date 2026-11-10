# HRMS Lite — Draft user guide
This is the expected workflow; the application has not yet been implemented. Update this guide with accurate screenshots/labels as each feature ships.

## Super user
1. Sign in to the separately provisioned super-user account.
2. Open Employees > Add employee. Enter name, unique email, temporary password, phone, address, blood group, image, designation and monthly base salary. Save. Give the temporary password securely outside the app; the employee must change it on first sign-in.
3. Open Attendance. Select an employee and work date, input times and reason when recording manually. Or let the employee use self check-in/out.
4. Open Salaries. Select employee and month; confirm salary snapshot, choose bonus yes/no and bonus amount, then leave payment status Unpaid until the money was actually provided.
5. When salary has been paid outside this application, select Mark Paid. For corrections, enter a reason. See payment-status history for who changed it and when.
6. Deactivate employees who have left; do not erase historical data.

## Employee
1. Sign in with the account provisioned by the super user.
2. Change initial temporary password.
3. View My Profile and own attendance history; use Check In and Check Out buttons to record server time.
4. View My Salaries to see monthly salary, bonus and Paid/Unpaid status; contact the super user for corrections.

## Limitations
Paid/Unpaid is an administrative status entered by a human. This product does not send money, create official payslips, calculate taxes, or validate financial transfers.
