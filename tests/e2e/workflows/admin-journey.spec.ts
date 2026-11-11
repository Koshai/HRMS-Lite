import { test, expect } from '@playwright/test';
import { fixturePng, logout, password, signIn, signInAsSuper } from '../helpers';

test.describe.configure({ mode: 'serial' });

const employeeEmail = 'journey.employee@hrms-lite-e2e.example.invalid';
const tempPassword = 'Temp-Journey-Passw0rd!';
let employeePath = '';
let salaryPath = '';

test('A21-01 super user creates and edits employee with photo', async ({ page }) => {
    await signInAsSuper(page);

    await page.goto('/employees/create');
    await page.getByLabel('Name').fill('E2E Journey Employee');
    await page.getByLabel('Email').fill(employeeEmail);
    await page.getByLabel('Temporary password').fill(tempPassword);
    await page.getByLabel('Phone').fill('01710000001');
    await page.getByLabel('Address').fill('Synthetic demo address');
    await page.getByLabel('Designation').fill('Clerk');
    await page.getByLabel(/Monthly base salary/).fill('45000.00');
    await page.getByLabel('Profile image').setInputFiles(fixturePng());
    await page.getByRole('button', { name: 'Create employee' }).click();

    await expect(page.getByRole('heading', { name: 'E2E Journey Employee' })).toBeVisible();
    employeePath = new URL(page.url()).pathname;
    await expect(page.getByAltText('Profile photo')).toBeVisible();

    await page.getByRole('link', { name: 'Edit' }).click();
    await page.getByLabel('Designation').fill('Senior Clerk');
    await page.getByRole('button', { name: 'Save changes' }).click();
    await expect(page.getByText('Senior Clerk')).toBeVisible();

    await page.getByPlaceholder('New temporary password').fill(tempPassword);
    await page.getByRole('button', { name: 'Reset password' }).click();
    await expect(page.getByText(/Yes|temporary|reset/i).first()).toBeVisible();
});

test('A21-02 admin manual attendance create and correct with audit', async ({ page }) => {
    await signInAsSuper(page);
    await page.goto('/attendance/create');
    await page.locator('#employee_id').selectOption({ label: 'E2E Journey Employee' });
    await page.getByLabel('Attendance date').fill('2026-11-09');
    await page.getByLabel('Check-in time').fill('09:00');
    await page.getByLabel('Check-out time (optional)').fill('17:00');
    await page.getByLabel('Reason').fill('Manual entry for browser acceptance');
    await page.getByRole('button', { name: 'Save attendance' }).click();

    await expect(page.getByText('Manual attendance saved.')).toBeVisible();
    await page.getByRole('link', { name: 'Correct' }).click();
    await page.getByLabel('Check-in time').fill('09:15');
    await page.getByLabel('Reason').fill('Corrected from gate log');
    await page.getByRole('button', { name: 'Save correction' }).click();
    await expect(page.getByText('attendance.manual_correct')).toBeVisible();
    await expect(page.getByText('Corrected from gate log')).toBeVisible();
});

test('A21-03 admin salary create, bonus, mark paid, reverse with audit', async ({ page }) => {
    await signInAsSuper(page);
    await page.goto('/salaries/create');
    await page.locator('#employee_id').selectOption({ label: 'E2E Journey Employee (active)' });
    await page.getByLabel('Month').fill('2026-11');
    await page.getByLabel(/Salary amount/).fill('45000.00');
    await page.getByLabel('Has bonus').check();
    await page.locator('#bonus_amount').fill('1500.00');
    await page.getByLabel('Reason').fill('November tracking with festival bonus');
    await page.getByRole('button', { name: 'Save monthly salary' }).click();

    await expect(page.getByText('Monthly salary saved as unpaid tracking record.')).toBeVisible();
    salaryPath = new URL(page.url()).pathname;
    await expect(page.getByText('Unpaid').first()).toBeVisible();
    await expect(page.getByText('1500.00').first()).toBeVisible();

    await page.getByRole('button', { name: 'Mark Paid' }).click();
    await expect(page.getByText(/Marked as paid/i)).toBeVisible();
    await expect(page.getByText('salary.mark_paid')).toBeVisible();
    await expect(page.getByText(/Confirmed \d{4}-\d{2}-\d{2}/)).toBeVisible();

    await page.locator('#reverse_reason').fill('Bank entry was reversed in demo');
    await page.getByRole('button', { name: 'Reverse to Unpaid' }).click();
    await expect(page.getByText(/Reversed to unpaid/i)).toBeVisible();
    await expect(page.getByText('salary.mark_paid')).toBeVisible();
    await expect(page.getByText('salary.reverse_unpaid')).toBeVisible();
});

test('A21-04 deactivate employee blocks login', async ({ page }) => {
    expect(employeePath).not.toEqual('');
    await signInAsSuper(page);
    await page.goto(employeePath);
    page.once('dialog', (dialog) => dialog.accept());
    await page.getByRole('button', { name: 'Deactivate' }).click();
    await expect(page.getByText('Inactive')).toBeVisible();

    await logout(page);
    await signIn(page, employeeEmail, tempPassword);
    await expect(page).toHaveURL(/\/login/);
});
