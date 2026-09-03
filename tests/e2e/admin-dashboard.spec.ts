import { expect, test } from "@playwright/test";

test("admin dashboard renders charts without chart errors", async ({ page }) => {
    const consoleErrors: string[] = [];
    page.on("console", (message) => {
        if (message.type() === "error") consoleErrors.push(message.text());
    });

    await page.goto("/admin/login");
    await page.locator('input[type="email"]').fill(process.env.ADMIN_EMAIL ?? "superadmin@example.com");
    await page.locator('input[type="password"]').fill(process.env.ADMIN_PASSWORD ?? "admin123");
    await page.getByRole("button", { name: /Masuk|Login/ }).click();
    await expect(page).toHaveURL(/\/admin\/dashboard/);

    await expect(page.getByText("Tren penjualan", { exact: true })).toBeVisible();
    await expect(page.getByText("Produk terlaris", { exact: true })).toBeVisible();
    await expect(page.getByText("Pesanan berdasarkan status", { exact: true })).toBeVisible();
    await expect(page.locator("canvas")).toHaveCount(4);
    expect(consoleErrors).not.toContain("this.options.ticks.setContext is not a function");
});

test("admin global search finds and opens products and customers", async ({ page }) => {
    await page.goto("/admin/login");
    await page.locator('input[type="email"]').fill(process.env.ADMIN_EMAIL ?? "superadmin@example.com");
    await page.locator('input[type="password"]').fill(process.env.ADMIN_PASSWORD ?? "admin123");
    await page.getByRole("button", { name: /Masuk|Login/ }).click();
    await expect(page).toHaveURL(/\/admin\/dashboard/);

    const search = page.locator('.fi-global-search input[type="search"]');

    await search.fill("On Club 7");
    const productResult = page.locator(".fi-global-search-result-link").filter({ hasText: "On Club 7" }).first();
    await expect(productResult).toBeVisible();
    await productResult.click();
    await expect(page).toHaveURL(/\/admin\/products\/[^/]+$/);

    await page.goto("/admin/dashboard");
    await search.fill("jakubowski.maryam@example.org");
    const customerResult = page.locator(".fi-global-search-result-link").filter({ hasText: "Jesse Dickinson" }).first();
    await expect(customerResult).toBeVisible();
    await customerResult.click();
    await expect(page).toHaveURL(/\/admin\/customer-management\/customers\/[^/]+\/profile/);
});
