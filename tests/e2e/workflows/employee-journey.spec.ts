import { test, expect } from '@playwright/test';
import { changeForcedPassword, logout, otherEmail, password, signIn, signInAsSuper } from '../helpers';

test.describe.configure({ mode: 'serial' });

const employeeEmail = 'self.service@hrms-lite-e2e.example.invalid';
const tempPassword = 'Temp-Self-Passw0rd!!';
const permanentPassword = 'Perm-Self-Passw0rd!!';
let otherEmployeeId = '';

test('A22-01 provision dedicated employee and salary for self-service', async ({ page }) => {
    await signInAsSuper(page);
    await page.goto('/employees/create');
    await page.getByLabel('Name').fill('E2E Self Service');
    await page.getByLabel('Email').fill(employeeEmail);
    await page.getByLabel('Temporary password').fill(tempPassword);
    await page.getByLabel('Designation').fill('Cashier');
    await page.getByLabel(/Monthly base salary/).fill('28000.00');
    await page.getByRole('button', { name: 'Create employee' }).click();
    await expect(page.getByRole('heading', { name: 'E2E Self Service' })).toBeVisible();

    await page.goto('/salaries/create');
    await page.locator('#employee_id').selectOption({ label: 'E2E Self Service (active)' });
    await page.getByLabel('Month').fill('2026-10');
    await page.getByLabel('Reason').fill('October self-service salary');
    await page.getByRole('button', { name: 'Save monthly salary' }).click();
    await expect(page.getByText('Monthly salary saved as unpaid tracking record.')).toBeVisible();

    await page.goto('/employees');
    await page.getByRole('link', { name: 'E2E Other Employee' }).click();
    otherEmployeeId = new URL(page.url()).pathname.split('/').pop()!;
    await logout(page);
});

test('A22-02 forced password change then profile attendance salaries', async ({ page }) => {
    await signIn(page, employeeEmail, tempPassword);
    await changeForcedPassword(page, tempPassword, permanentPassword);
    await expect(page).toHaveURL(/\/dashboard$/);

    await page.goto('/my-profile');
    await expect(page.getByText(employeeEmail)).toBeVisible();
    await expect(page.getByText('Cashier')).toBeVisible();

    await page.goto('/my-attendance');
    await page.getByRole('button', { name: 'Check In' }).click();
    await expect(page.getByText(/Checked in for work date/i)).toBeVisible();
    await expect(page.getByRole('button', { name: 'Check Out' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Check In' })).toHaveCount(0);

    await page.getByRole('button', { name: 'Check Out' }).click();
    await expect(page.getByRole('heading', { name: 'History' })).toBeVisible();

    await page.goto('/my-salaries');
    await expect(page.getByText('2026-10')).toBeVisible();
    await expect(page.getByText('Unpaid')).toBeVisible();
});

test('A22-03 employee cannot access other employee records by URL', async ({ page }) => {
    await signIn(page, employeeEmail, permanentPassword);

    const foreignShow = await page.request.get(`/employees/${otherEmployeeId}`);
    expect(foreignShow.status()).toBeGreaterThanOrEqual(400);

    const foreignPhoto = await page.request.get(`/employees/${otherEmployeeId}/photo`);
    expect(foreignPhoto.status()).toBeGreaterThanOrEqual(400);

    const adminSalaries = await page.request.get('/salaries');
    expect(adminSalaries.status()).toBeGreaterThanOrEqual(400);

    await page.goto(`/employees/${otherEmployeeId}`);
    await expect(page.getByText('E2E Other Employee')).toHaveCount(0);
});
