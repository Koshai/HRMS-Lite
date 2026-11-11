import { test, expect } from '@playwright/test';
import { signInAsSuper } from '../helpers';

test('A23 mobile viewport navigation stays usable for super user', async ({ page }) => {
    await signInAsSuper(page);

    const nav = page.getByRole('navigation');
    await expect(nav.getByRole('link', { name: 'Dashboard' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Employees' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Attendance' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Salaries' })).toBeVisible();

    await nav.getByRole('link', { name: 'Employees' }).click();
    await expect(page.getByRole('heading', { name: /Employees/i })).toBeVisible();
    await expect(page.getByRole('link', { name: /Add employee/i })).toBeVisible();

    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/salaries');
    await expect(page.getByRole('heading', { name: /Salaries/i })).toBeVisible();
    await expect(page.getByRole('link', { name: /Add monthly salary/i })).toBeVisible();
    await page.screenshot({ path: 'test-results/mobile-salaries.png', fullPage: true });
});
