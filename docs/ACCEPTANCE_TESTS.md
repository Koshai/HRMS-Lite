# Acceptance matrix
Automate feature and unit tests with PHPUnit. Use Playwright for a small set of browser-level journeys; retain existing HRMS `tests/e2e` conventions where appropriate.

| ID | Actor / journey | Expected |
|---|---|---|
| A01 | Anonymous visits /register | No public registration |
| A02 | Super user provisions employee | Employee row + hashed login account created atomically |
| A03 | Super user provides initial password | Employee must change it before accessing standard pages |
| A04 | Employee signs in with wrong password repeatedly | Rate limiting blocks brute force |
| A05 | Super user deactivates employee | Existing session and subsequent login denied |
| A06 | Employee opens another employee's profile URL | 403/404; no PII or images leaked |
| A07 | Employee tries POST to employee/salary admin routes | 403, no database changes |
| A08 | Photo MIME spoofed or too large | Rejected; no public file created |
| A09 | Employee checks in twice / concurrent submissions | Exactly one valid open attendance; duplicate safely handled — **covered M2A** |
| A10 | Employee checks out before check in | Rejected — **covered M2A** |
| A11 | Employee checks out once | Correct timestamp, timezone display, and work date — **covered M2A** |
| A12 | Super user manual attendance correction | Required reason, before/after and actor audited — planned M2B |
| A13 | Attendance time crossing midnight | Explicit overnight choice, valid UTC instants — self overnight covered M2A; admin explicit next-day M2B |
| A14 | Super user creates monthly salary | Unique month row, expected salary snapshot |
| A15 | Super user changes current base salary | Prior monthly salary remains unchanged |
| A16 | has_bonus=false and bonus_amount>0 | Rejected or normalized to zero according to contract |
| A17 | Mark salary paid | status paid, paid_at and paid_by set, audit appended |
| A18 | Undo paid | Reason required, audit preserves prior paid history |
| A19 | Duplicate salary same employee/month | One row only, readable validation error |
| A20 | Employee views My Salaries | Own records only, cannot change paid status |
| A21 | Browser: admin end-to-end workflow | Create employee > attendance > salary paid status |
| A22 | Browser: employee end-to-end workflow | First login > password change > self punch > own salary |
| A23 | Browser: mobile viewport | Functional navigation and no offscreen action buttons |
| A24 | Deployment: backup/restore drill | Restored DB retains users, attendance, salary and audit |

Test on SQLite then targeted MySQL tests for transactions, uniqueness, and concurrency. Never put real employee data in screenshots or fixtures.
