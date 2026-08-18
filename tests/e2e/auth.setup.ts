import { test as setup, expect } from '@playwright/test'

const authFile = 'playwright/auth/admin.json'

setup('authenticate as admin', async ({ page }) => {
    await page.goto('/login')

    await page.getByLabel('Email').fill('vanrezkytest@gmail.com')
    await page.getByLabel('Password').fill('Password123@')

    await page.getByRole('button', { name: 'Login' }).click()

    await expect(page).toHaveURL(/account/)

    await page.context().storageState({
        path: authFile,
    })
})