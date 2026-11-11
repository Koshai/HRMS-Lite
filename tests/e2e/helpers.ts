import { expect, type Page } from '@playwright/test';

export const superEmail = () => process.env.E2E_SUPER_EMAIL!;
export const otherEmail = () => process.env.E2E_OTHER_EMAIL!;
export const password = () => process.env.E2E_PASSWORD!;
export const fixturePng = () => process.env.E2E_FIXTURE_PNG!;

export async function signIn(page: Page, email: string, pass = password()) {
    await page.goto('/login');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(pass);
    await page.getByRole('button', { name: 'Log in' }).click();
}

export async function signInAsSuper(page: Page) {
    await signIn(page, superEmail());
    await expect(page).toHaveURL(/\/dashboard$/);
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
}

export async function logout(page: Page) {
    await page.locator('form[action$="/logout"]').first().evaluate((form: HTMLFormElement) => form.submit());
}

export async function changeForcedPassword(page: Page, current: string, next: string) {
    await expect(page.getByRole('heading', { name: /temporary password/i })).toBeVisible();
    await page.getByLabel('Current password').fill(current);
    await page.getByLabel('New password', { exact: true }).fill(next);
    await page.getByLabel('Confirm new password').fill(next);
    await page.getByRole('button', { name: 'Save new password' }).click();
}
