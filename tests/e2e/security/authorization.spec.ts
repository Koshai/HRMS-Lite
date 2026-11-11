import { test, expect } from '@playwright/test';
import { otherEmail, password, signIn, signInAsSuper } from '../helpers';

test('guest cannot open dashboard or register', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login/);

    const register = await page.request.get('/register');
    expect([404, 405, 302]).toContain(register.status());
});

test('invalid password stays signed out', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email').fill(process.env.E2E_SUPER_EMAIL!);
    await page.getByLabel('Password', { exact: true }).fill('Wrong-Fictional-Password-2026!');
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL(/\/login/);
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login/);
});

test('employee role cannot open admin create routes', async ({ page }) => {
    await signIn(page, otherEmail(), password());
    await expect(page).toHaveURL(/\/dashboard$/);

    for (const path of ['/employees/create', '/attendance/create', '/salaries/create']) {
        const response = await page.request.get(path);
        expect(response.status(), path).toBeGreaterThanOrEqual(400);
    }
});

test('super user can open admin routes', async ({ page }) => {
    await signInAsSuper(page);
    await page.goto('/employees');
    await expect(page.getByRole('heading', { name: /Employees/i })).toBeVisible();
    await page.goto('/attendance');
    await expect(page.getByRole('heading', { name: /Attendance/i })).toBeVisible();
    await page.goto('/salaries');
    await expect(page.getByRole('heading', { name: /Salaries/i })).toBeVisible();
});
